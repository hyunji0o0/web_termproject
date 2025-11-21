-- 사용자 정보 테이블 (users) 추후 항목 추가 바람
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '사용자 고유 ID',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '네이버 이메일 (로그인 ID)',
    password VARCHAR(255) NOT NULL COMMENT '비밀번호',
    name VARCHAR(100) COMMENT '사용자 이름',
    
    -- 신체 정보
    height DECIMAL(5,2) COMMENT '키 (cm)',
    weight DECIMAL(5,2) COMMENT '몸무게 (kg)',
    age INT COMMENT '나이',
    gender ENUM('male', 'female', 'other') COMMENT '성별',
    
    bmr FLOAT NULL COMMENT '기초대사량',
    
    -- 계정 상태
    is_verified TINYINT(1) DEFAULT 0 COMMENT '이메일 인증 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '가입일'
)
COMMENT='마이 피트니스 파트너 사용자 정보';


-- 이메일 인증 코드 테이블 (인증 후엔 삭제됨)
CREATE TABLE verification_codes (
    email VARCHAR(255) NOT NULL PRIMARY KEY COMMENT '인증 대상 이메일',
    code VARCHAR(6) NOT NULL COMMENT '6자리 인증 코드',
    expires_at TIMESTAMP NOT NULL COMMENT '코드 만료 시간'
)
COMMENT='이메일 인증 코드 및 만료 시간 관리';

--로그인 후 발급된 토큰과 토큰의 만료 시간 저장
CREATE TABLE revoked_tokens (
    jti VARCHAR(255) PRIMARY KEY,
    expires_at INT NOT NULL
)
COMMENT='로그인 토큰 저장 및 관리';
