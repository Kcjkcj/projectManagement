<!-- create project랑 CSS는 같게하고 update_project_check.php를 따로 만들어서 하는게 나을 듯-->
<!-- 프로젝트 생성 페이지-->
<html>
<?php
    header('Cache-Control: no cache'); //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    session_cache_limiter('private_no_expire');
    if(!session_id())
    {
        session_start();
    }
    require_once '../DBconfig/Database.php';
    $project_id = $_SESSION['proID'];

    function get_user_name(Database $DB){
        $sql = "SELECT `email`,`username` FROM user where is_admin=0";
                $stmt = $DB->prepare($sql);
        if(!$stmt->execute()){
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        return $result;
    }

    function get_member_name(Database $DB, $project_id)
    {
        $sql = "SELECT u.username, u.email, pm.index FROM user as u
        join project_member as pm
        on u.index = pm.user_index
        where pm.project_id = ?";
        $stmt = $DB->prepare($sql);
        $stmt->bind_param('i',$project_id);
        if(!$stmt->execute()){
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        return $result;
    }

    $DB = new Database();
    $user_infos = get_user_name($DB);
    $members = get_member_name($DB,$project_id);
    ?>
    <head>
        <title>프로젝트 수정하기</title>
        <meta charset="utf-8">

    </head>
    <body>
    <button onclick=history.back(-1)>이전화면으로</button>
    <h1>프로젝트 수정하기</h1>
    <br>
    <div class="update_project">
        <form action="../project/update_project_check.php" method="post" accept-charset="UTF-8">
            <h3>프로젝트 명</h3>
            <input type="text" name="project_name">
            <h3>프로젝트 설명</h3>
            <input type="text" name="project_description">
            <br>
            <h3>프로젝트 예상 시작일</h3>
            <input type="date" name="project_start_date" id="startDate" min="2020-01-01" max="2025-12-31">
            <h3>프로젝트 예상 종료일</h3>
            <input type="date" name="project_end_date" id="endDate" min="2020-01-01" max="2025-12-31">
            <br>
            <br>
            <h3>수정할 멤버</h3> <!-- 멤버가 바뀌는 경우는 어떻게 해야하지? 이전 멤버를 삭제하고 새로운 멤버를 넣는다? 그럼 이전 멤버의 정보는 어디서? 
            단순히 추가하는 건 돼 문제는 삭제다-->
            <div id="inputContainer">
                <select name='inputs[]' size="1">
                <?php 
                if($members){
                while($member = $members->fetch_assoc())
                    {
                        echo "수정할 멤버 ";
                        echo $member['username'];
                        echo " -> ";
                        if($user_infos){
                        while($user_info = $user_infos->fetch_assoc())
                            echo "<option value='{$user_info['email']}'>{$user_info['username']}</option>";
                        }
                        echo "<br>";
                    }
                }
                ?>
                </select>
            </div>
            <button type="button" onclick="addInput()">+</button>
            <br>
            <br>
            <input type="submit" value="수정하기">
        </form>
    </div>
        <script>
         function addInput() {
            const container = document.getElementById('inputContainer');
            const existingSelect = container.querySelector('select');
            if (!existingSelect) return;

            // 새로운 입력란 추가
            const newSelect = existingSelect.cloneNode(true);
            container.appendChild(newSelect);
        }
    </script>
     </body>

</html>