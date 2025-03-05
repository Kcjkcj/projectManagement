<!-- 로그인 페이지 -->
<html>
    <head>
        <title>로그인</title>
        <meta charset="utf-8">
    </head>
    <body>
        <button onclick="location.href='../board/main.php'">메인으로</button> 
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
    </body>

</html>