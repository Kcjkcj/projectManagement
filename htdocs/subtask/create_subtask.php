<!-- 태스크 생성 페이지 담당 멤버를 어떻게 할지는 토의해보는 걸로 우선은 미리 상의하고 입력하는 형태로 해보자-->
<html>
    <?php
    header('Cache-Control: no cache'); //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    session_cache_limiter('private_no_expire');
    if(!session_id())
    {
        session_start();
    }

    isset($_SESSION['user']) ? $user_id = $_SESSION['user'] : null;
    isset($_SESSION['proID']) ? $project_id = $_SESSION['proID'] : null;
    isset($_SESSION['taskID']) ? $task_id = $_SESSION['taskID'] : null;


    require_once '../DBconfig/Database.php';
    class GetDetail {
        private $db;
        private $userid;
        private $project_id;
        private $task_id;
        
        public function __construct(Database $db, $userid, $project_id, $task_id) {
            $this->db = $db;
            $this->userid;
            $this->project_id = $project_id;
            $this->task_id = $task_id;
        }

        function get_ProtaskName() //가장 높은 버전으로
        {
            $sql = "SELECT id, `name`
            FROM task t
            WHERE t.project_id = ? and t.id != ?
            AND t.version = (
                SELECT MAX(version)
                FROM task
                WHERE project_id = ? AND id = t.id
            )
                    ORDER BY t.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iii", $this->project_id,$this->task_id,$this->project_id);
            $stmt->execute();
            return $stmt->get_result();
        }
        /*
        
        function get_TaskID()
        {
            $sql = "SELECT id
                    FROM task t
                    WHERE t.project_id = ?
                    AND t.version = (
                        SELECT MAX(version)
                        FROM task
                        WHERE project_id = ? AND id = t.id
                    )
                    ORDER BY t.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $this->project_id,$this->project_id);
            $stmt->execute();
            return $stmt->get_result();
        }
            */

        function get_ProsubtaskName() //해당 프로젝트의 모든 태스크에 들어있는 서브 태스크의 이름이 나와줘야 함
        {
            $sql = "SELECT st.id, st.name
                    FROM task AS t
                    JOIN subtask AS st ON t.id = st.task_id
                    WHERE t.project_id = ?
                    AND t.version = (
                        SELECT MAX(version)
                        FROM task
                        WHERE project_id = ? AND id = t.id
                    )
                    AND st.version = (
                        SELECT MAX(version)
                        FROM subtask
                        WHERE task_id = st.task_id AND id = st.id
                    )
                    GROUP BY st.id, st.name
                    ORDER BY MAX(st.created_at) DESC";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->get_error());
                return false;
            }

            $stmt->bind_param("ii",$this->project_id,$this->project_id);
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }

            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
            
        function get_memberName() // 프로젝트에 참가하는 사람들의 멤버 인덱스랑 유저 이름 가져옴
        {
            $sql = "SELECT u.username, pm.index
            from user as u 
            inner join project_member as pm 
            on pm.user_index = u.index
            where pm.project_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i",$this->project_id);
            if (!$stmt->execute()){
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
            }
            $result = $stmt->get_result();
            return $result;
            
        }

        public function __destruct() {
            $this->db->close();
        }
    }

    $DB = new Database();
    $detail = new GetDetail($DB,$user_id, $project_id, $task_id);
    $taskNames = $detail->get_ProtaskName();
    $subtaskNames = $detail->get_ProsubtaskName();
    $memberNames = $detail->get_memberName();
    ?>
    <head>
        <title>세부 태스크 생성하기</title>
        <meta charset="utf-8">
        <button onclick="location.href='../board/main.php'">메인화면으로</button>
        <button onclick="history.back()">해당 태스크로</button>

    </head>
    <body>
    <h1>세부 태스크 생성</h1>
    <br>
    <div class ="create_subtask">
        <form action="../subtask/create_subtask_check.php" method="post">
            <h3>세부 태스크 명</h3>
            <input type="text" name="subtask_name">
            <h3>세부 태스크 설명</h3>
            <input type="text" name="subtask_description">
            <h3>최소 소요일</h3>
            <input type="number" name="min_term"><span class="unit">일</span>
            <h3>선행 태스크 ID</h3>
            <select name="pro_taskID" size="1">
            <option value="" selected>선택 안함</option> <!-- null 값 -->
                <?php 
                if($taskNames){
                while($taskName = $taskNames->fetch_assoc())
                    echo "<option value='{$taskName['id']}'>{$taskName['name']}</option>";
                }
                ?>
            </select>
            <h3>선행 세부 태스크 ID</h3>
            <select name="pro_subtaskID" size="1">
            <option value="" selected>선택 안함</option> <!-- null 값 -->
                <?php 
                if($subtaskNames){
                while($subtaskName = $subtaskNames->fetch_assoc())
                    echo "<option value='{$subtaskName['id']}'>{$subtaskName['name']}</option>";
                }
                ?>
            </select>
            <h3>담당 멤버 이름</h3>
            <select name="memID" size="1">
                <?php 
                while($memberName = $memberNames->fetch_assoc())
                    echo "<option value='{$memberName['index']}'>{$memberName['username']}</option>";
                ?>
            </select>
            <br>
            <input type="submit" value="생성하기">
        </form>
    </div>
     </body>
</html>