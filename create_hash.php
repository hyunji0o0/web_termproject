<?php
// 여기 '내비밀번호123' 자리에 실제 사용할 비밀번호를 넣기
$myPassword = 'hanbin77886';

// 비밀번호를 해시값으로 변환
$hashedPassword = password_hash($myPassword, PASSWORD_DEFAULT);

// 화면에 해시값 출력
echo $hashedPassword;
?>