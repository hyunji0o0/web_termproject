<?php
// 이 파일은 신규 회원 정보를 DB에 저장함 (is_verified = 0 상태)

require_once 'db_connection.php';
require_once 'config.php'; // 추가 설정 파일

header('Content-Type: application/json');

// 1. 요청 데이터 수신
$data = json_decode(file_get_contents('php://input'));

// 필수 값 확인
if (empty($data->email) || empty($data->password) || empty($data->name)) {
    echo json_encode(['success' => false, 'message' => '이메일, 비밀번호, 이름은 필수입니다']);
    exit;
}

$email = $data->email;
$password = $data->password;
$name = $data->name;

// 선택 값 (없으면 기본값 0 또는 null)
$height = $data->height ?? 0;
$weight = $data->weight ?? 0;
$age = $data->age ?? 0;
$gender = $data->gender ?? 'male';

try {
    // 2. 이미 존재하는 이메일인지 확인
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => '이미 가입된 이메일입니다.']);
        exit;
    }

    // 3. 비밀번호 해싱 (안전한 암호화)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. DB에 회원 정보 저장 (is_verified = 0)
    $sql = "INSERT INTO users (email, password, name, height, weight, age, gender, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $hashed_password, $name, $height, $weight, $age, $gender]);

    echo json_encode(['success' => true, 'message' => '회원가입이 완료되었습니다. 이메일 인증을 진행해주세요.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 오류: ' . $e->getMessage()]);
}
?>