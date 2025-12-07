<?php
// update_profile.php
// 역할: 로그인한 사용자의 키 또는 몸무게 정보를 수정함

require_once 'db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

// 1. JWT 토큰 검증 및 사용자 ID 추출 (인증 절차)
try {
    // 헤더에서 토큰 가져오기
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '인증 토큰이 없습니다.']);
        exit;
    }
    $jwt = $matches[1];

    // 토큰 디코딩
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));

    // 로그아웃된 토큰인지(블랙리스트) 확인
    if (isset($decoded->jti)) {
        $stmt = $pdo->prepare("SELECT jti FROM revoked_tokens WHERE jti = ?");
        $stmt->execute([$decoded->jti]);
        if ($stmt->rowCount() > 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => '로그아웃된 토큰입니다.']);
            exit;
        }
    }

    // 토큰 주인(userId) 획득
    $userId = $decoded->data->userId;

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증 실패: ' . $e->getMessage()]);
    exit;
}

// 2. 프론트엔드 데이터 수신
$data = json_decode(file_get_contents('php://input'));

// 데이터 유효성 검사
if (empty($data->mode) || empty($data->value)) {
    echo json_encode(['success' => false, 'message' => '수정할 항목과 값을 입력해주세요.']);
    exit;
}

$mode = $data->mode; // 'height' 또는 'weight'
$value = $data->value;

// 모드 검사 (보안을 위해 허용된 컬럼만 수정 가능하도록 제한)
if ($mode !== 'height' && $mode !== 'weight') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다. (height/weight만 가능)']);
    exit;
}

// 값 검사 (숫자인지 확인)
if (!is_numeric($value) || $value <= 0) {
    echo json_encode(['success' => false, 'message' => '값은 0보다 큰 숫자여야 합니다.']);
    exit;
}

// 3. DB 업데이트 수행
try {
    // 모드에 따라 쿼리 분기
    if ($mode == 'height') {
        $sql = "UPDATE users SET height = ? WHERE user_id = ?";
    } else {
        $sql = "UPDATE users SET weight = ? WHERE user_id = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value, $userId]);

    // 업데이트 성공
    echo json_encode([
        'success' => true,
        'message' => ($mode == 'height' ? '키' : '몸무게') . ' 정보가 수정되었습니다.',
        'updated_value' => $value
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB 업데이트 오류: ' . $e->getMessage()]);
}
?>