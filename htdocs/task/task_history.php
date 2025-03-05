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
    $task_id = $_SESSION['taskID'];
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
        <button onclick='location.href=`../task/task.php?id=<?php echo $task_id ?>`'>해당 태스크로</button>
    </head>

    <body>
        <?php

            function getTaskVersion(Database $DB, $task_id, $version) {
                $sql = "SELECT * FROM task WHERE id = ? and `version`= ?";
                $stmt = $DB->prepare($sql);
                $stmt->bind_param("ii",$task_id, $version);
                $stmt->execute();
                return $stmt->get_result()->fetch_assoc();
            }
            
            $row = getTaskVersion($DB, $task_id, $version);
            echo "<div class='project-container'>";
            echo "<div class='project-title'>태스크 이름 : {$row['name']}</div>";
            echo "<div class='project-details'>";
            echo "<strong>태스크 VER</strong>: {$row['version']}<br>";
            echo "<strong>태스크 설명</strong>: {$row['description']}<br>";
            echo "<strong>태스크 생성일</strong>: {$row['created_at']}<br>";
            echo "</div>";
            echo "</div>";

        ?>

    </body>
</html>