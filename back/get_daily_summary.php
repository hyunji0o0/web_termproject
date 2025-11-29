<?php
require_once 'db_connection.php';
require_once 'vendor/autoload.php';
require_once 'config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (strpos($authHeader, 'Bearer ') !== 0) {
    // 토큰 없으면 0 리턴
    echo json_encode(['success' => false, 'intake' => 0, 'burned' => 0, 'user' => null]);
    exit;
}
$jwt = substr($authHeader, 7);

try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
    $userId = $decoded->data->userId;

    // 1. 오늘의 섭취 칼로리 총합
    $stmt = $pdo->prepare("SELECT SUM(kcal) as total_intake FROM food_logs WHERE user_id = ? AND log_date = CURDATE()");
    $stmt->execute([$userId]);
    $intake = $stmt->fetchColumn() ?: 0;

    // 2. 오늘의 운동 칼로리 총합
    $stmt = $pdo->prepare("SELECT SUM(burned_kcal) as total_burned FROM activity_logs WHERE user_id = ? AND log_date = CURDATE()");
    $stmt->execute([$userId]);
    $burned = $stmt->fetchColumn() ?: 0;
    
    // 3. 유저 기본 정보 (BMI 계산용 키/몸무게)
    $stmt = $pdo->prepare("SELECT name, height, weight FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'intake' => round($intake),
        'burned' => round($burned),
        'user' => $user
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>