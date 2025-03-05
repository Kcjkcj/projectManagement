<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');

if (!session_id()) {
    session_start();
}
if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}

require_once '../DBconfig/Database.php';

$user_index = $_SESSION['user'];

class User_Statistics {
    private $db;
    private $user_index;
    private $longestPathResult;

    public function __construct(Database $db, $user_index) {
        $this->db = $db;
        $this->user_index = $user_index;
        date_default_timezone_set("Asia/Seoul");
    }

    public function getAllProjectData() {
        $sql = "SELECT DISTINCT
            p.id AS project_id,
            p.name AS project_name,
            p.start_date AS project_start_date,
            p.end_date AS project_end_date,
            t.id AS task_id,
            t.name AS task_name,
            s.id AS subtask_id,
            s.name AS subtask_name,
            s.allocated_days,
            s.actual_start_date AS subtask_start_date,
            s.actual_end_date AS subtask_end_date,
            s.preceding_task_id,
            s.preceding_subtask_id
            FROM 
                project p
            JOIN 
                project_member pm ON p.id = pm.project_id
            LEFT JOIN 
                task t ON p.id = t.project_id
            LEFT JOIN 
                subtask s ON t.id = s.task_id
            WHERE 
                pm.user_index = ?
                AND p.version = (SELECT MAX(version) FROM project WHERE id = p.id)
                AND t.version = (SELECT MAX(version) FROM task WHERE id = t.id)
                AND NOT EXISTS (
                    SELECT 1
                    FROM subtask sub
                    WHERE sub.task_id IN (SELECT id FROM task WHERE project_id = p.id)
                    AND sub.actual_end_date IS NULL
                )
            ORDER BY 
                p.id, t.id, s.id";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log($this->db->get_error());
        }
        $stmt->bind_param("i", $this->user_index);
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
        
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $project_id = intval($row['project_id']);
            $task_id = intval($row['task_id']);
            
            if (!isset($data[$project_id])) {
                $data[$project_id] = [
                    'project_name' => htmlspecialchars($row['project_name']),
                    'project_start_date' => $row['project_start_date'],
                    'project_end_date' => $row['project_end_date'],
                    'tasks' => []
                ];
            }
            
            // 태스크 추가
            if (!isset($data[$project_id]['tasks'][$task_id])) {
                $data[$project_id]['tasks'][$task_id] = [
                    'task_id' => $task_id,
                    'task_name' => htmlspecialchars($row['task_name']),
                    'subtasks' => []
                ];
            }
            
            // 서브태스크 추가
            if ($row['subtask_id'] !== null) { // 서브태스크가 존재하는 경우에만 추가
                $dependencies = [];
                if (!empty($row['preceding_task_id'])) {
                    $dependencies[] = "task_" . $row['preceding_task_id'];
                }
                if (!empty($row['preceding_subtask_id'])) {
                    $dependencies[] = "subtask_" . $row['preceding_subtask_id'];
                }

                $data[$project_id]['tasks'][$task_id]['subtasks'][] = [
                    'subtask_id' => intval($row['subtask_id']),
                    'subtask_name' => htmlspecialchars($row['subtask_name']),
                    'allocated_days' => (int)$row['allocated_days'],
                    'subtask_start_date' => !empty($row['subtask_start_date']) ? new DateTime($row['subtask_start_date']) : null,
                    'subtask_end_date' => !empty($row['subtask_end_date']) ? new DateTime($row['subtask_end_date']) : null,
                    'preceding_task_id' => !empty($row['preceding_task_id']) ? intval($row['preceding_task_id']) : null,
                    'preceding_subtask_id' => !empty($row['preceding_subtask_id']) ? intval($row['preceding_subtask_id']) : null,
                    'dependencies' => $dependencies
                ];
            }
        }
        
        $stmt->close();
        return $data;
    }

    public function set_dependency($data) {
        $tasks = [];
        $completionTimes = [];
        foreach ($data as &$project) {
            foreach ($project['tasks'] as &$task) {
                $taskId = "task_" . $task['task_id'];
                $tasks[$taskId] = [
                    "id" => $task['task_id'],
                    "name" => $taskId,
                    "subtasks" => []
                ];
                foreach ($task['subtasks'] as &$subtask) {
                    $subtaskId = "subtask_" . $subtask['subtask_id'];
                    $tasks[$taskId]["subtasks"][$subtaskId] = [
                        "id" => $subtask['subtask_id'],
                        "name" => $subtaskId,
                        "duration" => $this->getActualDays($subtask),
                        "dependencies" => $subtask['dependencies']
                    ];
                }
            }
        }
    
        foreach ($tasks as $taskId => &$task) {
            foreach ($task['subtasks'] as $subtaskId => &$subtask) {
                $this->calculateSubtaskCompletionTime($subtask, $completionTimes, $tasks);
            }
        }
    
        if (empty($completionTimes)) {
            return ['maxProjectTime' => 0, 'subtaskTimes' => [], 'taskGroupTimes' => []];
        } else {
            $maxProjectTime = max($completionTimes);
            return [
                'subtaskTimes' => $completionTimes,
                'maxProjectTime' => $maxProjectTime,
                'tasks' => $tasks
            ];
        }
    }
    

    private function calculateSubtaskCompletionTime(&$subtask, &$completionTimes, $allTasks) {
        if (isset($completionTimes[$subtask['id']])) {
            return $completionTimes[$subtask['id']];
        }
    
        $maxDependencyTime = 0;
        foreach ($subtask['dependencies'] as $dependency) {
            $dependencyParts = explode('_', $dependency);
            $dependencyType = $dependencyParts[0];
            $dependencyId = $dependencyParts[1];
    
            if ($dependencyType === 'task') {
                foreach ($allTasks[$dependency]['subtasks'] as $depSubtask) {
                    $depTime = $this->calculateSubtaskCompletionTime($depSubtask, $completionTimes, $allTasks);
                    $maxDependencyTime = max($maxDependencyTime, $depTime);
                }
            } elseif ($dependencyType === 'subtask') {
                foreach ($allTasks as $task) {
                    if (isset($task['subtasks'][$dependency])) {
                        $depTime = $this->calculateSubtaskCompletionTime($task['subtasks'][$dependency], $completionTimes, $allTasks);
                        $maxDependencyTime = max($maxDependencyTime, $depTime);
                        break;
                    }
                }
            }
        }
    
        $completionTimes[$subtask['id']] = $maxDependencyTime + $subtask['duration'];
        return $completionTimes[$subtask['id']];
    }
    
    private function getActualDays($subtask) {
        if ($subtask['subtask_start_date'] !== null && $subtask['subtask_end_date'] !== null) {
            return $subtask['subtask_start_date']->diff($subtask['subtask_end_date'])->days + 1;
        }
        return 0;
    }
    public function displayStatistics($data) {
        $dependencyResult = $this->set_dependency($data);
        echo "<h2>프로젝트 및 태스크 통계</h2>";
        
        foreach ($data as &$project) {
            $total_allocated_days = $dependencyResult['maxProjectTime'];
            echo "<h3>프로젝트: " . htmlspecialchars($project['project_name']) . "</h3>";
            
            echo "<table border='1'>";
            echo "<tr><th>태스크</th><th>서브태스크</th><th>할당된 일수</th><th>실제 소요 일수</th><th>누적 소요 일수</th><th>성공 여부</th></tr>";
            
            foreach ($project['tasks'] as &$task) {
                foreach ($task['subtasks'] as &$subtask) {
                    $actual_days = $this->getActualDays($subtask);
                    $subtask_key = "subtask_" . $subtask['subtask_id'];
                    $cumulative_days = $dependencyResult['subtaskTimes'][$subtask['subtask_id']] ?? 'N/A';
                    $is_success = $subtask['allocated_days'] < $actual_days ? "실패" : "성공";
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($task['task_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($subtask['subtask_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($subtask['allocated_days']) . "</td>";
                    echo "<td>" . htmlspecialchars($actual_days) . "</td>";
                    echo "<td>" . htmlspecialchars($cumulative_days) . "</td>";
                    echo "<td>" . htmlspecialchars($is_success) . "</td>";
                    echo "</tr>";
                }
            }
            
            echo "</table>";

            // 프로젝트 성공 여부 판단
            $project_duration = (new DateTime($project['project_start_date']))->diff(new DateTime($project['project_end_date']))->days + 1;
            echo "<p>모든 서브 태스크 완료의 최대 소요일: {$total_allocated_days}일</p>";
            echo "<p>프로젝트 예상 소요일: {$project_duration}일</p>";
            
            if ($total_allocated_days <= $project_duration) {
                echo "<p>프로젝트 성공 여부: 성공</p>";
            } else {
                echo "<p>프로젝트 성공 여부: 실패</p>";
            }
        }
    }

    public function __destruct() {
        if ($this->db) {
            $this->db->close();  // 올바른 메서드 호출 방식으로 수정했습니다.
        }
    }
}

$DB = new Database();
$user_statistics = new User_Statistics($DB, intval($user_index));
$project_data = $user_statistics->getAllProjectData();
$user_statistics->displayStatistics($project_data); // 통계 출력
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로젝트 및 태스크 성공률</title>
</head>
<body>
    <button onclick="location.href='../board/main.php'">메인화면으로</button><br>
</body>
</html>
