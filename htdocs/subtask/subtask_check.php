<?php

    require_once '../DBconfig/Database.php';
    if(!session_id())
        session_start();
    if(isset($_SESSION['proID'])) $project_id = $_SESSION['proID'];
    if(isset($_SESSION['user'])) $user_id = $_SESSION['user'];
    if(isset($_POST['subtask_id'])) $subtask_id = $_POST['subtask_id'];
    
    if(isset($_POST['task_id'])) $task_id = $_POST['task_id'];
    else $task_id = $_SESSION['taskID'];
    class Subtask_Check{
        private $db;
        private $userid;
        private $project_id;
        private $task_id;
        private $subtask_id;
        private $real_end_date;
        private $real_start_date;
        private $article_index;
        private $created_at;

        public function __construct(Database $db, $user_id,$project_id, $task_id, $subtask_id)
        {
            $this->db = $db;
            $this->userid=$user_id;
            $this->project_id=$project_id;
            $this->task_id = $task_id;
            $this->subtask_id = $subtask_id;
            date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
            $this->real_end_date = date("Y-m-d H:i:s",time());
            $this->real_start_date = date("Y-m-d H:i:s",time());
            $this->created_at = date("Y-m-d H:i:s",time());
            $this->article_index = rand(1,89999999)+10000000;
        }

        public function success()
        { 
            $sql = "UPDATE subtask set actual_end_date = ?
                where `id` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si',$this->real_end_date,$this->subtask_id);
            if(!$stmt->execute())
            {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            $stmt->close();

            // 연관된 서브태스크 및 태스크 업데이트
            $this->updateRelatedSubtasks();
            $this->updateRelatedTasks();
            return true;
        }

        public function start() //선행 태스크 or 서브 태스크가 완료되지 않은 경우 start자체가 안되게 막아야함, request, success도 마찬가지
        {
            $sql = "UPDATE subtask set actual_start_date = ?
                where `id` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si',$this->real_start_date,$this->subtask_id);
            if(!$stmt->execute())
            {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            $stmt->close();
            return true;
        }

        public function get_PreWork() //최신 버전이 가지고있는 선행 태스크의 정보가 필요함
        {
            $sql =  "SELECT preceding_task_id, preceding_subtask_id from subtask where `id` = ? order by `version` DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $this->subtask_id);
            if (!$stmt->execute()){
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }


        public function getSubtaskDetails() {
            $sql = "SELECT `name`,`description` from subtask where `id` = ? order by `version` DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $this->subtask_id);
            if (!$stmt->execute()){
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result;
        }


        public function project_memberIndex(){
            $sql = "SELECT `index` from project_member where user_index = ? and project_id = ?"; //해당 프로젝트의 멤버의 데려오면 되는거 아닌가?
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii",$this->userid, $this->project_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['index'] ?? null;
        }

        private function updateRelatedSubtasks()
        {
            $sql = "UPDATE subtask SET actual_start_date = ? 
                    WHERE preceding_subtask_id = ? AND actual_start_date IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $this->real_end_date, $this->subtask_id);
            $stmt->execute();
            $stmt->close();
        }

        private function updateRelatedTasks()
        {
            // 현재 서브태스크가 속한 태스크의 모든 서브태스크가 완료되었는지 확인
            $sql = "SELECT t.id FROM task t
                    WHERE t.id = (SELECT task_id FROM subtask WHERE id = ?)
                    AND NOT EXISTS (
                        SELECT 1 FROM subtask s
                        WHERE s.task_id = t.id AND s.actual_end_date IS NULL
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $this->subtask_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                $completed_task_id = $result->fetch_assoc()['id'];
                
                // 완료된 태스크를 선행 태스크로 가진 서브태스크들의 시작일 업데이트
                $sql = "UPDATE subtask SET actual_start_date = ? 
                        WHERE preceding_task_id = ? AND actual_start_date IS NULL";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('si', $this->real_end_date, $completed_task_id);
                $stmt->execute();
            }
            
            $stmt->close();
        }

    }


    $DB = new Database();
    $date_check = new Subtask_Check($DB,$user_id,$project_id,$task_id,$subtask_id); 

    if(isset($_POST['Request']))
    {
        $sql = "CALL `project_management`.`request_subtask_completion`(?)";
        $stmt = $DB->prepare($sql);
        $stmt->bind_param('i',$subtask_id);
        if(!$stmt->execute())
        {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            exit;
        }
        $stmt->close();
        echo "<script>alert('완료 승인 요청이 완료되었습니다.'); history.back();</script>";
    }
    
    if(isset($_POST['complete']))
    {
        $result = $date_check->success();
        if(!$result)
           echo "<script>alert('완료 체크 과정에 문제가 발생하였습니다.'); history.back();</script>";
    }


?>