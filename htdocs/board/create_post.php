<!-- 태스크 생성 페이지-->
<html>
<?php
    header('Cache-Control: no cache'); //no cache
    session_cache_limiter('private_no_expire'); // works
    if(!session_id())
    {
        session_start();
    }
    

    if(isset($_GET['project_id']))
        $project_id = (int)$_GET['project_id'];
     else $project_id =0;
    //echo " $project_id";
    ?>
    <head>
        
        <title>게시글 생성하기</title>
        <meta charset="utf-8">
        <button onclick="history.back()">이전화면</button>
    </head>
    <body>
    <h1>게시글 쓰기</h1>
    <br>
    <div class=create_post>
        <form action="../board/create_post_check.php" method="post">
            <h3>제목</h3>
            <input type="text" name="title">
            <h3>글 내용</h3>
            <textarea name="content"></textarea>
            <br>
            <input type="submit" value="생성하기">
        </form>
    </div>
     </body>

</html>