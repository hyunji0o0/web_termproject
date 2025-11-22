
<?php
// 이 파일은 사용자의 JWT 토큰을 즉시 무효화(revoked_token에 추가)하여 로그아웃 처리를 함

require 'db_connection.php';
require 'vendor/autoload.php';
require_once 'config.php'; // 추가 설정 파일

// JWT 라이브러리 사용 선언
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 응답 형식을 JSON으로 설정
header('Content-Type: application/json');

// 1. 요청 메소드 확인
$method = $_SERVER['REQUEST_METHOD'];

// 보안을 위해 POST 메소드만 허용 (토큰 무효화는 데이터를 변경하는 행위)
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST 메소드가 아닙니다']);
    exit;
}

// 2. Authorization 헤더에서 JWT 토큰 추출
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// 'Bearer' 형식으로 들어왔는지 정규표현식으로 체크
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '토큰이 제공되지 않았습니다']);
    exit;
}
$jwt = $matches[1]; // 정규표현식에서 추출된 토큰 문자열

// 3. 토큰 검증 및 블랙리스트 등록
try {
    // 토큰 검증 및 디코딩. 실패 시 catch 블록으로 이동
    $decoded = JWT::decode($jwt, new Firebase\JWT\Key($secret_key, 'HS256'));
    
    // 디코딩된 토큰에서 고유 ID와 만료 시간을 추출
    $jti = $decoded->jti;
    $exp = $decoded->exp;

    // 토큰의 jti와 만료 시간을 revoked_tokens 테이블에 삽입
    // expires_at은 만료된 토큰을 자동으로 정리
    $stmt = $pdo->prepare("INSERT INTO revoked_tokens (jti, expires_at) VALUES (?, ?)");
    $stmt->execute([$jti, $exp]);

    // 성공 응답 전송
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => '로그아웃 성공']);

} catch (Exception $e) {
    // 토큰 자체가 유효하지 않거나 이미 만료된 경우
    // 실질적인 로그아웃 효과는 있으나, 클라이언트에게는 실패를 알림
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '유효하지 않거나 만료된 토큰입니다']);
}
?>