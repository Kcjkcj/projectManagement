<?php
    require_once '../DBconfig/Database.php';
    class Delete_File{
        private $db;
        private $file_id;
        private $file_path;

        public function __construct(Database $db, $file_id, $file_path) {
            $this->db = $db;
            $this->file_id = $file_id;
            $this->file_path = $file_path;
        }

        public function delete_file()
        {
            // 파일이 존재하는지 확인하고 삭제
            if (file_exists($this->file_path)) {
                unlink($this->file_path);  // 서버에서 파일 삭제
            }

            $sql = "DELETE FROM file_uploads WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i',$this->file_id);
            if (!$stmt->execute()){
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                echo "<script>alert('파일 삭제에 실패했습니다.'); history.back();</script>";
            }
            else{
                echo "<script>alert('파일이 성공적으로 삭제되었습니다.'); history.back();</script>";
            }

        }
    }
    $file_id = $_POST['file_id'];
    $file_path = $_POST['file_path'];

    $DB = new Database();
    $delete_file = new Delete_File($DB,$file_id,$file_path);
    $delete_file->delete_file();
?>