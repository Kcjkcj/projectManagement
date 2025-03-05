<?php 
    if(!session_id())
    {
        session_start();
    }
    $project_id = $_SESSION['proID'];
    $log_user_index = $_SESSION['user'];
    require_once '../DBconfig/Database.php';
    class delete_project{
        private $db;
        public $project_id;
        public $userid;
        private $article_index;
        private $created_at;

        public function __construct(Database $db, $project_id, $userid) {
            $this->db = $db;
            $this->project_id = $project_id;
            $this->userid = $userid;
            date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
            $this->created_at = date("Y-m-d H:i:s",time());
            $this->article_index = rand(1,89999999)+10000000;
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

        public function getProName() {
            $sql = "SELECT `name` from project where `id`= ? order by `version` DESC LIMIT 1"; //select 1 -> 레코드 존재 여부확인
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i",$this->project_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc()['name'];
            return $result;
        }

        public function delete_project()
        {
            $sql = "DELETE from project where `id`=?"; //프로젝트 매니저인지 확인
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i',$this->project_id);
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error); // SQL 실행 실패 시 로그 남기기
                return false;
            }
            $stmt->close();
            return true;
        }
        public function __destruct() {
            $this->db->close();
        }
    }
    $DB = new Database();
    $delete_Project = new delete_project($DB,$project_id,$log_user_index);
    $is_manager = $delete_Project->is_manager();
    $is_system = $delete_Project->is_system();
    if(!($is_manager || $is_system))
    {
        exit("<script>alert('관리자가 아닙니다!');
        location.replace(history.back(-1))</script>");
    }
    //여기까지 오면 일단 프로젝트 관리자는 맞음
    $result = $delete_Project->delete_project();
    if(!$result)
    {
        echo "프로젝트 삭제에 실패";
        
    }
    else
    {
        exit("<script>alert('삭제가 완료되었습니다.');
        location.replace('../board/dash_board.php')</script>");
    }

?>