<?php
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    header("Pragma: no-cache");
    header('Expires: 0');
    session_cache_limiter('private_no_expire'); // works
    
 if(!session_id()){
    session_start();
    }

                
    if(isset($_POST['id'])) {
        $project_id = $_POST['id'];
    } else {
        $project_id = $_SESSION['proID'];
    }
    $user_index = $_SESSION['user']; //현재 로그인 한 유저
    
    require_once '../DBconfig/Database.php';
                
    $DB = new Database();
    $project_info = new Project($DB,$project_id,$user_index);
    $versions = $project_info->getProjectVersion();

    if(isset($_POST['auto_adjust'])) {
        $result = $project_info->autoAdjustSubtasks();
    }

    if(isset($_POST['adjust_after_delay'])) {
        $result = $project_info->adjustSubtasksAfterDelay();
    }
?>
<html>
    <head>
        <title>프로젝트 정보</title>
        <meta charset="utf-8">
        <button onclick="location.href='../board/main.php'">메인화면으로</button>
        <button onclick='location.href=`../board/dash_board.php`'>나의 프로젝트</button>
        <button onclick='location.href=`../board/board.php`'>게시판</button>
        <form action="project_history.php" method="GET">
            <select name="version" size="1">
                <?php if($versions) {
                    while($version = $versions->fetch_assoc()) {
                        echo "<option value='{$version['version']}'>프로젝트 버전 : {$version['version']}</option>";
                    }
                }
                ?>
            </select>
            <input type="submit" value="해당 버전 프로젝트 정보 보기">
        </form>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <!-- CSS 추가 -->
    </head>
    <body>
        <?php

            class Project{
                private $db;
                public $id;
                public $userid;
                private $longestPathResult;

                public function __construct(Database $db, $id, $userid) {
                    $this->db = $db;
                    $this->id = $id;
                    $this->userid = $userid;
                }

                public function is_manager()
                {
                    $sql = "SELECT 1 from project_member where `project_id`=? and `user_index`=? and `is_manager`=1"; //프로젝트 매니저인지 확인
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('ii',$this->id,$this->userid);
                    $stmt->execute();
                    $result = $stmt->get_result()->num_rows;
                    $stmt->close();
                    return $result>0;
                }
        
                public function is_system()
                {
                    $sql = "SELECT 1 from user where `index`=? and `is_admin`=1"; //프로젝트 매니저인지 확인
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('i',$this->userid);
                    $stmt->execute();
                    $result = $stmt->get_result()->num_rows;
                    $stmt->close();
                    return $result>0;
                }
            
                public function getProjectDetails() {
                    $sql = "SELECT * FROM project WHERE id = ? order by `version` DESC LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $this->id);
                    $stmt->execute();
                    return $stmt->get_result()->fetch_assoc();
                }

                public function getProjectVersion() {
                    $sql = "SELECT `version` FROM project WHERE id = ? order by `version` DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $this->id);
                    $stmt->execute();
                    return $stmt->get_result();
                }

                public function isProjectMember(){
                    if($this->is_system())
                        return true;
                    $sql = "SELECT 1 from project_member where user_index = ? and project_id = ? LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii",$this->userid, $this->id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    return $result->num_rows >0;
                }
            
                public function getTasks(){ //태스크 id얻는거 가능
                    $sql = "SELECT *
                            FROM task t
                            WHERE t.project_id = ?
                            AND t.version = (
                                SELECT MAX(version)
                                FROM task
                                WHERE project_id = ? AND id = t.id
                            )
                            ORDER BY t.created_at DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $this->id,$this->id);
                    $stmt->execute();
                    return $stmt->get_result();
                }

                public function getTaskName($task_id) //테스트 용 함수 실제로는 id로만 계산하게 해야함
                {
                    $sql = "SELECT t.name
                    FROM task t
                    WHERE t.id = ?
                    AND t.version = (
                        SELECT MAX(version)
                        FROM task
                        WHERE id = t.id
                    )
                    ORDER BY t.created_at DESC Limit 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $task_id);
                    $stmt->execute();
                    return $stmt->get_result()->fetch_assoc()['name'];
                }


                public function getSubTaskName($subtask_id) //테스트 용 함수 실제로는 id로만 계산하게 해야함
                {
                    $sql = "SELECT s.name
                    FROM subtask s
                    WHERE s.id = ?
                    AND s.version = (
                        SELECT MAX(version)
                        FROM subtask
                        WHERE id = s.id
                    )
                    ORDER BY s.created_at DESC Limit 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $subtask_id);
                    $stmt->execute();
                    return $stmt->get_result()->fetch_assoc()['name'];
                }

                public function getSubTaskMinDays($subtask_id)
                {
                    $sql = "SELECT s.min_estimated_days
                    FROM subtask s
                    WHERE s.id = ?
                    AND s.version = (
                        SELECT MAX(version)
                        FROM subtask
                        WHERE id = s.id
                    )
                    ORDER BY s.created_at DESC Limit 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $subtask_id);
                    $stmt->execute();
                    return $stmt->get_result()->fetch_assoc()['min_estimated_days'];
                }

                public function set_dependency() {
                    $tasks = [];
                    $resultTasks = $this->getTasks(); // 태스크 데이터 가져오기

                    while ($task = $resultTasks->fetch_assoc()) {
                        $taskID = $task['id'];
                        $taskName = $task['name'];

                        $sqlSubtasks = "SELECT st.id, st.name, st.min_estimated_days AS duration, st.preceding_task_id, st.preceding_subtask_id
                                        FROM subtask AS st
                                        WHERE st.task_id = ?
                                        AND st.version = (
                                            SELECT MAX(st2.version)
                                            FROM subtask AS st2
                                            WHERE st2.task_id = st.task_id AND st2.id = st.id
                                        )";

                        $stmtSubtasks = $this->db->prepare($sqlSubtasks);
                        $stmtSubtasks->bind_param("i", $taskID);
                        $stmtSubtasks->execute();
                        $resultSubtasks = $stmtSubtasks->get_result();

                        $subtasks = [];

                        while ($subtask = $resultSubtasks->fetch_assoc()) {
                            $dependencies = [];

                            // 의존성 설정
                            if (!empty($subtask['preceding_task_id'])) {
                                $dependencies[] = "task_" . $subtask['preceding_task_id'];
                            }
                            if (!empty($subtask['preceding_subtask_id'])) {
                                $dependencies[] = "subtask_" . $subtask['preceding_subtask_id'];
                            }

                            $subtasks[] = [
                                "id" => $subtask['id'],
                                "name" => "subtask_" . $subtask['id'], // 고유 ID 설정
                                "duration" => (int)$subtask['duration'],
                                "dependencies" => $dependencies
                            ];
                        }

                        // 태스크에 서브태스크 추가
                        $tasks["task_" . $taskID] = [
                            "subtasks" => $subtasks
                        ];
                    }

                    // 최장 경로 계산 함수 호출
                    $result = $this->calculateLongestPath($tasks);

                    // 결과 출력
                    echo "모든 세부 태스크를 완료하는 데 걸리는 최대 시간: " . $result['maxProjectTime'] . "일<br>";
                   
                   // 정렬된 결과 출력
                    $sortedResults = [];

                    foreach ($result['subtaskTimes'] as $subtaskId => $time) {
                        $subtaskName = $this->getSubTaskName($subtaskId);
                        $sortedResults[$subtaskName] = $time;
                    }

                    // 알파벳 순으로 정렬
                    ksort($sortedResults);

                    // 정렬된 결과 출력
                    echo "<table border='1'>";
                    echo "<tr><th>서브태스크</th><th>완료 시간 (일)</th></tr>";
                    foreach ($sortedResults as $subtaskName => $time) {
                        echo "<tr><td>{$subtaskName}</td><td>{$time}</td></tr>";
                    }
                    echo "</table>";
                    // 결과 저장
                    $this->longestPathResult = $result;

                }

                private function calculateLongestPath($tasks) {
                    $completionTimes = [];
                
                    // 모든 서브태스크의 완료 시간을 계산
                    foreach ($tasks as $taskName => $taskInfo) {
                        foreach ($taskInfo['subtasks'] as $subtask) {
                            $this->calculateSubtaskCompletionTime($subtask, $completionTimes, $tasks);
                        }
                    }
                
                    // 빈 배열 체크 추가
                    if (empty($completionTimes)) {
                        return ['maxProjectTime' => 0, 'subtaskTimes' => [], 'taskGroupTimes' => []];
                    } else {
                        // 전체 프로젝트의 최대 완료 시간 계산
                        $maxProjectTime = max($completionTimes);
                
                        return [
                            'subtaskTimes' => $completionTimes,
                            'maxProjectTime' => $maxProjectTime,
                            'subtasks' => $tasks
                        ];
                    }
                }
                
                private function calculateSubtaskCompletionTime($subtask, &$completionTimes, $allTasks) {
                    if (isset($completionTimes[$subtask['id']])) {
                        return $completionTimes[$subtask['id']];
                    }
                    
                    $maxDependencyTime = 0;
                    foreach ($subtask['dependencies'] as $dependency) {
                        if (strpos($dependency, 'task_') === 0) { // 선행 태스크
                            $taskID = substr($dependency, 5); // "task_" 제거
                            foreach ($allTasks[$dependency]['subtasks'] as $depSubtask) {
                                $depTime = $this->calculateSubtaskCompletionTime($depSubtask, $completionTimes, $allTasks);
                                $maxDependencyTime = max($maxDependencyTime, $depTime);
                            }
                        } else { // 선행 서브태스크
                            $depSubtask = $this->findSubtask($dependency, $allTasks);
                            if ($depSubtask) {
                                $depTime = $this->calculateSubtaskCompletionTime($depSubtask, $completionTimes, $allTasks);
                                $maxDependencyTime = max($maxDependencyTime, $depTime);
                            }
                        }
                    }
                
                    $completionTimes[$subtask['id']] = $maxDependencyTime + $subtask['duration'];
                    return $completionTimes[$subtask['id']];
                }

                private function findSubtask($subtaskName, $allTasks) {
                    foreach ($allTasks as $taskInfo) {
                        foreach ($taskInfo['subtasks'] as $subtask) {
                            if ($subtask['name'] === $subtaskName) {
                                return $subtask;
                            }
                        }
                    }
                    return null;
                }

                private function setActualStartDateForIndependentSubtasks($subtaskId, $actual_start_date) {
                    $sql = "UPDATE subtask AS t1
                            JOIN (
                                SELECT id, MAX(version) AS max_version
                                FROM subtask
                                GROUP BY id
                            ) AS t2 ON t1.id = t2.id AND t1.version = t2.max_version
                            SET t1.actual_start_date = ?
                            WHERE t1.id = ? 
                            AND t1.preceding_task_id IS NULL 
                            AND t1.preceding_subtask_id IS NULL
                            AND t1.actual_start_date IS NULL";
                    
                    //SET t1.actual_start_date = CURRENT_TIMESTAMP
                    $stmt = $this->db->prepare($sql);
                    if (!$stmt) {
                        error_log("Prepare failed: " . $this->db->get_error());
                        return false;
                    }
                    
                    $stmt->bind_param("si",$actual_start_date,$subtaskId); //시나리오를 위해 프로젝트 예상 시작일 기입
                    if (!$stmt->execute()) {
                        error_log("Execute failed: " . $stmt->error);
                        return false;
                    }
                    
                    $stmt->close();
                    return true;
                }

                public function autoAdjustSubtasks() {
                    // 이전에 계산한 결과가 없으면 다시 계산
                    if (!isset($this->longestPathResult)) {
                        $this->set_dependency();
                    }
                
                    $result = $this->longestPathResult;
                    $maxProjectTime = $result['maxProjectTime'];
                
                    // 프로젝트 전체 일정 가져오기
                    $projectDetails = $this->getProjectDetails();
                    $projectTotalDays = (int)((strtotime($projectDetails['end_date']) - strtotime($projectDetails['start_date'])) / (60*60*24)); 
                    $projectTotalDays += 1; //시작일 포함한 것으로 전체일정을 계산
                    echo "프로젝트 전체 일정 : ".$projectTotalDays. "<br>";
                    // 스케일링 팩터 계산
                    $scalingFactor = $projectTotalDays / $maxProjectTime;
                    echo "스케일링 계수 : ".$scalingFactor. "<br>";
                    // 각 서브태스크의 할당일 계산 및 업데이트
                    foreach ($result['subtaskTimes'] as $subtaskId => $completionTime) {
                        //선행 태스크와 선행 세부 태스크 둘다 없는 경우 바로 그 세부 태스크는 시작
                        $this->setActualStartDateForIndependentSubtasks($subtaskId, $projectDetails['start_date']);
                        //원래는 현재일로 하는게 맞는데 시연을 위해서 프로젝트 예상 시작일을 가져옴
                        
                        // 서브태스크 정보 가져오기
                        $subtaskDuration = $this->getSubTaskMinDays($subtaskId);
                        $allocatedDays = floor($subtaskDuration * $scalingFactor);
                
                        // 최소 소요일보다 작은지 확인
                        if ($allocatedDays < $subtaskDuration) {
                            $allocatedDays = $subtaskDuration;
                        }
                        $subtaskName = $this->getSubTaskName($subtaskId);
                        echo $subtaskName . ":" . $allocatedDays . "일 <br>";
                
                        // DB 업데이트
                        
                        $sql = "UPDATE subtask AS t1
                                JOIN (
                                    SELECT id, MAX(version) AS max_version
                                    FROM subtask
                                    GROUP BY id
                                ) AS t2 ON t1.id = t2.id AND t1.version = t2.max_version
                                SET t1.allocated_days = ?
                                WHERE t1.id = ?"; //해당 최신 버전 상태에서의 할당일을 수정
                        $stmt = $this->db->prepare($sql);
                        if (!$stmt) {
                            error_log("Prepare failed: " . $this->db->get_error());
                            return false;
                        }
                        $stmt->bind_param("ii", $allocatedDays, $subtaskId);
                        if (!$stmt->execute()) {
                            error_log("Execute failed: " . $stmt->error); // SQL 실행 실패 시 로그 남기기
                            return false;
                        }
                        $stmt->close();
                        
                    }
                
                    return true;
                }

                public function adjustSubtasksAfterDelay() {
                    //echo "adjustSubtasksAfterDelay 함수 시작<br>";
                    $delayedSubtasks = $this->getDelayedSubtasks();
                    if (empty($delayedSubtasks)) {
                        echo "지연된 서브태스크가 없습니다.<br>";
                        return true;
                    }
                
                    $projectDetails = $this->getProjectDetails();
                    $projectTotalDays =(int) ((strtotime($projectDetails['end_date']) - strtotime($projectDetails['start_date'])) / (60*60*24) + 1);
                    echo "프로젝트 전체 일정: {$projectTotalDays}일<br>";
                
                    foreach ($delayedSubtasks as $subtask) {
                        $delayDays =(int)((strtotime($subtask['actual_end_date']) - strtotime($subtask['actual_start_date'])) / (60*60*24) + 1 - $subtask['allocated_days']);
                        echo "지연된 서브태스크 ID: {$subtask['id']}, 이름: {$subtask['name']}, 지연 일수: {$delayDays}일<br>";
                        $this->adjustDependentSubtasks($subtask['id'], $delayDays, $projectTotalDays);
                    }
                
                    //echo "adjustSubtasksAfterDelay 함수 종료<br>";
                    return true;
                }

                private function getDelayedSubtasks() {
                    $sql = "SELECT s.id, s.name, s.allocated_days, s.actual_start_date, s.actual_end_date
                            FROM subtask s
                            JOIN (
                                SELECT id, MAX(version) as max_version
                                FROM task
                                WHERE project_id = ?
                                GROUP BY id
                            ) t ON s.task_id = t.id
                            WHERE s.version = (
                                SELECT MAX(version)
                                FROM subtask
                                WHERE id = s.id
                            )
                            AND s.actual_start_date IS NOT NULL
                            AND s.actual_end_date IS NOT NULL";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $this->id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    //echo $result->num_rows;
                    $delayedSubtasks = [];
                    while ($row = $result->fetch_assoc()) {
                        // 실제 소요일 계산
                        $actualCompletionDays = (strtotime($row['actual_end_date']) - strtotime($row['actual_start_date'])) / (60*60*24) + 1;

                        // 디버그 정보 출력
                        echo "<pre>";
                        echo "서브태스크 : " . $row['name'] . "\n";
                        echo "할당일 (allocated_days): " . $row['allocated_days'] . "\n";
                        echo "실제 시작일 (actual_start_date): " . $row['actual_start_date'] . "\n";
                        echo "실제 종료일 (actual_end_date): " . $row['actual_end_date'] . "\n";
                        echo "실제 소요일 (actualCompletionDays): " . $actualCompletionDays . "\n";
                        echo "</pre>";
                
                        // 지연 여부 판단
                        if ($actualCompletionDays > $row['allocated_days']) {
                            echo "서브태스크 " . $row['name'] . "는 지연되었습니다.<br>";
                            $delayedSubtasks[] = $row;
                        } else {
                            echo "서브태스크 " . $row['name'] . "는 지연되지 않았습니다.<br>";
                        }
                    }
                    return $delayedSubtasks;
                }
                
                private function adjustDependentSubtasks($subtaskId, $delayDays, $projectTotalDays) {
                    $dependentSubtasks = $this->getDependentSubtasks($subtaskId);
                    if (empty($dependentSubtasks)) {
                        return;
                    }
                
                    $currentTime = $this->getSubtaskCompletionTime($subtaskId);
                    $remainingTime = $projectTotalDays - $currentTime;
                    echo "현재 시간: {$currentTime}일 지남, 남은 시간: {$remainingTime}일<br>";
                
                    // 의존성 그래프 구축
                    $dependencyGraph = $this->buildDependencyGraph($dependentSubtasks);
                
                    // 병렬 진행을 고려한 최장 경로 계산
                    $longestPath = $this->calculateLongestPathSimple($dependencyGraph);
                    echo "의존적인 서브태스크들의 최장 경로 소요 시간: {$longestPath}일<br>";
                
                    if ($longestPath <= $remainingTime) {
                        echo "현재 할당일로 프로젝트 일정 내에 완료 가능합니다. 조정이 필요하지 않습니다.<br><br>";
                        return;
                    }
                
                    $scalingFactor = $remainingTime / $longestPath;
                    echo "새로운 스케일링 계수: " . $scalingFactor . "<br>";
                
                    foreach ($dependentSubtasks as $dependentSubtaskId) {
                        $sql = "SELECT s1.name, s1.allocated_days FROM subtask s1 
                                JOIN (SELECT id, MAX(version) as max_version FROM subtask GROUP BY id) s2 
                                ON s1.id = s2.id AND s1.version = s2.max_version 
                                WHERE s1.id = ?";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("i", $dependentSubtaskId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                
                        $currentAllocatedDays = $row['allocated_days'];
                        $minDays = $this->getSubTaskMinDays($dependentSubtaskId);
                        $newAllocatedDays = max(floor($currentAllocatedDays * $scalingFactor), $minDays);
                        $subtaskName = $row['name'];
                
                        echo "서브태스크 {$subtaskName}의 현재 할당일: {$currentAllocatedDays}일<br>";
                        echo "서브태스크 {$subtaskName}의 새로운 할당일: {$newAllocatedDays}일<br>";
                        echo "서브태스크 {$subtaskName}의 최소 소요일: {$minDays}일<br><br>";
                
                        $this->updateSubtaskAllocatedDays($dependentSubtaskId, $newAllocatedDays);
                    }
                }
                
                private function buildDependencyGraph($subtasks) {
                    $graph = [];
                    foreach ($subtasks as $subtaskId) {
                        $graph[$subtaskId] = [
                            'duration' => $this->getSubTaskAllocatedDays($subtaskId),
                            'dependencies' => $this->getDirectDependencies($subtaskId)
                        ];
                    }
                    return $graph;
                }

                private function dfs($subtaskId, $graph, &$memo) {
                    if (!isset($graph[$subtaskId])) {
                        error_log("Subtask ID $subtaskId not found in graph");
                        return 0;
                    }
                    
                    if (isset($memo[$subtaskId])) {
                        return $memo[$subtaskId];
                    }
                
                    $maxLength = 0;
                    if (isset($graph[$subtaskId]['dependencies']) && is_array($graph[$subtaskId]['dependencies'])) {
                        foreach ($graph[$subtaskId]['dependencies'] as $dependencyId) {
                            $maxLength = max($maxLength, $this->dfs($dependencyId, $graph, $memo));
                        }
                    }
                
                    $memo[$subtaskId] = $maxLength + $graph[$subtaskId]['duration'];
                    return $memo[$subtaskId];
                }
                
                private function getDirectDependencies($subtaskId) {
                    $sql = "SELECT preceding_subtask_id, preceding_task_id 
                            FROM subtask 
                            WHERE id = ? AND version = (SELECT MAX(version) FROM subtask WHERE id = ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $subtaskId, $subtaskId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                
                    $dependencies = [];
                    if ($row['preceding_subtask_id']) {
                        $dependencies[] = $row['preceding_subtask_id'];
                    }
                    if ($row['preceding_task_id']) {
                        $sqlTaskSubtasks = "SELECT id FROM subtask WHERE task_id = ? AND version = (SELECT MAX(version) FROM subtask WHERE task_id = ?)";
                        $stmtTaskSubtasks = $this->db->prepare($sqlTaskSubtasks);
                        $stmtTaskSubtasks->bind_param("ii", $row['preceding_task_id'], $row['preceding_task_id']);
                        $stmtTaskSubtasks->execute();
                        $resultTaskSubtasks = $stmtTaskSubtasks->get_result();
                        while ($rowTaskSubtask = $resultTaskSubtasks->fetch_assoc()) {
                            $dependencies[] = $rowTaskSubtask['id'];
                        }
                    }
                    return $dependencies;
                }
                
                private function calculateLongestPathSimple($graph) {
                    if (empty($graph)) {
                        error_log("Empty graph provided to calculateLongestPathSimple");
                        return 0;
                    }
                
                    $memo = [];
                    $longestPath = 0;
                
                    foreach ($graph as $subtaskId => $info) {
                        $pathLength = $this->dfs($subtaskId, $graph, $memo);
                        $longestPath = max($longestPath, $pathLength);
                    }
                
                    return $longestPath;
                }
                private function getSubTaskAllocatedDays($subtaskId) {
                    $sql = "SELECT allocated_days FROM subtask WHERE id = ? AND version = (SELECT MAX(version) FROM subtask WHERE id = ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $subtaskId, $subtaskId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    return $row['allocated_days'];
                }
                
                private function getDependentSubtasks($subtaskId) {
                    $subtasks = [];
                    $queue = [$subtaskId];
                    $visited = [];
                
                    while (!empty($queue)) {
                        $currentSubtaskId = array_shift($queue);
                        if (in_array($currentSubtaskId, $visited)) continue;
                        $visited[] = $currentSubtaskId;
                
                        // 1. 직접적인 서브태스크 의존성 확인
                        $sqlSubtask = "SELECT s1.id 
                                       FROM subtask s1
                                       JOIN (SELECT id, MAX(version) as max_version FROM subtask GROUP BY id) s2 
                                       ON s1.id = s2.id AND s1.version = s2.max_version
                                       WHERE s1.preceding_subtask_id = ?";
                        $stmtSubtask = $this->db->prepare($sqlSubtask);
                        $stmtSubtask->bind_param("i", $currentSubtaskId);
                        $stmtSubtask->execute();
                        $resultSubtask = $stmtSubtask->get_result();
                
                        //echo "서브태스크 ID {$currentSubtaskId}에 직접 의존하는 서브태스크:<br>";
                        while ($row = $resultSubtask->fetch_assoc()) {
                            if (!in_array($row['id'], $subtasks)) {
                                $subtasks[] = $row['id'];
                                $queue[] = $row['id'];
                                //echo "- 서브태스크 ID: {$row['id']}<br>";
                            }
                        }
                
                        // 2. 현재 서브태스크의 태스크 ID 가져오기
                        $sqlCurrentTask = "SELECT s1.task_id 
                                           FROM subtask s1
                                           JOIN (SELECT id, MAX(version) as max_version FROM subtask GROUP BY id) s2 
                                           ON s1.id = s2.id AND s1.version = s2.max_version
                                           WHERE s1.id = ?";
                        $stmtCurrentTask = $this->db->prepare($sqlCurrentTask);
                        $stmtCurrentTask->bind_param("i", $currentSubtaskId);
                        $stmtCurrentTask->execute();
                        $resultCurrentTask = $stmtCurrentTask->get_result();
                        $currentTask = $resultCurrentTask->fetch_assoc();
                
                        if ($currentTask) {
                            $currentTaskId = $currentTask['task_id'];
                            //echo "서브태스크 ID {$currentSubtaskId}가 속한 태스크 ID: {$currentTaskId}<br>";
                
                            // 3. 태스크 레벨 의존성 확인
                            $sqlDependentSubtasks = "SELECT s1.id 
                                                     FROM subtask s1
                                                     JOIN (SELECT id, MAX(version) as max_version FROM subtask GROUP BY id) s2 
                                                     ON s1.id = s2.id AND s1.version = s2.max_version
                                                     WHERE s1.preceding_task_id = ?";
                            $stmtDependentSubtasks = $this->db->prepare($sqlDependentSubtasks);
                            $stmtDependentSubtasks->bind_param("i", $currentTaskId);
                            $stmtDependentSubtasks->execute();
                            $resultDependentSubtasks = $stmtDependentSubtasks->get_result();
                
                            //echo "태스크 ID {$currentTaskId}를 선행 태스크로 가지는 서브태스크:<br>";
                            while ($rowSubtask = $resultDependentSubtasks->fetch_assoc()) {
                                if (!in_array($rowSubtask['id'], $subtasks)) {
                                    $subtasks[] = $rowSubtask['id'];
                                    $queue[] = $rowSubtask['id'];
                                    echo "- 서브태스크 ID: {$rowSubtask['id']}<br>";
                                }
                            }
                        }
                    }
                
                    //echo "총 의존적인 서브태스크 수: " . count($subtasks) . "<br>";
                    return $subtasks;
                }
                
                private function getSubtaskCompletionTime($subtaskId) {
                    $sql = "SELECT s.actual_start_date, s.actual_end_date, s.preceding_subtask_id, s.preceding_task_id 
                            FROM subtask s 
                            WHERE s.id = ? AND s.version = (SELECT MAX(version) FROM subtask WHERE id = ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $subtaskId, $subtaskId);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                
                    if (!$result['actual_end_date']) {
                        return 0;
                    }
                
                    $actualDays = (strtotime($result['actual_end_date']) - strtotime($result['actual_start_date'])) / (60*60*24) + 1;
                
                    $precedingTime = 0;
                    if ($result['preceding_subtask_id']) {
                        $precedingTime = $this->getSubtaskCompletionTime($result['preceding_subtask_id']);
                    } elseif ($result['preceding_task_id']) {
                        $precedingTime = $this->getTaskCompletionTime($result['preceding_task_id']);
                    }
                
                    return $precedingTime + $actualDays;
                }
                
                private function getTaskCompletionTime($taskId) {
                    $sql = "SELECT s.actual_start_date, s.actual_end_date
                            FROM subtask s 
                            WHERE s.task_id = ? AND s.version = (SELECT MAX(version) FROM subtask WHERE task_id = s.task_id AND id = s.id)
                            AND s.actual_start_date IS NOT NULL AND s.actual_end_date IS NOT NULL";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $taskId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                
                    $totalDuration = 0;
                    while ($row = $result->fetch_assoc()) {
                        $duration = (strtotime($row['actual_end_date']) - strtotime($row['actual_start_date'])) / (60*60*24) + 1;
                        $totalDuration += $duration;
                    }
                
                    return $totalDuration;
                }

                private function updateSubtaskAllocatedDays($subtaskId, $newAllocatedDays) {
                    $sql = "UPDATE subtask s1
                            JOIN (
                                SELECT id, MAX(version) as max_version
                                FROM subtask
                                GROUP BY id
                            ) s2 ON s1.id = s2.id AND s1.version = s2.max_version
                            SET s1.allocated_days = ?
                            WHERE s1.id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $newAllocatedDays, $subtaskId);
                    $stmt->execute(); 
                }



                public function __destruct() {
                    $this->db->close();
                }
            }


            $row = $project_info->getProjectDetails();
            $is_manager = $project_info->is_manager();
            $is_system = $project_info->is_system();

            // 자동 조정 버튼 표시 (프로젝트 관리자나 시스템 관리자만 가능)
            if($is_manager || $is_system){
                echo "<br>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='auto_adjust' value='1'>";
                echo "<button type='submit'>프로젝트 시작하기</button>";
                echo "</form>";
                echo "<br>";

                // 지연 후 재조정 버튼 추가
                echo "<form method='post'>";
                echo "<input type='hidden' name='adjust_after_delay' value='1'>";
                echo "<button type='submit'>지연된 작업 이후 할당일 재조정</button>";
                echo "</form>";
                echo "<br>";
            }

            if(!$row) {
                $project_info->__destruct();
                exit("<script>alert('등록된 프로젝트가 아닙니다!'); location.replace(`../proejct/create_project.php`)</script>");
            }

            $ispromem = $project_info->isProjectMember();
            if(!$ispromem) {
                $project_info->__destruct();
                exit("<script>alert('등록된 멤버가 아닙니다!'); location.replace(`../board/dash_board.php`)</script>");
            }

            $_SESSION["proID"] = $row['id'];

            echo "<div class='project-container'>";
            echo "<div class='project-title'>프로젝트 이름 : {$row['name']}</div>";
            echo "<div class='project-details'>";
            echo "<strong>프로젝트 VER</strong>: {$row['version']}<br>";
            echo "<strong>프로젝트 설명</strong>: {$row['description']}<br>";
            echo "<strong>프로젝트 시작일</strong>: {$row['start_date']}<br>";
            echo "<strong>프로젝트 종료일</strong>: {$row['end_date']}<br>";
            echo "<strong>프로젝트 생성일</strong>: {$row['created_at']}<br>";
            echo "</div>";
            echo "</div>";

            //$sql = "SELECT * from tasks where `version` = (SELECT MAX(`version`)from tasks where `project_id` = $project_id) and `project_id`=$project_id";
            //이렇게 SQL짜면 그냥 버전이 가장높은 태스크 하나만 나옴
            $tasks = $project_info->getTasks();
            if($tasks->num_rows>0) {
                echo "<div class='tasks-list'>";
                while($task = $tasks->fetch_assoc()) {
                    echo "<div class='tasks-item'>";
                    echo "<div class='tasks-title'>태스크 이름 : {$task['name']}</div>";
                    echo "<div class='tasks-details'>태스크 설명: {$task['description']}<br></div>";
                    echo "<form action='../task/task.php?id={$task['id']}' method='post'>";
                    echo "<input type='hidden' name='id' value='{$task['id']}'>";
                    echo "<input type='submit' class='tasks-button' value='자세히보기'>";
                    echo "</form>";
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "등록된 태스크가 없습니다.";
            }
            //$project_info->__destruct();
            if($is_manager){ //프로젝트 관리자 만이 새로운 태스크 생성가능 
                 echo "<button onclick='location.href=`../task/create_task.php`'>새 태스크 생성하기</button>";
            }

            //$project_info->set_dependency();
        ?>

        <!-- 프로젝트 수정 버튼 -->
        <!-- 수정 버튼을 클릭하면 로그인 된 유저의 id로 해당 프로젝트 관리자인지 확인. 프로젝트 관리자가 아니면 수정 금지 -->

        <?php if($is_manager || $is_system) : ?>
            <div class='update'>
            <button onclick='confirmUpdate()'>프로젝트 수정</button>
            </div>

            <div class='delete'>
            <button onclick='confirmDelete()'>프로젝트 삭제</button>
            </div>
        <?php endif; ?>
        <script>
            function confirmDelete() {
                if (confirm("해당 프로젝트를 삭제하시겠습니까?")) {
                    // 사용자가 "확인"을 누르면 delete_project_check.php 페이지로 이동
                    location.href = '../project/delete_project_check.php';
                } else {
                    // 사용자가 "취소"를 누르면 아무런 동작도 하지 않음
                    return false;
                }
            }

            function confirmUpdate() {
                if (confirm("해당 프로젝트를 수정하시겠습니까?")) {
                    // 사용자가 "확인"을 누르면 delete_project_check.php 페이지로 이동
                    location.href = '../project/update_project.php';
                } else {
                    // 사용자가 "취소"를 누르면 아무런 동작도 하지 않음
                    return false;
                }
            }
        </script>
    </body>
</html>
