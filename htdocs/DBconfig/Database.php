<?php
            require_once '../DBconfig/DBconfig.php';
            class Database{ //DB 연결에 대한 클래스화 
                private $conn;

                public function __construct() {
                    $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                    if ($this->conn->connect_error) {
                        die("DB 연결 실패: " . $this->conn->connect_error);
                    }
                    //$this->conn->set_charset("utf8mb4");
                }

                public function prepare($sql){ //prepare은 query에 비해 보안, 성능면에서 좋음
                    return $this->conn->prepare($sql); //sql 컴파일 해서 재사용함 -> 동일 구문에서 유효한 효과
                }

                // query 메서드 추가
                public function query($sql) {
                    $result = $this->conn->query($sql);
                    if ($result === false) {
                        // 쿼리 실행 실패 시 오류 로깅 또는 예외 처리
                        error_log("Query failed: " . $this->conn->error);
                        return false;
                    }
                    return $result;
                }

                // MySQLi의 error 메시지를 반환하는 메서드 추가
                public function get_error() {
                    return $this->conn->error;
                }

                // 트랜잭션 시작
                public function begin_transaction() {
                    return $this->conn->begin_transaction();
                }

                // 트랜잭션 커밋
                public function commit() {
                    return $this->conn->commit();
                }

                // 트랜잭션 롤백
                public function rollback() {
                    return $this->conn->rollback();
                }

                public function close() {
                    $this->conn->close();
                }
            }

?>