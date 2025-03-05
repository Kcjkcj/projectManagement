<!-- 프로젝트 수정에서 있을 수 있는 일 
 : 이름변경, 설명 변경, 멤버 변경(이 경우는 좀 더 생각할 필요가 있음),  종료일 변경-->
<?php
    header('Cache-Control: no cache'); //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    session_cache_limiter('private_no_expire');
    if(!session_id())
    {
        session_start();
    }
?>
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <?php
        //세션 정보, DB접속 변수
        if(!$_SESSION['user'])
        {
            exit("<script>alert(`로그인을 하지 않았습니다.\n프로젝트 수정에 실패하였습니다.`);
                location.replace(`../board/main.php`)</script>");
        }
        else
        {
         $user_index = $_SESSION['user'];
        }
        require_once '../DBconfig/Database.php';
        //create_project에서 넘겨받는 변수들
        class update_project{
            private $db;
            public $project_id;
            public $userid;
            private $updated_at;
            private $promem_index;
            private $version;

            public function __construct(Database $db, $userid, $project_id) {
                $this->db = $db;
                $this->project_id = $project_id;
                $this->promem_index = rand(1000000,9999999);
                $this->userid = $userid;
                date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
                $this->updated_at = date("Y-m-d H:i:s",time());
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

            public function promemindex_check($promem_index)
            {
                $sql = "SELECT 1 FROM project_member WHERE `index`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$promem_index);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_promem_id =  rand(1000000,9999999);
                    return $this->promemindex_check($new_promem_id);
                }
                $stmt->close();
                return $promem_index;
            }

            public function __destruct() {
                $this->db->close();
            }

            public function set_version()
            {
                $sql = "SELECT `version` from project where `id`=? order by `version` DESC LIMIT 1" ; //버전 필드를 역순으로 해서 버전이 높은걸 들고와야 함.
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$this->project_id);
                if (!$stmt->execute()){
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                }
                $row = $stmt->get_result()->fetch_assoc();
                $this->version = $row['version']+1;
                $stmt->close();
            }
            public function update_project($proName,$proDes,$EstiStart_date,$EstiEnd_date)
            {
                $sql = "INSERT INTO project(id, `version`, `name`, `description`,`start_date`,`end_date`,`created_at`)
                    VALUES(?,?,?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    // prepare 실패 시 오류 로그를 추가
                    error_log("Prepare failed: " . $this->db->get_error());
                    return false;
                }
                $this->set_version();
                $stmt->bind_param("iisssss",$this->project_id,$this->version,$proName,$proDes,$EstiStart_date,$EstiEnd_date,$this->updated_at);
                $result =  $stmt->execute(); //쿼리 성공 여부
                if (!$result) {
                    error_log("Execute failed: " . $stmt->error); // SQL 실행 실패 시 로그 남기기
                }
                $stmt->close();
                return $result;
            }

            public function update_manager($username) //create_project에서 만들어진 project index가 필요함
            {
                $is_manager = 1;
                $this->promem_index = $this->promemindex_check($this->promem_index);
                $sql = "INSERT into project_member (`index`, `project_id`, `user_index`, `is_manager`, `joined_at`)
                    VALUES(?,?,?,?,?)"; //프로젝트를 생성한 사람은 관리자
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iiiis',$this->promem_index,$this->project_id,$this->userid,$is_manager,$this->updated_at);
                $result = $stmt->execute(); //쿼리 성공 여부
                if (!$result) {
                    error_log("Execute failed: " . $stmt->error); // SQL 실행 실패 시 로그 남기기
                }
                $stmt->close();
                return $result;
            }

            public function update_member($username)
            {
                $is_manager = 0;
                $sql = "SELECT `index` from user where `email`= ? LIMIT 1"; //해당 이름을 가진 유저의 index
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("s",$username);
                if ($stmt->execute()) {
                    error_log("Execute failed: " . $stmt->error); // SQL 실행 실패 시 로그 남기기
                }
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $row = $result->fetch_assoc();
                    $to_add_user_index = $row['index'];
                    $this->promem_index = $this->promemindex_check($this->promem_index);
                    $sql = "INSERT INTO project_member (`index`, `project_id`, `user_index`, `is_manager`, `joined_at`) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('iiiis',$this->promem_index,$this->project_id,$to_add_user_index,$is_manager,$this->updated_at);
                    $result = $stmt->execute(); //쿼리 성공 여부
                    if (!$result) {
                        error_log("Execute failed: " . $stmt->error); // SQL 실행 실패 시 로그 남기기
                    }
                }
                $stmt->close();
                return $result;
            }

        }
        $proName=$_POST["project_name"];
        $proDes=$_POST["project_description"];
        $EstiStart_date=$_POST["project_start_date"];
        $EstiEnd_date=$_POST["project_end_date"];
        $inputs = $_POST['inputs'];

        if($proName==null || $proDes==null)
        {
            exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
                location.replace(`../project/update_project.php`)</script>");
        }
            $project_id = $_SESSION['proID'];
            $DB = new Database();
            $new_project = new update_project($DB,$user_index,$project_id);
            $is_manager = $new_project->is_manager();
            $is_system = $new_project->is_system();
            if(($is_manager || $is_system))
            {
                $result = $new_project->update_project($proName,$proDes,$EstiStart_date,$EstiEnd_date);
                if($result)
                {
                    echo "프로젝트 수정 성공"; //이후 project_member tb도 같이 채워야지

                    //user table에 있는 이름인지 검사하고 promem에 넣어야지
                    // foreach ($inputs as $input) //입력된 멤버를 넣어주는 부분
                    // {
                    //     if(!empty($input))
                    //     { //여기가 안들어오는데
                    //         //echo "<script>alert('$input')</script>"; //테스트
                    //         $promem_result = $new_project->update_member($input);
                    //             if(!$promem_result){
                    //                 //$new_project->__destruct();
                    //                 exit("<script>alert('등록되지 않은 유저입니다.');
                    //                 location.replace(`../board/dash_board.php`)</script>");
                    //             }
                            
                    //     }
                    //     else
                    //     {
                    //         //$new_project->__destruct();
                    //         echo "<script>alert('프로젝트 멤버는 아직 결정되지 않았습니다.')</script>";
                    //     }
                    // }
                    // echo "<script>alert('프로젝트의 멤버가 갱신되었습니다.')</script>";
                    //$new_project->__destruct();              
                        exit("<script>alert('프로젝트가 수정되었습니다.');
                    location.replace(`../board/dash_board.php`)</script>");

                }
                else 
                { 
                    //$new_project->__destruct();
                    exit("<script>alert('프로젝트 수정에 실패하였습니다.');
                    location.replace(`../board/dash_board.php`)</script>");
                }
            }
            else
            {
                exit("<script>alert('관리자가 아닙니다!');
                location.replace(history.back(-1))</script>");
            }
        ?>
    </body>

</html>