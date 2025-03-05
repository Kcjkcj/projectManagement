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

    $DB = new Database();
    $user_infos = get_user_name($DB)

    ?>
    <head>
        <title>프로젝트 생성하기</title>
        <meta charset="utf-8">
        <style>
            /* 전체 페이지 스타일 */
            body {
                font-family: 'Noto Sans KR', sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 20px;
            }

            /* 메인 컨테이너 스타일 */
            .main-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 80vh;
            }

            /* 프로젝트 생성 섹션 스타일 */
            .create-project-section {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                width: 400px; /* 너비 조정 */
            }

            .create-project-section h1 {
                color: #333;
                font-size: 24px;
                margin-bottom: 20px;
                text-align: center;
            }

            .create-project-section h3 {
                color: #555;
                font-size: 14px;
                margin: 10px 0 5px 0;
            }

            /* 입력 필드 스타일 */
            .create-project-section input[type="text"],
            .create-project-section input[type="date"] {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }

            /* 버튼 스타일 */
            .btn {
                width: 100%;
                padding: 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: background-color 0.2s;
                box-sizing: border-box;
                text-align: center;
                display: block;
                text-decoration: none;
            }

            .btn-create {
                background-color: #28a745;
                color: white;
                margin-top: 10px;
            }

            .btn-create:hover {
                background-color: #218838;
            }

            .btn-dashboard {
                background-color: #6c757d;
                color: white;
                margin-bottom: 10px;
            }

            .btn-dashboard:hover {
                background-color: #5a6268;
            }
        </style>
    </head>
    <body>
    <div class="main-container">
        <div class="create-project-section">
            <h1>프로젝트 정보 입력</h1>
            <form action="../project/create_project_check.php" method="post">
                <h3>프로젝트 명</h3>
                <input type="text" name="project_name" required>
                <h3>프로젝트 설명</h3>
                <input type="text" name="project_description" required>
                <h3>프로젝트 예상 시작일</h3>
                <input type="date" name="project_start_date" id="startDate" min="2020-01-01" max="2025-12-31" required>
                <h3>프로젝트 예상 종료일</h3>
                <input type="date" name="project_end_date" id="endDate" min="2020-01-01" max="2025-12-31" required>
                <br>
                <h3>추가할 멤버</h3>
                <div id="inputContainer">
                    <select name='inputs[]' size="1">
                    <?php 
                    if($user_infos){
                    while($user_info = $user_infos->fetch_assoc())
                        echo "<option value='{$user_info['email']}'>{$user_info['username']}</option>";
                    }
                    ?>
                    </select>
                </div>
                <button type="button" onclick="addInput()">+</button>
                <br>
                <input type="submit" class="btn btn-create" value="생성하기">
            </form>
            <button class="btn btn-dashboard" onclick="location.href='../board/dash_board.php'">대시보드로</button>
        </div>
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