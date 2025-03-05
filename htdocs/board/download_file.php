<?php
        if (isset($_GET['file'])) {
            $file_path = $_GET['file'];
            echo "전달된 파일 경로: " . $file_path;
        } else {
            echo "파일 경로가 전달되지 않았습니다.";
        }

    // 파일 경로가 올바른지 확인
    if (file_exists($file_path)) {
        // 파일의 확장자에 따른 MIME 타입 설정
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $mime_type = mime_content_type($file_path);

        // 파일 다운로드를 위한 헤더 설정
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');

        // 파일 읽기 및 전송
        readfile($file_path);
        exit;
    } else {
        echo "파일을 찾을 수 없습니다.";
    }
?>