<!-- 메인페이지 -->
<?php
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    if(!session_id())
    {
        session_start();
    }

    $is_logged_in = isset($_SESSION['user']);
    if(isset($_SESSION['user'])) 
        $user_index = $_SESSION['user'];
    else
        $user_index =null;
    /*
    echo "Current session save path: " . session_save_path();
    if (empty(session_save_path())) {
        echo "\nDefault temporary directory: " . sys_get_temp_dir();
    }
        //print_r($_SESSION['user']);
    */
    require_once '../DBconfig/Database.php';
    $DB = new Database();
    function is_system(Database $DB, $user_id)
    {
        $sql = "SELECT 1 from user where `index`=? and `is_admin`=1"; //시스템 관리자인지 확인
        $stmt = $DB->prepare($sql);
        $stmt->bind_param('i',$user_id);
        $stmt->execute();
        $result = $stmt->get_result()->num_rows;
        $stmt->close();
        return $result>0;
    }

    $is_system = is_system($DB, $user_index);
?>

<html>
    <head>
        <title>프로젝트 관리 시스템</title>
        <meta charset="utf-8">

    </head>
    <body>
        <div class='container'>
        <nav>
        <h1>프로젝트 관리 시스템</h1>
        <?php if ($is_logged_in) : ?>
                <button onclick="location.href='../user/logout.php'">로그아웃</button>
                <br>
                <button onclick="location.href='../board/dash_board.php'">대시보드로</button>
                <br>
                <?php if (!$is_system) : ?>
                <button onclick="location.href='../statistics/get_user_statistics.php'">나의 통계 페이지로</button>
                <br>
                <?php endif; ?>
                <button onclick="location.href='../statistics/get_project_statistics.php'">프로젝트 통계 페이지로</button>
                 <!-- 로그인이 되어있으면 버튼표시 -->
        <?php endif; ?>
            <?php if (!$is_logged_in) : ?>
                <div class='login'>
                <h1>로그인</h1>
                    <h3>이메일</h3>
                <form action="../user/login_check.php" method="post">
                    <input type="email" name="email">
                    <h3>비밀번호</h3>
                    <input type="password" name="password"><br>
                    <input type="submit" value="로그인">
                </form>
                </div>
                <button onclick="location.href='../user/join.php'">회원가입</button>
            <?php endif; ?>
        </nav>
        </div>
    </body>
</html>