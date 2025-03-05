<!-- 태스크 생성 페이지에서 입력한 정보들을 DB에 넣고, 무결성 검사. 프로젝트 테이블에서 없는 id가 들어오면 DB에 오류 메시지. 
 (서순적으로는 프로젝트 페이지에서 여기로 넘어오기 때문에 프로젝트 id를 세션에서 받아옴.) 이 과정이 없으면 이상한 접속인 것으로 판단하려는 의도도 있음.-->
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

        require_once '../DBconfig/Database.php';
        //create_project에서 넘겨받는 변수들
        class Update_Task{
            private $db;
            public $project_id;
            public $task_id;
            public $userid;
            private $updated_at;
            private $version;

            public function __construct(Database $db, $userid, $project_id,$task_id) {
                $this->db = $db;
                $this->project_id = $project_id;
                $this->task_id = $task_id;
                $this->userid = $userid;
                date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
                $this->updated_at = date("Y-m-d H:i:s",time());
            }

            public function __destruct() {
                $this->db->close();
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

            public function set_version()
            {
                $sql = "SELECT `version` from task where `id`=? order by `version` DESC LIMIT 1" ; //버전 필드를 역순으로 해서 버전이 높은걸 들고와야 함.
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$this->task_id);
                if (!$stmt->execute()){
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                }
                $row = $stmt->get_result()->fetch_assoc();
                $this->version = $row['version']+1;
                $stmt->close();
            }
            public function update_task($taskName, $taskDes)
            {
                $sql = "INSERT INTO task(id, `version`, `project_id`, `name`, `description`,`created_at`)
                     VALUES(?,?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $this->set_version();
                $stmt->bind_param('iiisss',$this->task_id,$this->version,$this->project_id,$taskName,$taskDes,$this->updated_at);
                $result =  $stmt->execute(); //쿼리 성공 여부
                if(!$result){
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                }
                $stmt->close();
                return $result;
            }

        }
        //세션 정보, DB접속 변수
        $user_index = $_SESSION['user'];
        $project_id = $_SESSION['proID']; //세션정보에 프로젝트 인덱스 정보가 있음
        $task_id = $_SESSION['taskID'];
        //create_project에서 넘겨받는 변수들
        $taskName=$_POST["task_name"];
        $taskDes=$_POST["task_description"];
        date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
        $created_at = date("Y-m-d H:i:s",time());

        if($taskName==null || $taskDes==null)
        {
            //exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
               // location.replace(`../task/create_task.php`)</script>");
        }
            $DB = new Database();
            $update_task = new Update_Task($DB,$user_index,$project_id,$task_id);
            $is_manager = $update_task->is_manager();
            $is_system = $update_task->is_system();
            if(($is_manager || $is_system)){
                $result = $update_task->update_task($taskName,$taskDes);
                if($result)
                {
                    echo "태스크 수정 성공"; //이후 project_member tb도 같이 채워야지
                    exit("<script>alert('태스크가 수정되었습니다.');
                    location.replace(`../project/project.php?id=$project_id`)</script>");

                }
                else 
                { 
                    error_log("태스크 수정 실패");
                    exit("<script>alert('태스크 수정에 실패하였습니다.');
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