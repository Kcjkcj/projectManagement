<!-- 내가 참여한 프로젝트들을 보여주는 페이지 html이랑 php가 좀 셖여서 이 부분은 수정될 가능성이 있음. -->
<?php
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    //header('Cache-Control: no cache'); //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    session_cache_limiter('private_no_expire');
    if(!session_id()){
        session_start();
    }
    $is_logged_in = isset($_SESSION['user']);
    if($is_logged_in)
        $user_index = $_SESSION['user'];
    else
    {
        exit("<script>alert('로그인을 하지 않았습니다.');
        location.replace(`../user/login.php`)</script>");
    }
    require_once '../DBconfig/Database.php';

    $DB = new Database();
    $projects_info = new Projects($DB, $user_index);
    $projects_info->getUsername();
    $is_system = $projects_info->is_system();
?>
<html>
    <head>
        <title>대시보드</title>
        <meta charset="utf-8">
        <button onclick="location.href='../board/main.php'">메인화면으로</button>
        <?php if(!$is_system) : ?>
        <button onclick="location.href='../project/create_project.php'">프로젝트 생성</button>
        <?php endif; ?>
    </head>
    <body>
            <button onclick="location.href='../user/logout.php'">로그아웃</button>
        <h1>나의 프로젝트</h1>
            <?php
                $is_logged_in = isset($_SESSION['user']);
                if($is_logged_in)
                    $user_index = $_SESSION['user'];
                else
                {
                    exit("<script>alert('로그인을 하지 않았습니다.');
                    location.replace(`../user/login.php`)</script>");
                }
                require_once '../DBconfig/Database.php';
                class Projects{
                    private $db;
                    public $userid;
    
                    public function __construct(Database $db, $userid) {
                        $this->db = $db;
                        $this->userid = $userid;
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

                    public function getProjects() {
                            $sql = "SELECT DISTINCT p.*, pm.user_index
                                    FROM project p
                                    JOIN project_member pm ON p.id = pm.project_id
                                    JOIN (
                                        SELECT id, MAX(version) AS latest_version
                                        FROM project
                                        GROUP BY id
                                    ) AS latest_versions ON p.id = latest_versions.id AND p.version = latest_versions.latest_version
                                    WHERE pm.user_index = ?
                                    ORDER BY p.created_at DESC";
                            $stmt = $this->db->prepare($sql);
                            $stmt->bind_param("i", $this->userid);
                            if (!$stmt->execute()){
                                error_log("Execute failed: " . $stmt->error);
                                $stmt->close();
                                exit("<script>alert('등록된 프로젝트가 아닙니다!');
                                location.replace(`../board/main.php`)</script>");
                            }
                            $rows = $stmt->get_result();
                            echo "<div style='overflow:auto; width:90%; height:40%;' class='Alarm-list'>";
                            if($rows){
                                while($row = $rows->fetch_assoc())
                                {
                                    echo "<div class='project-item'>";
                                    echo "프로젝트 ID : {$row['id']} <br>";
                                    //echo "프로젝트 VER : {$row['version']} <br>";
                                    echo "<div class='project-title'> 프로젝트 이름 : {$row['name']}</div>";
                                    echo "<div class='project-details'>프로젝트 설명 : {$row['description']} <br></div>";
                                    echo "프로젝트 시작일 : {$row['start_date']} <br>";
                                    echo "프로젝트 종료일 : {$row['end_date']} <br>";
                                    echo "프로젝트 생성일 : {$row['created_at']} <br>";
                                    echo "<br>";
                                    echo "<form action='../project/project.php?id={$row['id']}' method='post'>";
                                    echo "<input type='hidden' name='id' value='{$row['id']}'>";
                                    echo "<input type='submit' class='project-button' value='자세히보기'>";
                                    echo "</form>";
                                    echo "</div>";
                                }
                        }
                        echo "</div>";
                    }

                    public function SysgetProjects() {
                        $sql = "SELECT DISTINCT p.*
                                FROM project p
                                JOIN (
                                    SELECT id, MAX(version) AS latest_version
                                    FROM project
                                    GROUP BY id
                                ) AS latest_versions ON p.id = latest_versions.id AND p.version = latest_versions.latest_version
                                ORDER BY p.created_at DESC";
                        $stmt = $this->db->prepare($sql);
                        if (!$stmt->execute()){
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                            exit("<script>alert('등록된 프로젝트가 아닙니다!');
                            location.replace(`../board/main.php`)</script>");
                        }
                        $rows = $stmt->get_result();
                        echo "<div style='overflow:auto; width:90%; height:40%;' class='Alarm-list'>";
                        if($rows){
                            while($row = $rows->fetch_assoc())
                            {
                                echo "<div class='project-item'>";
                                echo "프로젝트 ID : {$row['id']} <br>";
                                //echo "프로젝트 VER : {$row['version']} <br>";
                                echo "<div class='project-title'> 프로젝트 이름 : {$row['name']}</div>";
                                echo "<div class='project-details'>프로젝트 설명 : {$row['description']} <br></div>";
                                echo "프로젝트 시작일 : {$row['start_date']} <br>";
                                echo "프로젝트 종료일 : {$row['end_date']} <br>";
                                echo "프로젝트 생성일 : {$row['created_at']} <br>";
                                echo "<br>";
                                echo "<form action='../project/project.php?id={$row['id']}' method='post'>";
                                echo "<input type='hidden' name='id' value='{$row['id']}'>";
                                echo "<input type='submit' class='project-button' value='자세히보기'>";
                                echo "</form>";
                                echo "</div>";
                            }
                    }
                    echo "</div>";
                }

                    public function isProjectMember($project_id){
                        $sql = "SELECT 1 from project_member where user_index = ? and project_id = ? LIMIT 1";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("ii",$this->userid, $project_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        return $result->num_rows >0;
                    }

                    public function getAlarm() { //완료 요청 알림
                        $sql = "SELECT project_id, created_member_index, notify_task_id, notify_subtask_id, title, content,created_at FROM article WHERE enable_notify = 1 order by created_at DESC";
                        $stmt = $this->db->prepare($sql);
                        if(!$stmt->execute()) {
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                        }
                        $rows = $stmt->get_result();

                        echo "<div style='overflow:auto; width:90%; height:40%;' class='Alarm-list'>";
                        if($rows){
                            while($row = $rows->fetch_assoc())
                            {
                                $is_member = $this->isProjectMember($row['project_id']); //로그인한 유저가 해당 프로젝트의 멤버인지 검사
                                if($is_member)
                                {
                                    echo "<div class='Alarm-item'>";
                                    echo "<div class='Alarm-title'> 알림 : {$row['title']}</div>";
                                    echo "<div class='Alarm-details'> 알림 내용 : {$row['content']} <br></div>";
                                    echo "알림 생성일 : {$row['created_at']} <br>";
                                    echo "<br>";
                                    if(!$row['created_member_index'] == null) 
                                    //시스템 메시지의 경우는 생성, 삭제, 수정 등의 내용이므로 해당 페이지로 이동할 필요는 없음
                                    {
                                        //아래의 경우는 완료 요청의 경우로 페이지로 이동할 필요가 있음
                                        echo "<form action='../subtask/subtask_board.php?id={$row['notify_subtask_id']}' method='post'>";
                                        echo "<input type='hidden' name='project_id' value='{$row['project_id']}'>";
                                        echo "<input type='hidden' name='task_id' value='{$row['notify_task_id']}'>";
                                        echo "<input type='hidden' name='subtask_id' value='{$row['notify_subtask_id']}'>";
                                        echo "<input type='submit' class='project-button' value='자세히보기'>";
                                        echo "</form>";
                                    }
                                    echo "===============================================";
                                    echo "</div>";
                                    
                                }
                            }
                        }
                        echo "</div>";
                    }
                    

                    public function getUsername(){
                        $sql =  "SELECT username from user where `index` = ? LIMIT 1";
                        $stmt = $this->db->prepare($sql);
                        $stmt->bind_param("i", $this->userid);
                        if (!$stmt->execute()){
                            error_log("Execute failed: " . $stmt->error);
                            $stmt->close();
                            exit("<script>alert('등록된 유저가 아닙니다!');
                            location.replace(`../board/main.php`)</script>");
                        }
                        $user_name = $stmt->get_result()->fetch_assoc()['username'];
                        print_r($user_name);
                        echo "님이 참여하는 프로젝트\n"; //여기까지 세션의 유저 index로 유저명을 알아내는 것
                    }
                    
                    public function __destruct() {
                        $this->db->close();
                    }
                }

                if($is_system){
                    $projects_info->SysgetProjects();
                    //echo str_repeat("=",180);
                }
                else{
                    $projects_info->getProjects();
                    echo str_repeat("=",180);
                    $projects_info->getAlarm();
                }
            ?>
    </body>
</html>