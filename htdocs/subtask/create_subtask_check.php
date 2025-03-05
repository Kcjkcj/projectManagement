<!-- 세부 태스크 생성 페이지에서 입력된 정보를 DB에 저장, 무결성 검사. 여기서 관련된 멤버가 되면 프로젝트 멤버 테이블에 갱신이 되어야 함.
 -->
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
        $user_index = $_SESSION['user'];
        $task_id = $_SESSION['taskID'];
        $project_id = $_SESSION['proID']; //세션정보에 프로젝트 인덱스 정보가 있음
        require_once '../DBconfig/Database.php';

        class Create_Subtask{
            private $db;
            public $project_id;
            public $task_id;
            public $subtask_id;
            public $userid;
            private $created_at;
            private $version;
            private $article_index;
            //private $promem_index;

            public function __construct(Database $db, $userid, $project_id, $task_id) {
                $this->db = $db;
                $this->project_id = $project_id;
                $this->task_id = $task_id;
                $this->subtask_id = rand(1000000,9999999);
                //$this->promem_index = rand(1000000,9999999);
                $this->userid = $userid;
                $this->version = 1;
                date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
                $this->created_at = date("Y-m-d H:i:s",time());
                $this->article_index = rand(1,89999999)+10000000;
            }

            public function subtask_index_check($subtaskindex)
            {
                $sql = $sql = "SELECT * FROM subtask WHERE `id`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$subtaskindex);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_subtask_id = rand(1000000,9999999);
                    return $this->subtask_index_check($new_subtask_id);
                }
                $stmt->close();
                return $subtaskindex;
            }

            public function is_manager()
            {
                $sql = "SELECT `is_manager` from project_member where `project_id`=? and `user_index`=?"; //프로젝트 매니저인지 확인
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

            /*
            public function promem_index_check($promem_index)
            {
                $sql = "SELECT 1 FROM project_member WHERE `index`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$promem_index);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_promem_id =  rand(1000000,9999999);
                    return $this->promem_index_check($new_promem_id);
                }
                $stmt->close();
                return $promem_index;
            }

            public function create_member($username)
            {
                $is_manager = 0;
                $sql = "SELECT `index` from user where `username`= ? LIMIT 1"; //해당 이름을 가진 유저의 index
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("s",$username);
                if (!$stmt->execute()) {
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    return false;
                }
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $row = $result->fetch_assoc();
                    $to_add_user_index = $row['index'];
                    $this->promem_index = $this->promem_index_check($this->promem_index);
                    $sql = "INSERT INTO project_member (`index`, `project_id`, `user_index`, `is_manager`, `joined_at`) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('iiiis',$this->promem_index,$this->project_id,$to_add_user_index,$is_manager,$this->created_at);
                    $result = $stmt->execute(); //쿼리 성공 여부
                }
                $stmt->close();
                return $result;
            }
            */
            public function article_index_check($article_index)
            {
                $sql = "SELECT 1 from article where `index`= ? LIMIT 1"; //select 1 -> 레코드 존재 여부확인
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i",$article_index);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows >0)
                {
                    echo "중복된 index<br>";
                    $new_index = rand(10000000,99999999);
                    return $this->article_index_check($new_index);
                }
    
                return $article_index;
            }

            public function create_subtask($subtaskName, $subtaskDes, $subminTerm, $proTID, $proSTID, $mem_id)
            {
                $this->subtask_id = $this->subtask_index_check($this->subtask_id);
                //$this->create_member($memname);
                $proTID = isset($proTID) ? $proTID : null;
                $proSTID = isset($proSTID) ? $proSTID : null;

                $sql = "INSERT INTO subtask(`id`, `version`, `task_id`, `assigned_member_index`, `name`, `description`, `min_estimated_days`, `preceding_task_id`, `preceding_subtask_id`, `created_at`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("iiiissiiis", 
                    $this->subtask_id,
                    $this->version, 
                    $this->task_id, 
                    $mem_id,
                    $subtaskName, 
                    $subtaskDes, 
                    $subminTerm, 
                    $proTID, 
                    $proSTID, 
                    //$this->promem_index, 
                    $this->created_at
                );

                if (!$stmt->execute()) {
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    return false;
                }

                $stmt->close();
                return true;
            }


            public function __destruct() {
                $this->db->close();
            }

        }
        //create_project에서 넘겨받는 변수들
        $subtaskName=$_POST["subtask_name"];
        $subtaskDes=$_POST["subtask_description"];
        $subminTerm = (int)$_POST["min_term"]; //POST로 받으면 문자열로 받게 됨. 원래 형식을 유지하지 않음
        $proTID = (int)$_POST["pro_taskID"]; //입력을 안하면 0이됨. - 이거 좀 주의해야 하겠네. 근데 숫자로 바꾸니 NULL이 안되네
        $proSTID = (int)$_POST["pro_subtaskID"];
        $mem_id =  (int)($_POST['memID']);
        //index 생성 변수
        if($proTID>9999999 || $proTID<1000001)
        {
            $proTID=NULL;
        }
        if($proSTID>9999999 || $proSTID<1000001)
        {
            $proSTID=NULL;
        }

        if($subtaskName==null || $subtaskDes==null || $mem_id==null)
        {
            exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
                location.replace(`../subtask/create_subtask.php`)</script>");
        }
            $DB = new Database();
            $new_subtask = new Create_Subtask($DB,$user_index,$project_id,$task_id);
            $is_manager = $new_subtask->is_manager();
            $is_system = $new_subtask->is_system();
            if(($is_manager || $is_system)){
                $result = $new_subtask->create_subtask($subtaskName,$subtaskDes,$subminTerm,$proTID,$proSTID,$mem_id);
                if($result)
                {
                    
                    echo "서브 태스크 생성 성공"; //이후 project_member tb도 같이 채워야지
                    exit("<script>alert('서브 태스크가 생성되었습니다.');
                    location.replace(`../task/task.php?id=$task_id`)</script>");
                    
                }
                else 
                { 
                    
                    exit("<script>alert('서브 태스크 생성에 실패하였습니다.');
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