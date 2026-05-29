-- =====================================================================
-- Migration v1 -> v2 : FaucetPay-email + PIN authentication
-- Run ONLY if you previously installed v1 (with separate username/email/
-- password). For fresh installs use schema.sql instead.
-- =====================================================================

SET NAMES utf8mb4;

-- 1. Rename password_hash to pin_hash
ALTER TABLE `users` CHANGE COLUMN `password_hash` `pin_hash` VARCHAR(255) NOT NULL;

-- 2. Drop the separate site email column (FaucetPay email is now primary)
ALTER TABLE `users` DROP COLUMN `email`;

-- 3. Drop email_verified / verify_token (no longer used)
ALTER TABLE `users` DROP COLUMN `email_verified`;
ALTER TABLE `users` DROP COLUMN `verify_token`;

-- 4. Add unique key on faucetpay_email (now used for login)
ALTER TABLE `users` ADD UNIQUE KEY `uniq_faucetpay_email` (`faucetpay_email`);

-- 5. Insert pin_length setting if missing
INSERT IGNORE INTO `settings` (`name`,`value`) VALUES ('pin_length','6');

-- 6. Reset all PINs to 123456 (so existing users can still log in).
--    Each user MUST then change their PIN from the profile page.
UPDATE `users` SET `pin_hash` = '$2y$10$Kg4ISzuaGHmSN/YzY71yc.8VV0y24Zwhzi3NsI9CW7MMk1kHIvRa6';
