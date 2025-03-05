<?php
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");  //no cache //post이후 페이지 이동시 뒤로가기 눌렀을 때 ERR_CACHE_MISS 방지
    header("Pragma: no-cache");
    header('Expires: 0');
    if(!session_id())
        session_start();
    require_once '../DBconfig/Database.php';
    if(isset($_SESSION['user']))
        $user_id = $_SESSION['user'];

    if(isset($_SESSION['proID'])) //session이 아니라 post로 받아야 겠다 project_id는 세션이 나을거 같은데
        $project_id = $_SESSION['proID'];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 보기</title>
</head>
<body>
<?php
    require_once '../DBconfig/Database.php';
    class aboutPost{
        private $db;
        //private $subtask_id;
        private $project_id;
        private $user_id;
        private $task_id;
        private $article_id;
        public function __construct(Database $db, $user_id,$project_id, $task_id, $article_id) {
            $this->db = $db;
            $this->user_id = $user_id;
            $this->project_id = $project_id;
            $this->task_id = $task_id;
            $this->article_id = $article_id;
        }
        public function getFileList() {
            $sql = "SELECT `id`, file_path, created_at FROM file_uploads WHERE `project_id` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $this->project_id);
            $stmt->execute();
            return $stmt->get_result();
        }
        public function getProArticle() {
            $sql = "SELECT title, content, created_at, enable_notify FROM article WHERE `index` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $this->article_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function is_manager()
        {
            $sql = "SELECT 1 from project_member where `project_id`=? and `user_index`=? and `is_manager`=1"; //프로젝트 매니저인지 확인
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii',$this->project_id,$this->user_id);
            $stmt->execute();
            $result = $stmt->get_result()->num_rows;
            $stmt->close();
            return $result>0;
        }
    }

    $article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
    //echo "$article_id";
    $DB = new Database();
    $aboutfile = new aboutPost($DB, $user_id,$project_id, $task_id, $article_id );
    $post_info = $aboutfile->getProArticle();
    $is_notice = $post_info['enable_notify'];
    $is_manager = $aboutfile->is_manager();
    ?>
        <?php if($is_notice) echo "시스템 공지"; ?>
        <div class="post-header">
            <h1 class="post-title"><?php echo $post_info['title']; ?></h1>
        </div>

        <div class="post-content"><?php echo $post_info['content']; ?>
        </div>

        <div class="container">
            <button class="back-button" onclick="history.back()">이전 화면으로</button>
            <?php if(!$is_notice) : ?> <!-- 공지글은 댓글, 삭제 못하게 숨기기, 파일관련도 숨기기-->
            <div class="post-box">
                    <div class="file-list">
                        <h3>업로드된 파일 목록</h3>
                        <?php
                        /*
                        $results = $aboutfile->getFileList(); 
                        if ($results) {
                            while ($row = $results->fetch_assoc()) {
                                $file_name = basename($row['file_path']);
                                echo "<div class='file-item'>
                                        <a href='../board/download_file.php?file={$row['file_path']}'>$file_name {$row['created_at']}</a>
                                    </div>";
                            }
                        } else {
                            echo "업로드된 파일이 없습니다.";
                        }*/
                        ?>
                    </div>
        
                <div class="file-upload">
                <form action="../board/upload_file.php" method="post" enctype="multipart/form-data">
                    <label for="file-upload" class="custom-file-upload">
                    파일 선택
                    </label>
                    <input id="file-upload" type="file" name="uploaded_file">
                    <input type=hidden name="article_id" value="<?php echo $article_id;?>">
                    <input type=hidden name="article_id" value="<?php echo $project_id;?>">
                    <input type=hidden name="article_id" value="<?php echo $task_id;?>">
                    <input type=hidden name="article_id" value="<?php echo $subtask_id;?>">

                        <input type="submit" value="파일 업로드">
                    </form>
                </div>
            </div>
                        <?php endif; ?>
            <!-- 댓글 섹션 -->
            <?php if(!$is_notice) : ?> <!-- 공지글은 댓글, 삭제 못하게 숨기기 -->
            <div>
                <h3>댓글</h3>
                <div id="commentsSection"></div>
                <form id="commentForm">
                    <textarea id="content" name="content" required></textarea><br><br>
                    <input type="hidden" id="article_id" name="article_id" value="<?php echo $article_id; ?>">
                    <input type="hidden" id="task_id" name="task_id" value="<?php echo $task_id; ?>">
                    <button type="submit" class="comment-submit-button">댓글 작성</button>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if($is_manager) : ?> <!-- 매니저가 아니면 삭제 버튼 숨기기 -->
            <form id="deleteForm" action="../board/delete_post_check.php" method="post" style="display:inline;">
                <input type="hidden" name="article_id" value="<?php echo intval($article_id); ?>">
                <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>">
                <button type="button" class="delete-button" onclick="confirmDelete()">게시글 삭제</button>
            </form>
            <?php endif; ?>
        </div>
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
            function confirmDelete() {
                    if (confirm("정말로 이 게시글을 삭제하시겠습니까?")) {
                        // form을 submit하여 POST 요청을 전송합니다.
                        document.getElementById("deleteForm").submit();
                    }
                }

            $(document).ready(function() {
                loadComments();

                // 댓글 작성
                $('#commentForm').on('submit', function(event) {
                    event.preventDefault();
                    var formData = $(this).serialize();
                    console.log("Form Data to submit:", formData); // 디버깅용 로그
                    $.ajax({
                        url: '../board/submit_comment.php',
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            console.log("Submit response:", response); // 서버 응답 확인
                            if (response.trim() === "success") {
                                loadComments();
                                $('#content').val('');
                            } else {
                                console.error("Submit failed with response:", response);
                                alert("댓글 작성에 실패했습니다.");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Submit error:", xhr, status, error); // 에러 정보 출력
                            console.log("Response Text:", xhr.responseText); // 응답 텍스트 출력
                            alert("댓글 작성 중 오류가 발생했습니다.");
                        }
                    });
                });

                // 댓글 불러오기
                function loadComments() {
                    $.ajax({
                        url: '../board/load_comments.php',
                        type: 'POST',
                        data: { project_id: <?php echo $project_id; ?>, article_id: <?php echo $article_id; ?> },
                        success: function(data) {
                            console.log("Load response:", data); // 디버깅용 로그
                            $('#commentsSection').html(data);
                        },
                        error: function(xhr, status, error) {
                            console.error("Load error:", xhr, status, error);
                            alert("댓글을 불러오는데 오류가 발생했습니다.");
                        }
                    });
                }
            });

        </script>
</body>
</html>