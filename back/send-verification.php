<?php
// 필요한 파일 포함
require_once 'db_connection.php';
require_once 'mailer.php'; // sendVerificationEmail 함수 사용

// 1. React로부터 JSON 입력을 받음
$data = json_decode(file_get_contents('php://input'));

// 헤더 설정 (JSON 응답)
header('Content-Type: application/json');

if (empty($data->email)) {
    echo json_encode(['success' => false, 'message' => '이메일을 입력해주세요']);
    exit;
}

$email = $data->email;

// 2. 6자리 인증코드 생성
$code = (string)rand(100000, 999999);

// 3. 만료 시간 설정
$expires_at = date('Y-m-d H:i:s', time() + 60); // 1분

try {
    // 4. DB에 인증 코드 저장 (이미 이메일이 존재하면 코드와 만료시간 업데이트)
    $sql = "INSERT INTO verification_codes (email, code, expires_at) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE code = ?, expires_at = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $code, $expires_at, $code, $expires_at]);

    // 5. 메일 발송 함수 호출
    if (sendVerificationEmail($email, $code)) {
        // 발송 성공
        echo json_encode(['success' => true, 'message' => '인증코드가 발송되었습니다']);
    } else {
        // 발송 실패
        echo json_encode(['success' => false, 'message' => '메일 발송에 실패했습니다']);
    }

} catch (PDOException $e) {
    // DB 오류
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다']);
    error_log($e->getMessage());
}
?>
