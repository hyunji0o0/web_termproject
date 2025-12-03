<?php
// 이 파일은 신규 회원 정보를 DB에 저장함 (is_verified = 0 상태)

require_once __DIR__ . '/db_connection.php'; // $pdo 준비

$pdo->exec("
  CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NULL,
    height DECIMAL(5,2) NULL,
    weight DECIMAL(5,2) NULL,
    age INT NULL,
    gender ENUM('male','female','other') NULL,
    bmr FLOAT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )
");

require_once 'config.php'; // 추가 설정 파일

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Headers: Content-Type');

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
$input_code = $data->code;
// 선택 값 (없으면 기본값 0 또는 null)
$height = $data->height ?? 0;
$weight = $data->weight ?? 0;
$age = $data->age ?? 0;
$gender = $data->gender ?? 'male';

try {
    // 2. 이메일 중복 체크
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => '이미 가입된 이메일입니다.']);
        exit;
    }

    // 3. 인증번호 검증 로직 (상세 디버깅용)
    // 일단 이메일로 저장된 코드를 가져옵니다.
    $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3-1. DB에 코드가 아예 없는 경우
    if (!$row) {
        echo json_encode(['success' => false, 'message' => '인증 내역이 없습니다. (verify.php에서 삭제 로직을 확인하세요)']);
        exit;
    }

    // 3-2. 코드가 틀린 경우
    if ($row['code'] != $input_code) {
        echo json_encode(['success' => false, 'message' => "인증번호가 일치하지 않습니다. (입력: $input_code)"]);
        exit;
    }

    // 3-3. 시간이 만료된 경우
    // DB의 expires_at 문자열을 타임스탬프로 변환
    $expire_time = strtotime($row['expires_at']);
    $current_time = time();

    if ($current_time > $expire_time) {
        echo json_encode(['success' => false, 'message' => '인증 시간이 만료되었습니다. 다시 인증해주세요.']);
        exit;
    }

    // 4. 비밀번호 암호화
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. DB에 회원 정보 저장 (is_verified = 1)
    $pdo->beginTransaction();

    $sql = "INSERT INTO users (email, password, name, height, weight, age, gender, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $hashed_password, $name, $height, $weight, $age, $gender]);
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE email = ?");
    $stmt->execute([$email]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => '회원가입이 완료되었습니다.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'DB 오류: ' . $e->getMessage()]);
}
?>
