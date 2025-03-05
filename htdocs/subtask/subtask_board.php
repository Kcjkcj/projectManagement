<?php
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    header("Pragma: no-cache");
    header('Expires: 0');
    session_cache_limiter('private_no_expire'); // works
 if(!session_id()){
    session_start();
    }
    $upload_dir = 'uploads/'; // 업로드할 파일을 저장할 디렉토리
    require_once '../DBconfig/Database.php';

    if(isset($_POST['subtask_id']))$subtask_id = $_POST['subtask_id'];
    if(isset($_GET['id']))$subtask_id = $_GET['id'];
    if(isset($_POST['project_id'])) //대쉬보드에서 넘어오는 경우
    { 
        $project_id = $_POST['project_id'];
        $_SESSION['proID'] = $project_id;
    }
    else $project_id = $_SESSION['proID'];
    $user_index = $_SESSION['user'];
    if(isset($_POST['task_id']))
    {
        $task_id = $_POST['task_id']; 
        $_SESSION['taskID'] = $task_id;
    }
    else $task_id = $_SESSION['taskID'];

    $DB = new Database();
    $subtask_info = new subTask($DB,$user_index,$project_id,$task_id,$subtask_id);
    $versions = $subtask_info->getSubTaskVersion();

?>
<html>
    <head>
        <title>프로젝트 정보</title>
        <meta charset="utf-8">
        <button onclick="location.href='../board/main.php'">메인화면으로</button>
        <button onclick="location.href='../task/task.php?id=<?php echo $task_id ?>'">태스크로</button>
        <form action="subtask_history.php" method="GET">
            <select name="version" size="1">
                <?php if($versions) {
                    while($version = $versions->fetch_assoc()) {
                        echo "<option value='{$version['version']}'>서브 태스크 버전 : {$version['version']}</option>";
                    }
                }
                ?>
            </select>
            <input hidden name="subtask_id" value=<?php echo $subtask_id?>>
            <input type="submit" value="해당 버전 서브 태스크 정보 보기">
        </form>
        <!-- CSS 추가 -->
        <style>
            .button-container {
                display: flex;
                justify-content: flex-start;
                gap: 10px; /* 버튼 사이의 간격 */
            }
            .inline-form {
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="file-upload-form">
            <form action="../board/upload_file.php" method="post" enctype="multipart/form-data">
            <label for="file-upload" class="custom-file-upload">
             파일 선택
            </label>
            <input id="file-upload" type="file" name="uploaded_file">
            <input type=hidden name="subtask_id" value="<?php echo $subtask_id;?>">
            <input type=hidden name="project_id" value="<?php echo $project_id;?>">
            <input type=hidden name="task_id" value="<?php echo $task_id;?>">
                <input type="submit" value="파일 업로드">
            </form>
        </div>

        <?php

            class subTask{
                private $db;
                public $userid;
                public $project_id;
                public $task_id;
                public $subtask_id;

                public function __construct(Database $db, $userid,$project_id,$task_id,$subtask_id) {
                    $this->db = $db;
                    $this->userid = $userid;
                    $this->project_id = $project_id;
                    $this->task_id = $task_id;
                    $this->subtask_id=$subtask_id;
                }
                
                public function isRealSubtask()
                {
                    $sql = "SELECT 1 FROM subtask WHERE id = ? LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $this->subtask_id);
                    $result = $stmt->execute();
                    if (!$result){
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                        return false;
                    }
                    return $result;
                }
                
                public function getSubTaskVersion() {
                    $sql = "SELECT `version` FROM subtask WHERE id = ? order by `version` DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $this->subtask_id);
                    $stmt->execute();
                    return $stmt->get_result();
                }

                public function getSubtaskDetails() {
                    $sql =  $sql = "SELECT * from subtask where `id` = ? order by `version` DESC LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("i", $this->subtask_id);
                    if (!$stmt->execute()){
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                        return false;
                    }
                    return $stmt->get_result()->fetch_assoc();
                }

                function getPreTaskName($task_id){
                    $sql = "SELECT `name` from task where `id` = ? order by `version` DESC LIMIT 1";
                    $stmt = $this->db->prepare($sql);
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

                function getPreSubtaskName($subtask_id){
                    $sql =  "SELECT `name` from subtask where `id` = ? order by `version` DESC LIMIT 1";
                    $stmt = $this->db->prepare($sql);
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

                public function getMemberName()
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

                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $this->subtask_id, $this->subtask_id);
                    if (!$stmt->execute()) {
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                        return false;
                    }
                    $assigned_member_username = $stmt->get_result()->fetch_assoc()['username'];
                    return $assigned_member_username;
                }
                
                public function isAssignedMember() 
                //이 경우 프로젝트 멤버가 변경되어 담당 멤버가 수정된 상태에서도 동작하는지 확인해야 함
                {
                    $sql = "SELECT pm.user_index from project_member as pm
                    join subtask as s
                    on s.assigned_member_index = pm.index
                    where s.id = ? 
                        AND s.version = (
                        SELECT MAX(version) 
                        FROM subtask
                        WHERE id = ?
                    )";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii", $this->subtask_id,$this->subtask_id);
                    if (!$stmt->execute()){
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                        return false;
                    }
                    $assigned_memeber_index = $stmt->get_result()->fetch_assoc()['user_index'];
                    if($this->userid !== $assigned_memeber_index) //다른 유저가 로그인 한 경우
                        return false;
                    else
                        return true; //해당 유저가 로그인 한 경우
                }

                public function isProjectMember(){
                    $sql = "SELECT 1 from project_member where user_index = ? and project_id = ? LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("ii",$this->userid, $this->project_id);
                    if (!$stmt->execute()){
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                    }
                    $result = $stmt->get_result();
                    return $result->num_rows >0;
                }

                public function is_manager()
                {
                    $sql = "SELECT 1 from project_member where `project_id`=? and `user_index`=? and `is_manager`=1"; //프로젝트 매니저인지 확인
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('ii',$this->project_id,$this->userid);
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

                public function __destruct() {
                    $this->db->close();
                }

                public function getFileList()
                {
                    $sql = "SELECT `id`,file_path,created_at FROM file_uploads WHERE `subtask_id` = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('i',$this->subtask_id);
                    if (!$stmt->execute()){
                        error_log("Execute failed: " . $stmt->error);
                        $stmt->close();
                        return false;
                    }
                    $stmt->close();
                    return $stmt->get_result();
                }

                

            }

            $is_manager = $subtask_info->is_manager();
            $is_system = $subtask_info->is_system();
            $is_assigned_member = $subtask_info->isAssignedMember();
            //echo $is_manager;
            $result = $subtask_info->isRealSubtask();
            if(!$result) {
                exit("<script>alert('등록된 세부 태스크가 아닙니다!'); location.replace(`../subtask/create_subtask.php`)</script>");
            }
            $row = $subtask_info->getSubtaskDetails();
            $preceding_taskName = $subtask_info->getPreTaskName($row['preceding_task_id']);
            $preceding_subtaskName = $subtask_info->getPreSubtaskName($row['preceding_subtask_id']);
            $assigned_member_username = $subtask_info->getMemberName();
            echo "<div class='subtask-container'>";
            echo "<div class='subtask-title'>세부 태스크 이름 : {$row['name']}</div>";
            echo "<div class='subtask-details'>";
            echo "세부 태스크 VER : {$row['version']} <br>";
            //print_r("태스크 ID : {$row['task_id']} ");
            echo "세부 태스크 최소 소요일 : {$row['min_estimated_days']}일 <br>";
            echo "세부 태스크 할당일 : {$row['allocated_days']}일 <br>";
            echo "세부 태스크 실제 시작일 : {$row['actual_start_date']} <br>";
            echo "세부 태스크 실제 종료일 : {$row['actual_end_date']} <br>";
            echo "선행 태스크  : {$preceding_taskName} <br>";
            echo "선행 세부 태스크  : {$preceding_subtaskName} <br>";
            echo "세부 태스크 할당 유저 : {$assigned_member_username} <br>";
            echo "세부 태스크 생성일 : {$row['created_at']} <br>";
            echo "</div>";
            echo "</div>";
            $is_started = $row['actual_start_date'];
            $is_done = $row['actual_end_date'];

        ?>
        

    <div class="button-container">
        <?php if (!$is_done): ?> <!-- 완료가 되지 않은경우 완료 버튼과 완료 요청 버튼이 표시되게 함 -->
            <?php if ($is_started): ?> <!-- 시작이 된 경우 완료와 완료 버튼만, 그렇지 않은 경우 시작 버튼만 표시 -->

                <?php if ($is_manager || $is_system): ?>
                    <form action="../subtask/subtask_check.php" method="POST" onsubmit="return goToCompletionCheck()">
                        <input type="hidden" name="subtask_id" value="<?php echo $subtask_id ?>"> 
                        <input type="hidden" name="complete" value="true"> 
                        <button type="submit">완료</button>
                    </form>
                <?php endif; ?> <!-- 매니저나 시스템만이 완료 기능을 이용할 수 있음 -->

            <?php if ($is_assigned_member) : ?> <!-- 해당 유저가 로그인 한 경우에만 완료 요청과 시작이 가능 -->
                <form action="../subtask/subtask_check.php" method="POST" onsubmit="return goToCompletionRequest()">
                    <input type="hidden" name="subtask_id" value="<?php echo $subtask_id ?>"> 
                    <input type="hidden" name="task_id" value="<?php echo $task_id ?>"> 
                    <input type="hidden" name="Request" value="true"> 
                    <button type="submit">완료 요청하기</button>
                </form>

            <?php endif; ?>
            <?php endif; ?> <!-- 완료가 되지 않은경우 완료 버튼과 완료 요청 버튼이 표시되게 함 -->

            <?php if ($is_manager || $is_system): ?> <!-- 매니저나 시스템만이 수정 기능을 이용할 수 있음 -->
                <form action="../subtask/update_subtask.php" method="POST" onsubmit="return confirmUpdate()">
                    <input type="hidden" name="subtask_id" value="<?php echo $subtask_id ?>"> 
                    <button type="submit">서브 태스크 수정</button>
                </form>
                <!-- 매니저나 시스템만이 삭제 기능을 이용할 수 있음 -->
                <form action="../subtask/delete_subtask_check.php" method="POST" onsubmit="return confirmDelete()">
                    <input type="hidden" name="subtask_id" value="<?php echo $subtask_id ?>"> 
                    <button type="submit">서브 태스크 삭제</button>
                </form>
            <?php endif; ?>
        <?php endif; ?> <!-- 이미 완료가 된 상태이면 모든 버튼이 표시가 되지 않음 -->
    </div>


        <div class="file-list">
            <!--<h3>업로드된 파일 목록</h3>-->
            <?php
            /*
                $results = $subtask_info->getFileList(); 
                if ($results) {
                    while ($row = $results->fetch_assoc()) {
                        $file_id = $row['id'];
                        $file_created_at = $row['created_at'];
                        $file_path = $row['file_path'];
                        $file_name = basename($file_path);
                        echo "<div class='file-item'>
                        <a href='../board/download_file.php?file=$file_path'>$file_name $file_created_at</a>
                        <form action='../board/delete_file.php' method='post' style='display:inline;'>
                            <input type='hidden' name='file_id' value='$file_id'>
                            <input type='hidden' name='file_path' value='$file_path'>
                            <button type='submit'>삭제</button>
                        </form>
                      </div>";
                    }
                } else {
                    echo "업로드된 파일이 없습니다.";
                }*/
            ?>
        </div>


        <script>
            function goToCompletionCheck() {
                return confirm("해당 서브 태스크를 완료하시겠습니까?");
            }

            function goToCompletionRequest() {
                return confirm("해당 서브 태스크의 완료를 요청하시겠습니까?");
            }

            function confirmUpdate() {
                return confirm("해당 서브 태스크를 수정하시겠습니까?");
            }

            function confirmDelete() {
                return confirm("해당 서브 태스크를 삭제하시겠습니까?");
            }

        </script>
    </body>
</html>