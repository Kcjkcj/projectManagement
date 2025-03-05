<!-- 로그인 페이지에서 입력한 정보와 DB의 정보를 비교, 무결성 검사-->
<?php         
    if(!session_id()){
        session_start();
    }
?>
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <?php
        require_once '../DBconfig/Database.php';
        class Login{
            private $db;
            private $email;
            private $password;

            public function __construct(Database $db, $email, $password)
            {
                $this->db = $db;
                $this->email = $email;
                $this->password = $password; 
            }

            public function check_info()
            {
                $sql = "SELECT `password`, `index` FROM user WHERE `email`=? ";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('s',$this->email);
                if(!$stmt->execute()){
                    //error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    return false;
                }
                $result = $stmt->get_result();
                if ($result->num_rows == 0)
                {
                    $stmt->close();
                    exit("<script>alert('등록된 email이 아닙니다.');
                    location.replace(`../board/main.php`)</script>");
                }
                $row = $result->fetch_assoc();
                $password = $row['password'];
                $index = $row['index'];
            
                if($this->password != $password)
                {
                    $stmt->close();
                    exit("<script>alert('등록된 비밀번호가 아닙니다.');
                    location.replace(`../board/main.php`)</script>");
                }
                $_SESSION['user'] = $index; //세션 변수에 유저 index값 부여
                $stmt->close();
                exit("<script>alert('로그인이 완료되었습니다.');
                location.replace(`../board/main.php`)</script>");
            }
        }
        $email=$_POST["email"];
        $password=$_POST["password"];
        if($email==null || $password==null)
        {
            exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
                location.replace(`../board/main.php`)</script>");
        }
        $DB = new Database();
        $login = new Login($DB,$email,$password);
        $login->check_info();
        ?>
    </body>

</html>