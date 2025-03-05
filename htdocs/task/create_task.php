<!-- 태스크 생성 페이지-->
<html>
<?php
    header('Cache-Control: no cache'); //no cache
    session_cache_limiter('private_no_expire'); // works
    if(!session_id())
    {
        session_start();
    }
    ?>
    <head>
        
        <title>태스크 생성하기</title>
        <meta charset="utf-8">
        <button onclick="location.href='../board/main.php'">메인화면으로</button>
        <button onclick="history.back()">해당 프로젝트로</button>
    </head>
    <body>
    <h1>태스크 생성</h1>
    <br>
    <div class=create_task>
        <form action="../task/create_task_check.php" method="post">
            <h3>태스크 명</h3>
            <input type="text" name="task_name">
            <h3>태스크 설명</h3>
            <input type="text" name="task_description">
            <br>
            <input type="submit" value="생성하기">
        </form>
    </div>
     </body>

</html>