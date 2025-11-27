<?php
// 필요한 파일 포함
require 'db_connection.php'; // DB 연결 ($pdo)

// 1. React로 JSON 입력을 받음 (email, code)
$data = json_decode(file_get_contents('php://input'));
header('Content-Type: application/json');

if (empty($data->email) || empty($data->code)) {
    echo json_encode(['success' => false, 'message' => '이메일과 인증 코드를 모두 입력해주세요.']);
    exit;
}

$email = $data->email;
$code = $data->code;

try {
    // 2. DB에서 이메일로 인증 코드 조회
    $sql = "SELECT code, expires_at FROM verification_codes WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    
    $verification_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification_data) {
        echo json_encode(['success' => false, 'message' => '인증 코드가 요청된 적이 없거나 만료되었습니다.']);
        exit;
    }

    // 3. 코드 일치 및 만료 시간 확인
    $db_code = $verification_data['code'];
    $expires_at = strtotime($verification_data['expires_at']);
    $current_time = time();

    if ($db_code !== $code) {
        echo json_encode(['success' => false, 'message' => '인증 코드가 올바르지 않습니다.']);
        exit;
    }

    if ($current_time > $expires_at) {
        echo json_encode(['success' => false, 'message' => '인증 코드가 만료되었습니다. 다시 요청해주세요.']);
        exit;
    }

    // 4. 인증 성공: 트랜잭션 시작 (users 업데이트, verification_codes 삭제)
    $pdo->beginTransaction();

    // 4-1. users 테이블의 is_verified = 1 로 업데이트
    $sql_update_user = "UPDATE users SET is_verified = 1 WHERE email = ?";
    $stmt_update = $pdo->prepare($sql_update_user);
    $stmt_update->execute([$email]);

    // 4-2. 사용 완료된 인증 코드 삭제
    $sql_delete_code = "DELETE FROM verification_codes WHERE email = ?";
    $stmt_delete = $pdo->prepare($sql_delete_code);
    $stmt_delete->execute([$email]);
    
    // 4-3. 트랜잭션 커밋
    $pdo->commit();

    // 5. 인증 성공 응답
    echo json_encode(['success' => true, 'message' => '이메일 인증이 완료되었습니다']);

} catch (PDOException $e) {
    // DB 오류 시 롤백
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => '데이터베이스 처리 중 오류가 발생했습니다']);
    // error_log($e->getMessage());
}
?>