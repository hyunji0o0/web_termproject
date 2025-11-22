<?php
// XAMPP DB 연결 설정
require_once 'config.php'; // 추가 설정 파일

$host = DB_HOST; // XAMPP 기본 호스트
$db_name = DB_NAME; // 데이터베이스 이름
$username = DB_USER; // XAMPP 기본 사용자 이름
$password = DB_PASSWORD; // XAMPP 기본 비밀번호
$charset = 'utf8mb4'; // 한글 깨짐 방지

// PDO 연결 시도
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 오류 발생 시 예외를 던짐
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 결과를 항상 key-value로 받음
    PDO::ATTR_EMULATE_PREPARES => false, // SQL Injection 방지
];

try {
     // $pdo 라는 이름의 변수에 DB 연결 객체를 생성
     $pdo = new PDO($dsn, $username, $password, $options);
     
} catch (\PDOException $e) {
     // DB 연결 실패 시, 에러 메시지를 JSON으로 출력하고 스크립트 종료
     http_response_code(500); // 서버 내부 오류
     echo json_encode([
        'success' => false, 
        'message' => '데이터베이스 연결에 실패했습니다.',
        //'error' => $e->getMessage()
    ]);
     exit; // 즉시 종료
}

// 이 파일이 다른 파일에서 요청되면, $pdo 변수를 바로 사용할 수 있게 됨
?>
