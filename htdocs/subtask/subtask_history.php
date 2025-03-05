<?php
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    header("Pragma: no-cache");
    header('Expires: 0');
    session_cache_limiter('private_no_expire'); // works
    
 if(!session_id()){
    session_start();
    }


    $user_index = $_SESSION['user']; //현재 로그인 한 유저
    $subtask_id = $_GET['subtask_id'];
    if(isset($_GET['version'])) {
        $version = $_GET['version'] ?? null;
    }

    require_once '../DBconfig/Database.php';
                
    $DB = new Database();

?>
<html>
    <head>
        <title>프로젝트 정보</title>
        <meta charset="utf-8">
        <button onclick='location.href=`../subtask/subtask_board.php?id=<?php echo $subtask_id ?>`'>해당 서브 태스크로</button>
    </head>

    <body>
        <?php

            function getSubTaskVersion(Database $DB, $subtask_id, $version) {
                $sql = "SELECT * FROM subtask WHERE id = ? and `version`= ?";
                $stmt = $DB->prepare($sql);
                $stmt->bind_param("ii",$subtask_id, $version);
                $stmt->execute();
                return $stmt->get_result()->fetch_assoc();
            }

            function getPreTaskName(Database $DB, $task_id){
                    $sql = "SELECT `name` from task where `id` = ? order by `version` DESC LIMIT 1";
                    $stmt = $DB->prepare($sql);
                    $stmt->bind_param("i", $task_id);
                    if (!$stmt->execute()){
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                        return false;
                    }
                    $result = $stmt->get_result();
                    if($result->num_rows>0)
                        return $result->fetch_assoc()['name'];
                    else
                        return null;
            }

            function getPreSubtaskName(Database $DB, $subtask_id){
                $sql =  "SELECT `name` from subtask where `id` = ? order by `version` DESC LIMIT 1";
                $stmt = $DB->prepare($sql);
                $stmt->bind_param("i", $subtask_id);
                if (!$stmt->execute()){
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    return false;
                }
                $result = $stmt->get_result();
                if($result->num_rows>0)
                    return $result->fetch_assoc()['name'];
                else
                    return null;  
            }

            function getMemberName(Database $DB, $subtask_id)
            {
                $sql = "SELECT u.username
                FROM project_member AS pm
                JOIN subtask AS s ON s.assigned_member_index = pm.index
                JOIN user AS u ON u.index = pm.user_index
                WHERE s.id = ? 
                AND s.version = (
                    SELECT MAX(version) 
                    FROM subtask
                    WHERE id = ?
                )";

                $stmt = $DB->prepare($sql);
                $stmt->bind_param("ii", $subtask_id, $subtask_id);
                if (!$stmt->execute()) {
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    return false;
                }
                $assigned_member_username = $stmt->get_result()->fetch_assoc()['username'];
                return $assigned_member_username;
            }
            
            $row = getSubTaskVersion($DB, $subtask_id, $version);
            $preceding_taskName = getPreTaskName($DB,$row['preceding_task_id']);
            $preceding_subtaskName = getPreSubtaskName($DB,$row['preceding_subtask_id']);
            $assigned_member_username = getMemberName($DB,$subtask_id);

            echo "<div class='project-container'>";
            echo "<div class='project-title'>서브 태스크 이름 : {$row['name']}</div>";
            echo "<div class='project-details'>";
            echo "<strong>서브 태스크 VER</strong>: {$row['version']}<br>";
            echo "<strong>서브 태스크 설명</strong>: {$row['description']}<br>";
            echo "<strong>서브 태스크 최소 소요일</strong>: {$row['min_estimated_days']}<br>";
            echo "<strong>서브 태스크 실제 시작일</strong>: {$row['actual_start_date']}<br>";
            echo "<strong>서브 태스크 실제 종료일</strong>: {$row['actual_end_date']}<br>";
            echo "<strong>서브 태스크 선행 태스크</strong>: {$preceding_taskName}<br>";
            echo "<strong>서브 태스크 선행 세부 태스크</strong>: {$preceding_subtaskName}<br>";
            echo "<strong>서브 태스크 할당 유저</strong>: {$assigned_member_username}<br>";
            echo "<strong>서브 태스크 할당일</strong>: {$row['allocated_days']}<br>";
            echo "<strong>서브 태스크 생성일</strong>: {$row['created_at']}<br>";
            echo "</div>";
            echo "</div>";

        ?>

    </body>
</html>