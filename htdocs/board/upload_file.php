<?php
    if(!session_id())
     session_start();
    $article_id = (int)$_POST['article_id']; // 세션에서 project_id 가져오기
    $project_id = (int)$_POST['project_id'];
    $task_id = (int)$_POST['task_id'];
    $subtask_id = (int)$_POST['subtask_id'];

    //$upload_dir = 'uploads/'; // 업로드할 파일을 저장할 디렉토리
    require_once '../DBconfig/Database.php';
    class Upload_FIle{
        private $db;
        private $file_index;
        private $article_id;
        private $upload_dir;
        private $created_at;
        private $project_id;
        private $task_id;
        private $subtask_id;

        public function __construct(Database $db,$article_id, $project_id, $task_id, $subtask_id)
        {
            $this->db = $db;
            $this->file_index = rand(1,8999999)+1000000;
            $this->article_id = $article_id;
            $this->project_id = $project_id;
            $this->task_id = $task_id;
            $this->subtask_id = $subtask_id;
            $this->upload_dir = 'uploads/';
            date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
            $this->created_at = date("Y-m-d H:i:s",time());
        }

        public function file_index_check($file_index)
        {
            $sql = "SELECT `id` FROM file_uploads WHERE `id`=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i',$file_index);
            if (!$stmt->execute()){
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
            }
            $result = $stmt->get_result();
            if($result->num_rows>0)
            {
                $new_file_index =  rand(1000000,9999999);
                return $this->file_index_check($new_file_index);
            }
            $stmt->close();
            return $file_index;
        }


        public function Save_file()
        {
            if (!is_dir($this->upload_dir)) {
                mkdir($this->upload_dir, 0777, true); // 디렉토리가 없으면 생성
            }

            if ($_FILES['uploaded_file']['error'] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['uploaded_file']['tmp_name'];
                $filename = basename($_FILES['uploaded_file']['name']);
                $target_file = $this->upload_dir . $filename;
                
                // 파일을 서버에 저장
                if (move_uploaded_file($tmp_name, $target_file)) {
                    // DB에 파일 경로 저장
                    $this->file_index = $this->file_index_check($this->file_index);
                    $sql = "INSERT INTO file_uploads (`id`,article_index, file_path, created_at, project_id, task_id, subtask_id) VALUES (?,?,?,?,?,?,?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('iissiii',$this->file_index ,$this->article_id, $target_file, $this->created_at, 
                            $this->project_id, $this->task_id, $this->subtask_id);
                    if(!$stmt->execute()){
                    exit("<script>location.replace(history.back(-1))</script>");
                    } else {
                        
                       $this->db->close();
                       exit("<script>location.replace(history.back(-1))</script>");
                    }
                    //$this->db->close();
                } else {
                    echo "파일 업로드 실패!";
                }
            } else {
                echo "파일 업로드 에러!";
            }
        }
    }

    $DB = new Database();
    $upload_file = new Upload_FIle($DB,$article_id,$project_id,$task_id,$subtask_id);
    $upload_file->Save_file();
?>