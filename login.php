<?php
// 필요한 파일 포함
require 'db_connection.php'; // DB 연결 ($pdo)
require 'vendor/autoload.php'; // Composer 라이브러리
require_once 'config.php'; // 추가 설정 파일

// JWT 네임스페이스 사용
use Firebase\JWT\JWT;

// 1. React로부터 JSON 입력을 받음 (email, password)
$data = json_decode(file_get_contents('php://input'));
header('Content-Type: application/json');

if (empty($data->email) || empty($data->password)) {
    echo json_encode(['success' => false, 'message' => '이메일과 비밀번호를 모두 입력해주세요']);
    exit;
}

$email = $data->email;
$password = $data->password;

try {
    // 2. DB에서 사용자 정보 조회
    $sql = "SELECT user_id, email, password, is_verified FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. 사용자 존재 여부 및 비밀번호 확인
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
        exit;
    }

    // 4. 이메일 인증 여부 확인
    if ($user['is_verified'] == 0) {
        echo json_encode(['success' => false, 'message' => '아직 이메일 인증이 완료되지 않았습니다.']);
        exit;
    }

    // 5. 로그인 성공: JWT 토큰 생성
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 24 * 365); // 토큰 만료 시간 (현재: 1년)
    $issuer = "http://localhost/my_fitness_partner"; // 토큰 발급자 (도메인)
    $token_id = uniqid('', true); // 토큰의 고유 ID 생성

    $payload = [
        'iat' => $issued_at, // 토큰 발급 시간
        'exp' => $expiration_time, // 토큰 만료 시간
        'iss' => $issuer, // 토큰 발급자
        "jti" => $token_id, // 로그인 토큰의 고유 id
        // 실제 사용할 사용자 데이터
        'data' => [
            'userId' => $user['user_id'],
            'email' => $user['email']
        ]

    ];

    // JWT 생성
    $jwt = JWT::encode($payload, JWT_SECRET_KEY, 'HS256'); // HS256 알고리즘 사용

    // 6. 토큰과 함께 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '로그인 성공',
        'token' => $jwt
    ]);

}
catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
    // error_log($e->getMessage());
}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '토큰 생성 중 오류가 발생했습니다.']);
    // error_log($e->getMessage());
}
?>
