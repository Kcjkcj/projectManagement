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
        //세션 정보, DB접속 변수
        $user_index = $_SESSION['user'];
        $project_id = $_SESSION['proID']; //세션정보에 프로젝트 인덱스 정보가 있음
        require_once '../DBconfig/Database.php';

        class Create_Task{
            private $db;
            public $project_id;
            public $task_id;
            public $userid;
            private $created_at;
            private $version;
            private $article_index;

            public function __construct(Database $db, $userid, $project_id) {
                $this->db = $db;
                $this->project_id = $project_id;
                $this->task_id = rand(1000000,9999999);
                $this->userid = $userid;
                $this->version = 1;
                date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
                $this->created_at = date("Y-m-d H:i:s",time());
                $this->article_index = rand(1,89999999)+10000000;
            }

            public function task_index_check($taskindex)
            {
                $sql = $sql = "SELECT * FROM task WHERE `id`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$taskindex);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_task_id = rand(1000000,9999999);
                    return $this->task_index_check($new_task_id);
                }
                $stmt->close();
                return $taskindex;
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

            public function create_task($taskName, $taskDes)
            {
                $this->task_id = $this->task_index_check($this->task_id);
                $sql = "INSERT INTO task(id, `version`, `project_id`, `name`, `description`,`created_at`)
                     VALUES(?,?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iiisss',$this->task_id,$this->version,$this->project_id,$taskName,$taskDes,$this->created_at);
                $result =  $stmt->execute(); //쿼리 성공 여부
                $stmt->close();
                return $result;
            }


            public function __destruct() {
                $this->db->close();
            }

        }
        //create_project에서 넘겨받는 변수들
        $taskName=$_POST["task_name"];
        $taskDes=$_POST["task_description"];
        //index 생성 변수

        if($taskName==null || $taskDes==null)
        {
            //exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
               // location.replace(`create_task.php`)</script>");
        }
            $DB = new Database();
            $new_task = new Create_Task($DB,$user_index,$project_id);
            $is_manager = $new_task->is_manager();
            $is_system = $new_task->is_system();
            if(($is_manager || $is_system)){
                $result = $new_task->create_task($taskName,$taskDes); //DB에 insert 성공여부
                if($result)
                {
                    echo "태스크 생성 성공"; //이후 project_member tb도 같이 채워야지
                    exit("<script>alert('태스크가 생성되었습니다.');
                    location.replace(`../project/project.php?id=$project_id`)</script>");

                }
                else 
                { 
                    //print_r($DB->get_error());
                    //$new_task->__destruct();
                    exit("<script>alert('태스크 생성에 실패하였습니다.');
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