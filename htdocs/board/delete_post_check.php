<?php
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    header("Pragma: no-cache");
    header('Expires: 0');
    require_once '../DBconfig/Database.php';
    $article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    class delete_Post{
        private $db;
        private $article_id;

        public function __construct(Database $db, $article_id)
        {
            $this->db = $db;
            $this->article_id = $article_id;
        }

        public function delete_post()
        {
            $sql = "DELETE from article where `index` =?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i',$this->article_id);
            if(!$stmt->execute())
            {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            $stmt->close();
            return true;
        }

        public function __destruct()
        {
            $this->db->close();
        }
    }

    $DB = new Database();
    $delete_post = new delete_Post($DB,$article_id);
    echo  "<script>alert('$article_id');</script>";
    $delete = $delete_post->delete_post();
    if($delete)
    {
        exit("<script>alert('삭제가 완료되었습니다.');
        location.replace('../board/board.php?task_id=$task_id')</script>");
    }
    else
    {
        exit("<script>alert('게시글 삭제에 실패하였습니다.');
        location.replace(history.back(-1))</script>");
    }
?>