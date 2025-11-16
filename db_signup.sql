-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- 생성 시간: 25-11-16 09:28
-- 서버 버전: 10.4.32-MariaDB
-- PHP 버전: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `db_signup`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL COMMENT '사용자 고유 ID',
  `email` varchar(255) NOT NULL COMMENT '네이버 이메일 (로그인 ID)',
  `password` varchar(255) NOT NULL COMMENT '비밀번호',
  `name` varchar(100) DEFAULT NULL COMMENT '사용자 이름',
  `height` decimal(5,2) DEFAULT NULL COMMENT '키 (cm)',
  `weight` decimal(5,2) DEFAULT NULL COMMENT '몸무게 (kg)',
  `age` int(11) DEFAULT NULL COMMENT '나이',
  `gender` enum('male','female','other') DEFAULT NULL COMMENT '성별',
  `bmr` float DEFAULT NULL COMMENT '기초대사량',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT '이메일 인증 여부',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '가입일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='마이 피트니스 파트너 사용자 정보';

-- --------------------------------------------------------

--
-- 테이블 구조 `verification_codes`
--

CREATE TABLE `verification_codes` (
  `email` varchar(255) NOT NULL COMMENT '인증 대상 이메일',
  `code` varchar(6) NOT NULL COMMENT '6자리 인증 코드',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '코드 만료 시간'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='이메일 인증 코드 및 만료 시간 관리';

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 테이블의 인덱스 `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`email`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '사용자 고유 ID', AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
