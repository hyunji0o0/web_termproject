<?php
// config.php
// 예시 파일이니 진짜 비밀번호를 채워서 사용 (이 파일 본인 컴퓨터에 저장 후 본인 이메일 정보에 맞게 수정 필수)
// 여기 들어가는 메일은 마이 피트니스 발신자의 메일로 인증 메일을 보낼 때
// 발신자로 표시할 메일을 임의로 넣어주시면 됩니다

define('JWT_SECRET_KEY', 'YOUR_SECRET_KEY_HERE'); // 임의의 비밀키
define('MAIL_USERNAME', 'YOUR_EMAIL@naver.com'); // 도메인 이메일
define('MAIL_PASSWORD', 'YOUR_NAVER_APP_PASSWORD'); // 네이버 2차 인증 패스키(한 번만 발급받아서 넣으면 됨)

define('DB_HOST', 'localhost');
define('DB_NAME', 'db_signup');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
?>