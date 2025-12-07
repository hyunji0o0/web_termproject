<?php
// 역할: 이메일 기반 최신 토큰 조회 및 유저 정보 검증

require_once 'db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json; charset=utf-8');

// 1. 이메일 받기
$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => '이메일 주소가 필요합니다. (?email=...)']);
    exit;
}

try {
    // 2. 최신 토큰 조회
    $stmt = $pdo->prepare("SELECT token, created_at FROM dev_tokens WHERE user_email = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        throw new Exception("해당 이메일로 저장된 토큰 내역이 없습니다.");
    }

    $jwt = $tokenData['token'];

    // 3. 토큰 검증
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));

    // 4. 유저 정보 조회
    $userId = $decoded->data->userId;

    $stmt = $pdo->prepare("SELECT user_id, email, name, height, weight, age, gender, is_verified FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('토큰은 유효하나, 사용자 정보를 찾을 수 없습니다.');
    }

    // 5. 결과 출력
    echo json_encode([
        'success' => true,
        'message' => '토큰 조회 및 검증 성공',
        'token_info' => [
            'created_at' => $tokenData['created_at'],
            'expires_at' => date('Y-m-d H:i:s', $decoded->exp),
            'jti' => $decoded->jti ?? 'N/A'
        ],
        'user_info' => $user,
        'token_string' => $jwt
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => '토큰 검증 실패',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>