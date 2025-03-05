<!-- 프로젝트 생성 페이지에서 입력된 값을 DB에 넣고, 무결성 검사. 
 이 페이지는 메인페이지에서 넘어오는데 로그인을 할때 세션 변수에 저장된 유저 index를 통해서 허가된 접근인지를 파악함 -->
<?php
    header('Cache-Control: no cache'); //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    session_cache_limiter('private_no_expire');
    if(!session_id())
    {
        session_start();
    }
?>
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <?php
        //세션 정보, DB접속 변수
        if(!$_SESSION['user'])
        {
            exit("<script>alert(`로그인을 하지 않았습니다.\n프로젝트 생성에 실패하였습니다.`);
                location.replace(`../board/main.php`)</script>");
        }
        else
        {
            $user_index = $_SESSION['user'];
        }

        require_once '../DBconfig/Database.php';
        $DB = new Database();

        class create_project{
            private $db;
            public $project_id;
            public $userid;
            private $created_at;
            private $promem_index;
            private $version;
            private $article_index;

            public function __construct(Database $db, $userid) {
                $this->db = $db;
                $this->project_id = rand(10000,99999);
                $this->promem_index = rand(1000000,9999999);
                $this->userid = $userid;
                $this->version = 1;
                date_default_timezone_set("Asia/Seoul");  // 한국 시간대 설정
                $this->created_at = date("Y-m-d H:i:s",time());
                $this->article_index = rand(1,89999999)+10000000;
            }

            public function proindex_check($proindex)
            {
                $sql = "SELECT 1 FROM project WHERE `id`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$proindex);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_project_id = rand(10000,99999);
                    return $this->proindex_check($new_project_id);
                }
                $stmt->close();
                return $proindex;
            }

            public function promem_index_check($promem_index)
            {
                $sql = "SELECT 1 FROM project_member WHERE `index`=? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i',$promem_index);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $new_promem_id =  rand(1000000,9999999);
                    return $this->promem_index_check($new_promem_id);
                }
                $stmt->close();
                return $promem_index;
            }


            public function create_project($proName,$proDes,$EstiStart_date,$EstiEnd_date)
            {
                $this->project_id = $this->proindex_check($this->project_id);
                $sql = "INSERT INTO project(id, `version`, `name`, `description`,`start_date`,`end_date`,`created_at`)
                    VALUES(?,?,?,?,?,?,?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iisssss',$this->project_id,$this->version,$proName,$proDes,$EstiStart_date,$EstiEnd_date,$this->created_at);
                $result =  $stmt->execute(); //쿼리 성공 여부
                $stmt->close();
                return $result;
            }

            public function create_manager() //create_project에서 만들어진 project index가 필요함
            {
                $is_manager = 1;
                $this->promem_index = $this->promem_index_check($this->promem_index);
                $sql = "INSERT into project_member (`index`, `project_id`, `user_index`, `is_manager`, `joined_at`)
                    VALUES(?,?,?,?,?)"; //프로젝트를 생성한 사람은 관리자
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iiiis',$this->promem_index,$this->project_id,$this->userid,$is_manager,$this->created_at);
                $result = $stmt->execute(); //쿼리 성공 여부
                $stmt->close();
                return $result;
            }

            public function create_member($email)
            {
                $is_manager = 0;
                $sql = "SELECT `index` from user where `email`= ? LIMIT 1"; //해당 이름을 가진 유저의 index
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("s",$email);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows>0)
                {
                    $row = $result->fetch_assoc();
                    $to_add_user_index = $row['index'];
                    $this->promem_index = $this->promem_index_check($this->promem_index);
                    $sql = "INSERT INTO project_member (`index`, `project_id`, `user_index`, `is_manager`, `joined_at`) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('iiiis',$this->promem_index,$this->project_id,$to_add_user_index,$is_manager,$this->created_at);
                    $result = $stmt->execute(); //쿼리 성공 여부
                }
                else
                {
                    $sql = "DELETE from project where `id`=?"; //이상한 멤버를 넣어서 멤버 갱신이 안되었으니 바로 삭제
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('i',$this->project_id);
                    $result = $stmt->execute();
                    $stmt->close();
                    return false;
                }
                $stmt->close();
                return $result;
            }

            public function article_index_check($article_index)
            {
                $sql = "SELECT 1 from article where `index`= ? LIMIT 1"; //select 1 -> 레코드 존재 여부확인
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i",$article_index);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows >0)
                {
                    echo "중복된 index<br>";
                    $new_index = rand(10000000,99999999);
                    return $this->article_index_check($new_index);
                }
    
                return $article_index;
            }

            public function __destruct() {
                $this->db->close();
            }

        }
        //create_project에서 넘겨받는 변수들
        $proName=$_POST["project_name"];
        $proDes=$_POST["project_description"];
        $EstiStart_date=$_POST["project_start_date"];
        $EstiEnd_date=$_POST["project_end_date"];
        $inputs = $_POST['inputs'];
        //index 생성 변수
        if($proName==null || $proDes==null)
        {
            exit("<script>alert(`입력하지 않은 정보가 있습니다.`);
                location.replace(`../project/create_project.php`)</script>");
        }
            $DB = new Database();
            $new_project = new create_project($DB,$user_index);

            $result = $new_project->create_project($proName,$proDes,$EstiStart_date,$EstiEnd_date);
            if($result)
            {
                echo "프로젝트 생성 성공"; //이후 project_member tb도 같이 채워야지
                $promem_result = $new_project->create_manager();
                if($promem_result)
                {
                   echo "프로젝트 관리자가 갱신되었습니다.";
                }
                else{
                    echo "프로젝트 관리자 갱신에 실패하였습니다.";
                }

                //user table에 있는 이름인지 검사하고 promem에 넣어야지
                foreach ($inputs as $input) //입력된 멤버를 넣어주는 부분
                {
                    if(!empty($input))
                    { //여기가 안들어오는데
                        //echo "<script>alert('$input')</script>"; //테스트
                        $promem_result = $new_project->create_member($input);
                            if(!$promem_result){
                                //$new_project->__destruct();
                                exit("<script>alert('해당 멤버는 등록되지 않은 유저입니다.');
                                location.replace(`../board/dash_board.php`)</script>");
                            }
                        
                    }
                    else
                    {
                        //$new_project->__destruct();
                        //echo "<script>alert('프로젝트 멤버는 아직 결정되지 않았습니다.')</script>";
                    }
                }
                //echo "<script>alert('프로젝트의 멤버가 갱신되었습니다.')</script>";
                //$new_project->__destruct();    
                
                    exit("<script>alert('프로젝트가 생성되었습니다.');
                location.replace(`../board/dash_board.php`)</script>");

            }
            else 
            { 
                //$new_project->__destruct();
                exit("<script>alert('프로젝트 생성에 실패하였습니다.');
                location.replace(`../board/dash_board.php`)</script>");
            }
        ?>
    </body>

</html>