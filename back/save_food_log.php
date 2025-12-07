<?php
require_once 'db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// 1. 토큰 검증
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (strpos($authHeader, 'Bearer ') !== 0) {
    echo json_encode(['success' => false, 'message' => '토큰이 없습니다.']);
    exit;
}
$jwt = substr($authHeader, 7);

try {
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
    $userId = $decoded->data->userId;

    // 2. 데이터 수신 (JSON 배열)
    $input = json_decode(file_get_contents('php://input'), true);
    $plate = $input['plate'] ?? [];

    if (empty($plate)) {
        echo json_encode(['success' => false, 'message' => '저장할 데이터가 없습니다.']);
        exit;
    }

    // 3. DB 저장 (트랜잭션)
    $pdo->beginTransaction();
    $sql = "INSERT INTO food_logs (user_id, food_name, intake_weight, kcal, carb, protein, fat, log_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
    $stmt = $pdo->prepare($sql);

    foreach ($plate as $item) {
        // 영양소 비율 계산 (기준 중량 대비 실제 섭취량)
        $ratio = ($item['baseWeight'] > 0) ? ($item['weight'] / $item['baseWeight']) : 0;
        
        $kcal = ($item['nutrients']['kcal'] ?? 0) * $ratio;
        $carb = ($item['nutrients']['carb'] ?? 0) * $ratio;
        $prot = ($item['nutrients']['protein'] ?? 0) * $ratio;
        $fat  = ($item['nutrients']['fat'] ?? 0) * $ratio;

        $stmt->execute([
            $userId, 
            $item['name'], 
            $item['weight'], 
            $kcal, $carb, $prot, $fat
        ]);
    }
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => '식단이 기록되었습니다.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '저장 실패: ' . $e->getMessage()]);
}
?>