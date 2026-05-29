-- =====================================================================
-- Dogecoin Faucet - Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.2+
-- Import this file via phpMyAdmin or MySQL CLI on your cPanel hosting.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(32) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `faucetpay_email` VARCHAR(120) NOT NULL,
  `referrer_id` INT UNSIGNED NULL DEFAULT NULL,
  `balance` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `referral_balance` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `total_earned` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `total_claims` INT UNSIGNED NOT NULL DEFAULT 0,
  `signup_ip` VARCHAR(45) NOT NULL,
  `last_ip` VARCHAR(45) DEFAULT NULL,
  `status` ENUM('active','banned','pending') NOT NULL DEFAULT 'active',
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verify_token` VARCHAR(64) DEFAULT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CLAIMS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `claims` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(18,8) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'DOGE',
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `payout_status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `payout_txid` VARCHAR(120) DEFAULT NULL,
  `payout_response` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- WITHDRAWALS (FaucetPay payouts)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(32) NOT NULL,
  `faucetpay_email` VARCHAR(120) NOT NULL,
  `amount` DECIMAL(18,8) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'DOGE',
  `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `txid` VARCHAR(120) DEFAULT NULL,
  `response` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REFERRAL EARNINGS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referral_earnings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id` INT UNSIGNED NOT NULL,
  `referred_id` INT UNSIGNED NOT NULL,
  `claim_id` BIGINT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(18,8) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_referred` (`referred_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- REFERRAL COMPETITIONS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referral_contests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `prize_pool` VARCHAR(255) DEFAULT NULL,
  `prizes_json` TEXT,
  `start_at` DATETIME NOT NULL,
  `end_at` DATETIME NOT NULL,
  `status` ENUM('upcoming','active','ended','archived') NOT NULL DEFAULT 'upcoming',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ADVERTISEMENTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `placement` ENUM('header','sidebar','dashboard','popup','footer','between') NOT NULL,
  `code` MEDIUMTEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_placement_active` (`placement`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SPONSOR LINKS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sponsor_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(500) NOT NULL,
  `reward` DECIMAL(18,8) NOT NULL DEFAULT 0,
  `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_on_home` TINYINT(1) NOT NULL DEFAULT 1,
  `display_on_dashboard` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SPONSOR CLICK TRACKING (per IP/user, anti-abuse)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sponsor_clicks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sponsor_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sponsor` (`sponsor_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SETTINGS (key/value)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `name` VARCHAR(80) NOT NULL,
  `value` TEXT,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- LOGS (general activity / fraud / failed logins)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(40) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `message` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- LOGIN ATTEMPTS (for brute force lockout)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(120) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_id_ip` (`identifier`,`ip_address`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- IP BLACKLIST
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- FAQ
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faq` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ANNOUNCEMENTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CONTACT MESSAGES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DEFAULT SETTINGS
-- ---------------------------------------------------------------------
INSERT INTO `settings` (`name`, `value`) VALUES
('site_name','Doge Faucet'),
('site_url','https://example.com'),
('site_tagline','Claim free Dogecoin every 5 minutes'),
('site_description','Free Dogecoin faucet with FaucetPay instant withdrawal'),
('admin_email','admin@example.com'),
('faucetpay_api_key',''),
('faucetpay_currency','DOGE'),
('claim_amount','0.0005'),
('claim_interval_seconds','300'),
('daily_claim_limit','200'),
('referral_percent','20'),
('faucet_enabled','1'),
('maintenance_mode','0'),
('one_account_per_ip','1'),
('one_claim_per_ip','1'),
('recaptcha_enabled','0'),
('recaptcha_site_key',''),
('recaptcha_secret_key',''),
('vpn_check_enabled','1'),
('homepage_text','Welcome to our Dogecoin faucet. Claim every 5 minutes and earn free DOGE.'),
('terms_text','Terms and conditions.'),
('privacy_text','Privacy policy.'),
('announcement_text',''),
('announcement_active','0'),
('cookie_notice_enabled','1'),
('analytics_code',''),
('login_max_attempts','5'),
('login_lockout_minutes','15');

-- ---------------------------------------------------------------------
-- DEFAULT ADMIN USER
-- Username: admin
-- Password: admin123  (CHANGE IT IMMEDIATELY AFTER LOGIN)
-- The hash below corresponds to "admin123".
-- ---------------------------------------------------------------------
INSERT INTO `users`
(`username`,`email`,`password_hash`,`faucetpay_email`,`signup_ip`,`status`,`is_admin`,`email_verified`)
VALUES
('admin','admin@example.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin@example.com','127.0.0.1','active',1,1);

-- ---------------------------------------------------------------------
-- SAMPLE FAQ
-- ---------------------------------------------------------------------
INSERT INTO `faq` (`question`,`answer`,`sort_order`,`is_active`) VALUES
('What is a Dogecoin faucet?','A faucet is a website that gives away small amounts of cryptocurrency to users for free. You can claim DOGE every few minutes.',1,1),
('How do I get paid?','We pay instantly through FaucetPay. Just register at faucetpay.io and add your FaucetPay email when you sign up.',2,1),
('Is there a minimum withdrawal?','No. Every claim is sent instantly to your FaucetPay account.',3,1),
('How does the referral system work?','Share your referral link. When your referrals claim, you earn a percentage of every claim they make for life.',4,1);

SET FOREIGN_KEY_CHECKS = 1;
