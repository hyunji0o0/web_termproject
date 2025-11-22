<?php
// 필요한 파일 포함
require_once 'db_connection.php'; // DB 연결 ($pdo)
require_once 'vendor/autoload.php'; // Composer 라이브러리
require_once 'config.php'; // 추가 설정 파일

// JWT 네임스페이스 사용
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

// 헤더 설정 (JSON 응답)
header('Content-Type: application/json');

try {
    // 1. 요청 헤더에서 토큰 가져오기
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!$authHeader) {
        throw new Exception('Authorization 헤더가 없습니다');
    }

    // "Bearer" 부분을 잘라내고 토큰 값만 추출
    if (strpos($authHeader, 'Bearer ') !== 0) {
        throw new Exception('토큰 형식이 올바르지 않습니다 (Bearer 누락)');
    }
    $jwt = substr($authHeader, 7);

    if (!$jwt) {
        throw new Exception('토큰이 없습니다.');
    }

    // 2. 토큰 검증 및 디코딩
    // Key 객체 사용 (최신 firebase/php-jwt 방식)
    $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
    
    // 로그아웃된 토큰인지 확인
    if (isset($decoded->jti)) {
        $jti = $decoded->jti;
        // DB에서 이 jti가 있는지 확인
        $stmt = $pdo->prepare("SELECT jti FROM revoked_tokens WHERE jti = ?");
        $stmt->execute([$jti]);
        
        if ($stmt->rowCount() > 0) {
            // revoked_tokens에 있다면 쫓아냄
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => '로그아웃된 토큰입니다']);
            exit;
        }
    }

    // $decoded 안에는 login.php에서 넣었던 payload가 들어있음
    $userId = $decoded->data->userId;

    // 3. DB에서 실제 사용자 정보 조회 (추후 계산된 정보 추가)
    $sql = "SELECT user_id, email, name, height, weight, age, gender, bmr 
            FROM users 
            WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('토큰은 유효하지만, 해당 사용자를 찾을 수 없습니다');
    }

    // 4. 성공 응답 (비밀번호 제외한 정보)
    echo json_encode([
        'success' => true,
        'message' => '사용자 정보 조회 성공',
        'data' => $user // DB에서 가져온 사용자 정보
    ]);

} catch (ExpiredException $e) {
    http_response_code(401); // 401 Unauthorized
    echo json_encode(['success' => false, 'message' => '토큰이 만료되었습니다']);
} catch (SignatureInvalidException $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '토큰 서명이 유효하지 않습니다']);
} catch (BeforeValidException $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '토큰이 아직 유효하지 않습니다']);
} catch (Exception $e) {
    // 그 외 모든 예외
    http_response_code(401); // 기본적으로 인증 실패로 처리
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
