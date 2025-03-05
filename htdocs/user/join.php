<?php
    $page_title = "회원가입";
    require_once '../includes/header.php';
?>

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

    /* 회원가입 섹션 스타일 */
    .join-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        width: 300px;
        margin: 0 auto;
    }

    .join-section h1 {
        color: #333;
        font-size: 24px;
        margin-bottom: 20px;
        text-align: center;
    }

    .join-section h3 {
        color: #555;
        font-size: 14px;
        margin: 10px 0 5px 0;
    }

    /* 입력 필드 스타일 */
    .join-section input[type="email"],
    .join-section input[type="password"],
    .join-section input[type="text"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    /* 라디오 버튼 그룹 스타일 */
    .radio-group {
        margin: 15px 0;
        display: flex;
        gap: 15px;
    }

    .radio-group label {
        display: flex;
        align-items: center;
        color: #555;
        font-size: 14px;
    }

    .radio-group input[type="radio"] {
        margin-right: 5px;
    }

    /* 버튼 컨테이너 스타일 추가 */
    .button-container {
        width: 100%;
        margin-top: 15px;
    }

    /* 버튼 스타일 수정 */
    .btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: background-color 0.2s;
        box-sizing: border-box; /* 패딩을 너비에 포함 */
        text-align: center;
        display: block; /* 인라인 요소를 블록 요소로 변경 */
        text-decoration: none;
    }

    .btn-join {
        background-color: #28a745;
        color: white;
        margin-bottom: 10px;
    }

    .btn-join:hover {
        background-color: #218838;
    }

    .btn-back {
        background-color: #6c757d;
        color: white;
    }

    .btn-back:hover {
        background-color: #5a6268;
    }
</style>

<div class="main-container">
    <div class="join-section">
        <h1>회원가입</h1>
        <form action="../user/join_check.php" method="post">
            <h3>이메일</h3>
            <input type="email" name="email" placeholder="이메일을 입력하세요" required>
            
            <h3>비밀번호</h3>
            <input type="password" name="password" placeholder="비밀번호를 입력하세요" required>
            
            <h3>이름</h3>
            <input type="text" name="username" placeholder="이름을 입력하세요" required>
            
            <h3>계정 유형</h3>
            <div class="radio-group">
                <label>
                    <input type="radio" name="role" value="sysmanager">
                    시스템 매니저
                </label>
                <label>
                    <input type="radio" name="role" value="user" checked>
                    일반 사용자
                </label>
            </div>

            <div class="button-container">
                <button type="submit" class="btn btn-join">회원가입</button>
                <a href="../board/main.php" class="btn btn-back">메인으로 돌아가기</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>