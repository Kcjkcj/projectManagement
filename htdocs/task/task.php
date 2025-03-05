<!-- 'A'(예시)태스크에 대한 정보 (A태스크와 거기에 속한 세부태스크) / 각 세부태스크에 대응하는 페이지는 필요없을 것이라 예상함. -->
<?php
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    header("Pragma: no-cache");
    header('Expires: 0');
    session_cache_limiter('private_no_expire'); // works
 if(!session_id()){
    session_start();
    }
    require_once '../DBconfig/Database.php';
                
    if(isset($_POST['id'])) //대쉬보드에서 프로젝트 자세히보기 클릭하면 그 프로젝트의 id를 post로 넘겨줌
    {
        $task_id = $_POST['id']; //dash_board에서
        //해당 프로젝트의 자세히보기를 클릭할 때 task_id를 받아옴 이걸로 task검색하면 됨
    }
    else //post로 넘어온 정보가 아니라면.. 즉 대쉬보드 이외의 창에서 project로 넘어올 때.. 
    //project.php에서 생성된 세션정보 -> ex 태스크 생성하고 나서 해당 프로젝트 정보로 넘어갈 때 
    {
        $task_id = $_SESSION['taskID'];
    }
        $user_index = $_SESSION['user'];
        $project_id = $_SESSION['proID'];

        $DB = new Database();
        $task_info = new Task($DB,$user_index,$project_id,$task_id);
        $versions = $task_info->getTaskVersion();

?>
<html>
    <head>
    <title>태스크 정보</title>
        <meta charset="utf-8">
        <button onclick="location.href='../board/main.php'">메인화면으로</button>
        <button onclick="location.href='../project/project.php?id=<?php echo $_SESSION['proID']; ?>'">프로젝트로</button>
        <form action="task_history.php" method="GET">
            <select name="version" size="1">
                <?php if($versions) {
                    while($version = $versions->fetch_assoc()) {
                        echo "<option value='{$version['version']}'>태스크 버전 : {$version['version']}</option>";
                    }
                }
                ?>
            </select>
            <input type="submit" value="해당 버전 태스크 정보 보기">
        </form>
        <!--<button onclick='location.href=`../board/board.php?task_id=<?php echo $task_id?>`'>게시판</button>-->
        <!-- CSS 추가 -->
    </head>
    <body>
            <?php
                class Task{
                    private $db;
                    public $userid;
                    public $project_id;
                    public $task_id;
    
                    public function __construct(Database $db, $userid,$project_id,$task_id) {
                        $this->db = $db;
                        $this->userid = $userid;
                        $this->project_id = $project_id;
                        $this->task_id = $task_id;
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

                    public function getMemberName($subtask_id)
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
                        $stmt->bind_param("ii", $subtask_id, $subtask_id);
                        if (!$stmt->execute()) {
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                            return false;
                        }
                        $assigned_member_username = $stmt->get_result()->fetch_assoc()['username'];
                        return $assigned_member_username;
                    }

                    
                    public function getTaskVersion() {
                        $sql = "SELECT `version` FROM task WHERE id = ? order by `version` DESC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("i", $this->task_id);
                        $stmt->execute();
                        return $stmt->get_result();
                    }
                    
                    public function isRealTask()
                    {
                        $sql = "SELECT 1 FROM task WHERE id = ? LIMIT 1";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("i", $this->task_id);
                        $result = $stmt->execute();
                        if (!$result){
                            return false;
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                        }
                        return $result;
                    }
                    
                    public function getTaskDetails() {
                        $sql = "SELECT * FROM task WHERE id = ? order by `version` DESC LIMIT 1";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("i", $this->task_id);
                        if (!$stmt->execute()){
                            return false;
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                        }
                        return $stmt->get_result()->fetch_assoc();
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
                
                    public function getSubTasks() {
                        $sql = "SELECT st.* 
                                FROM subtask AS st
                                INNER JOIN (
                                    SELECT id, MAX(version) AS latest_version 
                                    FROM subtask 
                                    WHERE task_id = ? 
                                    GROUP BY id
                                ) AS latest_versions
                                ON st.id = latest_versions.id AND st.version = latest_versions.latest_version
                                WHERE st.task_id = ?
                                ORDER BY st.created_at DESC";
                    
                        $stmt = $this->db->prepare($sql);
                        if (!$stmt) {
                            error_log("Prepare failed: " . $this->db->get_error());
                            return false;
                        }
                    
                        $stmt->bind_param("ii", $this->task_id, $this->task_id);
                        if (!$stmt->execute()) {
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                            return false;
                        }
                    
                        $result = $stmt->get_result();
                        $stmt->close();
                        return $result;
                    }

                    public function __destruct() {
                        $this->db->close();
                    }
                }
                

                $is_manager = $task_info->is_manager();
                $is_system = $task_info->is_system();
                $result = $task_info->isRealTask();
                if(!$result)
                {
                   exit("<script>alert('등록된 태스크가 아닙니다!');
                    location.replace(`../task/create_task.php`)</script>");
                }
                $row = $task_info->getTaskDetails(); //row는 인덱스 0~으로 assoc은 컬럼명, array는 둘다 대신 오버헤드가 큼
                echo "<div class='task-container'>";
                echo "<div class='task-title'>태스크 이름 : {$row['name']}</div>";
                echo "<div class='task-details'>";
                //echo "<strong>태스크 ID</strong>: {$row['id']} <br>";
                echo "<strong>태스크 VER</strong> : {$row['version']} <br> ";
                //echo "<strong>프로젝트 ID</strong> : {$row['project_id']} <br>";
                echo "<strong>태스크 이름</strong> : {$row['name']} <br>";
                echo "<strong>태스크 설명</strong> : {$row['description']} <br>";
                echo "<strong>태스크 생성일</strong> : {$row['created_at']}";
                //지금은 대쉬보드로 되어있지만 project정보.php를 따로 만들어서 거기서 id로 구분하면 될 듯 
                echo "</div>";
                echo "</div>";
                $_SESSION["taskID"] = $row['id']; //이 정보를 세션에 담아두면 subtask_check.php에서 편하게 조회가 가능.
                //print_r($_SESSION['taskID']);
                
                $subtasks = $task_info->getSubTasks();
                if(!$subtasks)
                {
                    echo "<script>alert('등록된 세부 태스크가 아닙니다!')";
                }
                    echo "<div class='subtask-list'>";
                    while($row = $subtasks->fetch_assoc())
                    {
                        $preceding_taskName = $task_info->getPreTaskName($row['preceding_task_id']);
                        $preceding_subtaskName = $task_info->getPreSubtaskName($row['preceding_subtask_id']);
                        $assigned_member_username = $task_info->getMemberName($row['id']);
                        //$sql = "SELECT * from subtask where `version`=
                        //(SELECT MAX(`version`) from subtask where `id` = $subtask_id_array[$i]) and `id`=$subtask_id_array[$i]"; 
                        //해당 서브 태스크 id에서 버전이 높은 것만 추려서 출력 이 경우 성능이슈 발생 가능.. 이런식으로 
                        echo "<div class='subtask-item'>";
                        echo "<div class='subtask-title'>세부 태스크 이름 : {$row['name']}</div>";
                        echo "<div class='subtask-details'>세부 태스크 설명: {$row['description']}<br></div>";
                        //print_r("세부 태스크 ID : {$row['id']} ");
                        echo "세부 태스크 VER : {$row['version']} <br>";
                        //print_r("태스크 ID : {$row['task_id']} ");
                        echo "세부 태스크 최소 소요일 : {$row['min_estimated_days']}일 <br>";
                        echo "세부 태스크 할당일 : {$row['allocated_days']}일 <br>";
                        echo "세부 태스크 실제 시작일 : {$row['actual_start_date']} <br>";
                        echo "세부 태스크 실제 종료일 : {$row['actual_end_date']} <br>";
                        echo "선행 태스크 : {$preceding_taskName} <br>";
                        echo "선행 세부 태스크 : {$preceding_subtaskName} <br>";
                        echo "세부 태스크 할당 유저 : {$assigned_member_username} <br>";
                        echo "세부 태스크 생성일 : {$row['created_at']} <br>";
                        
                        echo "<form action='../subtask/subtask_board.php?id={$row['id']}' method='post'>";
                        echo "<input type='hidden' name='subtask_id' value={$row['id']}>";
                        echo "<input type='submit' value='자세히보기'>";
                        echo "</form>";
                        //여기에 똑같이 하고 삭제 버튼 만들면 될 듯
                        echo "</div>"; //역시 서브태스크를 수정하려면 각 서브태스크를 보여주는 페이지가 따로 있어야 겠는데?
                    } //반복문이 끝나도 이 html정보는 페이지에 남아 렌더링 된 상태로 유지가 됨 실제로 테스트 해봤을 때도 동일함
                    //다만 id를 사용자가 조작할 수도 있으니 이에 대한 유효성 검사를 해야함.
                
                echo "</div>";
                if($is_manager){ //관리자나 시스템 만이 새로운 세부 태스크 생성이 가능
                echo "<button onclick='location.href=`../subtask/create_subtask.php`'>새 세부 태스크 생성하기</button>";
                }


            ?>
        </form>
        <br>
        <?php if($is_manager || $is_system) : ?> <!-- 매니저와 시스템만 수정, 삭제 기능 사용가능 -->    
            <div class="update">
                <button onclick='confirmUpdate()'>태스크 수정</button>
            </div>

            <div class="delete">
                <button onclick='confirmDelete()'>태스크 삭제</button>
            </div>
        <?php endif; ?>

        <script>
            function confirmDelete() {
                if (confirm("해당 태스크를 삭제하시겠습니까?")) {
                    location.href = '../task/delete_task_check.php';
                } else {
                    // 사용자가 "취소"를 누르면 아무런 동작도 하지 않음
                    return false;
                }
            }

            function confirmUpdate() {
                if (confirm("해당 태스크를 수정하시겠습니까?")) {
                    location.href = '../task/update_task.php';
                } else {
                    // 사용자가 "취소"를 누르면 아무런 동작도 하지 않음
                    return false;
                }
            }
            
        </script>
    </body>
        <!-- 태스크 수정 버튼 -->
        <!-- 수정 버튼을 클릭하면 로그인 된 유저의 id로 해당 프로젝트 관리자인지 확인. 프로젝트 관리자가 아니면 수정 금지 -->
        <!-- 이러면 서브태스크 수정페이지도 따로 만들긴 해야겠네. 수정 버튼은.. 사각형 안에 만드는게 일단 가장 좋은듯-->
    <body>
    </body>

</html>