프로젝트 일정 관리 시스템

📌 프로젝트 개요

본 프로젝트는 팀의 프로젝트 일정 및 진행 상황을 효율적으로 관리하기 위한 웹 기반 프로젝트 일정 관리 시스템입니다. 사용자는 일정 생성, 수정, 삭제 및 게시판을 통해 협업할 수 있습니다.

🚀 주요 기능

일정 관리: 프로젝트 일정 추가, 수정, 삭제

게시판: 프로젝트 관련 공지 및 논의 가능

파일 업로드 및 다운로드: 관련 자료 업로드 및 공유

사용자 관리: 로그인 및 권한 관리 (추가 필요 시 적용 가능)

🛠️ 사용 기술

백엔드: PHP, MySQL

프론트엔드: HTML, CSS, JavaScript

데이터베이스: MySQL

환경: Apache (XAMPP 또는 LAMP)

📂 프로젝트 폴더 구조

projectMangement/
├── htdocs/
│   ├── index.php  # 메인 페이지
│   ├── board/
│   │   ├── board.php  # 게시판 메인
│   │   ├── create_post.php  # 게시글 작성
│   │   ├── delete_post_check.php  # 게시글 삭제
│   │   ├── download_file.php  # 파일 다운로드
│   ├── ...

🔧 설치 및 실행 방법

1. 환경 설정

XAMPP 또는 LAMP를 설치합니다.
Apache와 MySQL을 실행합니다.

2. 프로젝트 배포

htdocs/ 폴더를 XAMPP의 htdocs 디렉토리에 복사합니다.
브라우저에서 http://localhost/index.php에 접속합니다.

3. 데이터베이스 설정
테이블 구조
project: 프로젝트 정보
user: 사용자 정보
project_member: 프로젝트 멤버 정보
task: 작업 정보
subtask: 세부작업 정보
article: 게시글 정보
file: 파일 정보
article_attachment: 게시글 첨부파일 정보

뷰
latest_project_view: 최신 프로젝트 정보
latest_task_view: 최신 작업 정보
latest_subtask_view: 최신 세부작업 정보
latest_summary_view: 프로젝트, 작업, 세부작업 요약 정보

프로시저
request_subtask_completion: 세부작업 완료 승인 요청
start_project: 프로젝트 시작 시 세부작업 시작 날짜 설정

트리거
프로젝트, 작업, 세부작업 정보 변경 시 자동 알림

관리자의 프로젝트 멤버 추가 방지

주요 필드 직접 수정 방지

