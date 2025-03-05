<?php
   if(!session_id())
   {
       session_start(); //세션파괴에도 세션 스타트 해야함
   }

    // 세션 데이터 파괴
    $_SESSION = array();

    // 세션 쿠키가 있으면 삭제
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, 
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]
        );
    }

    session_destroy();
    exit("<script>alert('로그아웃이 완료되었습니다.');
    location.replace(`../board/main.php`)</script>");
?>