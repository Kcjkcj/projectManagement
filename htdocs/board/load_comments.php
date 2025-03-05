<?php
    require_once '../DBconfig/Database.php';

    class load_comment{
        private $db;
        private $comment_index;
        private $created_at;
        //public $userid;
        public $project_id;

        public function __construct(Database $db, $project_id,$comment_index) {
            $this->db = $db;
            //$this->userid = $userid;
            $this->project_id = $project_id;
            $this->comment_index = $comment_index;
        }

        public function project_memberIndex(){
            $sql = "SELECT `created_member_index` WHERE project_id = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i",$this->project_id);
            $stmt->execute();
            return $result = $stmt->get_result()->fetch_assoc();
        }

        public function who_write($promem_index)
        {
            $sql = "SELECT u.username from user u 
                join project_member pm on u.`index`= pm.user_index where pm.`index`= ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i",$promem_index);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['username'];
        }
        
        public function get_comment()
        {
            $sql = "SELECT created_member_index, content, created_at FROM article WHERE project_id = ? AND comment_of_index = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $this->project_id, $this->comment_index);
            $stmt->execute();
            return $result = $stmt->get_result();
        }

        public function __destruct() {
            $this->db->close();
        }
    }

    if (isset($_POST['project_id'])) {
        $project_id = $_POST['project_id'];
        $comment_index = $_POST['article_id'];
        $DB = new Database();
        $comment = new load_comment($DB, $project_id,$comment_index); 
        $rows = $comment->get_comment();
        if ($rows->num_rows>0) {
            while ($row = $rows->fetch_assoc()) { //이렇게 while을 해야 무한루프에 안빠진다
                echo "<div>";
                $promem_index = $row['created_member_index'];
                $name = $comment->who_write($promem_index);
                echo "<strong>{$name}</strong> ({$row['created_at']}) <br>";
                echo nl2br($row['content']);
                echo "</div><hr>";
            }
        } else {
            echo "댓글이 없습니다.";
        }
    }
?>