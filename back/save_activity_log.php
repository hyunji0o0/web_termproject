<?php
require_once 'db_connection.php';
require_once 'vendor/autoload.php';
require_once 'config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(['success' => false, 'message' => '인증 토큰 필요']);
    exit;
}
$jwt = substr($authHeader, 7);

try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
    $userId = $decoded->data->userId;

    $data = json_decode(file_get_contents('php://input'));

    if (empty($data->name) || empty($data->kcal)) {
        echo json_encode(['success' => false, 'message' => '운동 데이터 부족']);
        exit;
    }

    $sql = "INSERT INTO activity_logs (user_id, activity_name, duration_min, burned_kcal, log_date) 
            VALUES (?, ?, ?, ?, CURDATE())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $data->name, $data->duration, $data->kcal]);

    echo json_encode(['success' => true, 'message' => '운동 기록 완료']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '오류: ' . $e->getMessage()]);
}
?>