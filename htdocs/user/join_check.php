<!-- 회원가입 페이지에서 입력한 정보를 DB에 넣고, 무결성 검사 -->
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <?php
        require_once '../DBconfig/Database.php';
        class Join{
            private $db;
            private $email;
            private $password;
            private $username;
            private $role;
            private $user_index;

            public function __construct(Database $db, $email, $password, $username, $role)
            {
                $this->db = $db;
                $this->email = $email;
                $this->password = $password; 
                $this->username = $username;
                $this->role = $role;
                $this->user_index = rand(1000000,9999999);
            }

            public function check_info()
            {
                $sql = "SELECT 1 FROM user WHERE `email`=? ";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('s',$this->email);
                if(!$stmt->execute()){
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    return false;
                }
                $result = $stmt->get_result();
                if ($result->num_rows > 0)
                {
                    $stmt->close();
                    echo "email 중복";
                    exit("<script>alert('중복된 이메일입니다.');
                    location.replace(`../user/join.php`)</script>");
                }

            }

            public function create_user()
            {
                $this->user_index = $this->user_index_check($this->user_index);
                $this->check_info();
                if($this->role ===1){
                    $this->is_already_admin(); // 시스템 관리자가 이미 존재하는지 확인
                }
                $sql = "INSERT INTO user(`index`,`email`, `password`, `username` , `is_admin`)
                    VALUES(?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('isssi',$this->user_index,$this->email,$this->password,$this->username,$this->role);
                if(!$stmt->execute()) //쿼리 실패
                {
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    /*exit("<script>alert('회원가입에 실패하였습니다.');
                    location.replace(`../user/join.php`)</script>");*/
                }
                $stmt->close();
                exit("<script>alert('회원가입이 완료되었습니다.');
                location.replace(`../board/main.php`)</script>");

            }

            public function user_index_check($userindex)
            {
                $sql = "SELECT 1 FROM user WHERE `index`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$userindex);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_user_id = rand(1000000,9999999);
                    return $this->user_index_check($new_user_id);
                }
                $stmt->close();
                return $userindex;
            }

            public function is_already_admin()
            {
                $sql = $sql = "SELECT 1 FROM user WHERE `is_admin`= ? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$this->role);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    echo "이미 시스템 매니저가 존재합니다.";
                    $stmt->close();
                    exit("<script>alert('시스템 매니저가 이미 존재합니다.');
                    location.replace(`../user/join.php`)</script>");
                }
                $stmt->close();
            }

        }
            $email=$_POST["email"];
            $password=$_POST["password"];
            $username = $_POST["username"];
            $role=$_POST["role"];
            if($email==null || $password==null || $role==null)
            {exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
                    location.replace(`../user/join.php`)</script>");
            }

            if($role==="sysmanager")
                $is_admin = 1;
            else
                $is_admin = 0;            
            $DB = new Database();
            $join_info = new Join($DB,$email,$password,$username, $is_admin);
            $join_info->create_user();
            
            ?>
        </body>

</html>