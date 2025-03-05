<?php
   header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
   header("Pragma: no-cache");
   header('Expires: 0');
   session_cache_limiter('private_no_expire'); // works
    if(!session_id())
        session_start();
    require_once '../DBconfig/Database.php';
    if(isset($_SESSION['user']))
        $user_id = $_SESSION['user'];

    if(isset($_SESSION['proID'])) //session이 아니라 post로 받아야 겠다 project_id는 세션이 나을거 같은데
        $project_id = $_SESSION['proID'];


    class board{ //프로젝트에서 게시판 들어오면 썻던 글 보여주고, 관련 태스크에서 썻던 글들 다 보여주고 그거 클릭하면 업로드한 파일이랑 댓글들..
        private $user_id;
        private $project_id;
        private $db;

        public function __construct(Database $db,$user_id, $project_id,) //php는 생성자 오버로딩 없음
        {
            $this->db = $db;
            $this->user_id = $user_id;
            $this->project_id = $project_id; //project까지는 확실히 생김 
        }
        
        public function getProArticle() //댓글이랑 구별이 되어야 하는데 comment of index가 없는 애가 게시글
        {

            //echo "프로젝트 함수"; //당연히 프로젝트 게시판에는 모든 태스크에 대한 글이 보여야지
            $sql = "SELECT `index`, title, created_member_index,created_at from article where project_id = ? and (comment_of_index IS NULL) order by created_at DESC"; //게시글 최신부터 역순으로
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i',$this->project_id);
            if(!$stmt->execute())
            {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                echo "<script>alert(게시글을 불러오는데 문제가 발생하였습니다.); history.back();</script>";
            }
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
            
        }

        public function getKeywordArticle($keyword) //project_id가 ?인 것 중에서 들고와야 겠는데
        {
            $keyword = '%'.$keyword.'%';
            $sql = "SELECT `index`, title, created_member_index,created_at from article where project_id = ? and  title LIKE ?
            and (comment_of_index IS NULL) order by created_at DESC"; //해당 프로젝트 내에 속해있는 검색어가 있는 게시글 최신부터 역순으로
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is',$this->project_id,$keyword);
            if(!$stmt->execute())
            {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                echo "<script>alert(해당 제목의 게시글이 없습니다.);</script>";
            }
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
     
        public function getWriter($created_member_index) //이거 어떡하냐
        {
            $sql = "SELECT u.username from user as u
                        inner join project_member as pm
                        ON u.index = pm.user_index 
                        where pm.index = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i',$created_member_index);
            if(!$stmt->execute())
                {
                    error_log("Execute failed: " . $stmt->error);
                    $stmt->close();
                    echo "<script>alert(게시글을 불러오는데 문제가 발생하였습니다.); history.back();</script>";
                }
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row && isset($row['username'])) {
                    return $row['username'];
                } else {
                    return false; // 사용자를 찾지 못했을 때 기본값 반환
                }
        }

    }


    $DB = new Database();
    $about_Post = new board($DB,$user_id,$project_id);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <title>프로젝트 게시판</title>
</head>
<body>
    <div class="container">
    <button onclick="location.href='../board/dash_board.php'">대시보드로</button>
        <button onclick="location.href='../project/project.php?id=<?php echo $project_id?>'">해당 프로젝트로</button>
        <form id="searchForm">
            <input type="text" id="searchInput" name="keyword" placeholder="검색어를 입력하세요">
            <button type="submit">검색</button>
        </form>
        <h1>프로젝트 게시판</h1>
        <table>
            <thead>
                <tr>
                    <th>제목</th>
                    <th>작성자</th>
                    <th>날짜</th>
                </tr>
            </thead>
            <tbody>
                
                    <?php
                    if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') {
                        $keyword = htmlspecialchars($_GET['keyword']);
                        $posts = $about_Post->getKeywordArticle($keyword);
                        while($post = $posts->fetch_assoc())
                        {
                            echo "<tr>";
                            $created_member_name = $about_Post->getWriter($post['created_member_index']);
                            if(!$created_member_name)
                            echo "<script>alert(게시글을 불러오는데 문제가 발생하였습니다.); history.back();</script>";
                            $title = $post['title'];
                            $created_at = $post['created_at'];
                            $article_index = $post['index'];
                            //echo "$article_index";
                            echo "<td><a href='../board/view_post.php?id={$article_index}'>{$title}</a></td>";
                            echo "<td>$created_member_name</td>";
                            echo "<td>$created_at</td>";
                            echo "</tr>";
                        }

                    }
                    else{
                        $posts = $about_Post->getProArticle(); //여기서 게시글 배열이 저장됨
                        while($post = $posts->fetch_assoc())
                            {
                                echo "<tr>";
                                $created_member_name = $about_Post->getWriter($post['created_member_index']);
                                if(!$created_member_name)
                                echo "<script>alert(게시글을 불러오는데 문제가 발생하였습니다.); history.back();</script>";
                                $title = $post['title'];
                                $created_at = $post['created_at'];
                                $article_index = $post['index'];
                                //echo "$article_index";
                                echo "<td><a href='../board/view_post.php?id={$article_index}'>{$title}</a></td>";
                                echo "<td>$created_member_name</td>";
                                echo "<td>$created_at</td>";
                                echo "</tr>";
                            }
                    }
                    ?>
                
            </tbody>
        </table>
        <div class="button-container">
            <button onclick="confirmWrite()">글쓰기</button>
            <input type="hidden" id="project_id" name="project_id" value="<?php echo $project_id; ?>">
        </div>

    
        <script>
       
            function confirmWrite() {
                if (confirm("글을 쓰겠습니까?")) {
                    var projectId = document.getElementById('project_id').value;
                    location.href = "../board/create_post.php?project_id=" + projectId;
                }
            }
            

            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                var searchTerm = document.getElementById('searchInput').value;
                if (searchTerm) {
                    window.location.href = '?keyword=' + encodeURIComponent(searchTerm);
                } else {
                    // 검색어가 비어 있으면 'keyword' 파라미터 없이 페이지 리로드
                    window.location.href = window.location.pathname;
                }
            });

            // 페이지 로드 시 URL에서 검색어 파라미터 확인
            window.addEventListener('load', function() {
                var urlParams = new URLSearchParams(window.location.search);
                var searchTerm = urlParams.get('keyword');
                if (searchTerm) {
                    document.getElementById('searchInput').value = searchTerm;
                }
            });
        </script>
        
    </div>
</body>
</html>