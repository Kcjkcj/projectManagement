<?php
    session_start();
    require_once '../DBconfig/Database.php';

    error_log("submit_comment.php 시작"); // 시작 로그
    class submit_comment{
        private $db;
        private $article_index;
        private $created_at;
        public $userid;
        public $project_id;
        public $task_id;
        
        public function __construct(Database $db, $userid, $project_id,$task_id) {
            $this->db = $db;
            $this->userid = $userid;
            $this->article_index = rand(1,89999999)+10000000;
            date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
            $this->created_at = date("Y-m-d H:i:s",time());
            $this->project_id = $project_id;
            $this->task_id = $task_id;
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

        public function project_memberIndex(){
            $sql = "SELECT `index` from project_member where user_index = ? and project_id = ?"; //해당 프로젝트의 멤버의 데려오면 되는거 아닌가?
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii",$this->userid, $this->project_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['index'] ?? null;
        }

        public function save_article($title, $content, $is_notice, $comment_index)
        {
            $article_index = $this->article_index_check($this->article_index);
            $member_index = $this->project_memberIndex();
            {
                $sql = "INSERT INTO article (`index`, project_id, created_member_index, `title`, `content`, comment_of_index, `created_at`) 
                    VALUES (?,?,?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("iiissis",$article_index, $this->project_id, $member_index, $title, $content, $comment_index, $this->created_at);
                return $stmt->execute();
            }  

        }

        public function __destruct() {
            $this->db->close();
        }
    }

    if (isset($_POST['content'])) { //작성된 댓글의 유무 확인
        //echo "댓글 정보는 post 됨 ";
        $user_index = $_SESSION['user'];
        $project_id = $_SESSION['proID'];
        $article_id = $_POST['article_id'];
        if(isset($_POST['task_id']))
            $task_id = (int)$_POST['task_id'];
        else $task_id =0;
        $DB = new Database();
        $comment = new submit_comment($DB,$user_index,$project_id,$task_id);
        //$member_index = $comment->projectMemberIndex();
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
        $comment_index = $_POST['article_id'] ? (int)$_POST['article_id'] : null;
        $is_notice = isset($_POST['is_notice']) ? 1 : 0;
        //$article_index = article_index_check($article_index, $conn);
        //echo "sql 전까지는 문제없음";
        if ($comment->save_article($title, $content, $is_notice, $comment_index)) {
            echo "success";
        } else {
            echo "error";
        }
        } else {
            error_log("content 값이 설정되지 않음"); // 오류 로그
            echo "error";
    }
?>