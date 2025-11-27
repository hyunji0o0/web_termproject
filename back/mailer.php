<?php
// PHPMailer 클래스 사용 선언
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Composer의 autoload 파일 로드
require_once 'vendor/autoload.php';
require_once 'config.php'; // 추가 설정 파일

// 네이버 SMTP를 사용해 인증 메일을 발송
// $toEmail: 수신자 이메일 주소 
// $code: 발송할 6자리의 인증 코드
// 발송 성공 시 true, 실패 시 false
function sendVerificationEmail($toEmail, $code) {

    $mail = new PHPMailer(true); // 예외 처리를 활성화한 PHPMailer 인스턴스 생성

    try {
        // 1. SMTP 서버 설정
        $mail->isSMTP(); // SMTP 사용 설정
        $mail->Host= 'smtp.naver.com'; // 네이버 SMTP 서버 주소
        $mail->SMTPAuth= true; // SMTP 인증 사용
        $mail->Username= MAIL_USERNAME; // 메일 발신자의 네이버 이메일 주소, config.php에서 입력
        $mail->Password= MAIL_PASSWORD; // 네이버 앱 비밀번호, 네이버 앱에서 2차 인증키 생성 후 config.php에서 입력 
        $mail->SMTPSecure= PHPMailer::ENCRYPTION_SMTPS; // SSL 암호화 사용 (SMTPS)
        $mail->Port= 465; // SMTPS 포트

        // 2. 발신자/수신자 설정
        $mail->setFrom(MAIL_USERNAME, '마이 피트니스 파트너'); // 발신자 정보
        $mail->addAddress($toEmail); // 수신자 이메일 주소

        // 3. 메일 내용 설정
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true); // 메일 형식: HTML
        
        // 메일 제목
        $mail->Subject = '[마이 피트니스 파트너] 회원가입 인증 코드입니다.';
        
        // 메일 본문 (HTML)
        $mail->Body= "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2>마이 피트니스 파트너 인증코드</h2>
                <p>회원가입을 완료하려면 아래 인증 코드를 입력해주세요.</p>
                <p style='font-size: 24px; font-weight: bold; color: black; letter-spacing: 2px; background: white; padding: 10px 20px; display: inline-block;'>
                    {$code}
                </p>
                <p>인증코드는 5분간 유효합니다</p>
            </div>";

        // 4. 메일 발송
        $mail->send();
        return true; // 발송 성공
        
    
    }
    // 발송 실패 시 예외 처리
    catch (Exception $e) {
        echo "인증 실패(메일 오류): {$mail->ErrorInfo}";
        return false; // 발송 실패
    }
}
