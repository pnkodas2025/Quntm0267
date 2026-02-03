-- EquityMirror combined import file
-- Import this into your existing database (select the DB in phpMyAdmin first)
-- This file contains table creation and demo data, and does NOT attempt to CREATE DATABASE or USE it.

-- --- SCHEMA ---
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('master','follower') NOT NULL DEFAULT 'follower',
  account_id VARCHAR(50) DEFAULT NULL,
  account_name VARCHAR(255) DEFAULT NULL,
  telegram_id VARCHAR(100) DEFAULT NULL,
  balance DECIMAL(15,2) DEFAULT 0,
  lots INT DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Disconnected',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS risk_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT DEFAULT NULL,
  is_global TINYINT(1) DEFAULT 0,
  lot_multiplier DECIMAL(6,3) DEFAULT 1.000,
  daily_loss_limit DECIMAL(12,2) DEFAULT 0,
  max_exposure_per_symbol DECIMAL(12,2) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  symbol VARCHAR(20),
  type ENUM('Buy','Sell') DEFAULT 'Buy',
  order_type VARCHAR(50) DEFAULT 'Market',
  quantity INT DEFAULT 0,
  price DECIMAL(12,4) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50),
  message TEXT,
  meta TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --- DATA ---
SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE `users`;
INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES ('1','master','$2y$10$ECoFkte9C0aBdm0preDTi.L/MHpp8WllJLtkPiZbaKKHNP8OMwlSG','master','ALICEBLUE-1','Master Account',NULL,'125430.50',NULL,'Connected','2026-02-03 19:59:27');
INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES ('2','john','$2y$10$dlDDEE2nnKFGsRT2irHxLeEpvLCMeMRLHpV4.LUDtCpaI8ARmcHOm','follower','FG-456','John\'s Growth','@john','25000.00',NULL,'Connected','2026-02-03 19:59:27');
INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES ('3','sarah','$2y$10$qlxIFDyLGbjhFrK4r9uMzejAtM//TcvvWi88C.fu4FiROhmngIyYK','follower','HJ-789','Sarah\'s Portfolio','@sarah','50000.00',NULL,'Disconnected','2026-02-03 19:59:27');
INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES ('4','retire','$2y$10$fEBT.wPb7bCXnKPdw9manujmbzYVtUiPbQgryVUJUPfDQVpG9y5Na','follower','KL-101','Retirement Fund','@retire','150000.00',NULL,'Disconnected','2026-02-03 19:59:27');
INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES ('5','aggressive','$2y$10$DjouTVhdIgBOUzTkS3UFSu/UZtNAaM50KCTpqUehNGp2MpDUc2Mp6','follower','MN-212','Aggressive Bets','@aggressive','10000.00',NULL,'Connected','2026-02-03 19:59:27');
INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES ('6','test','$2y$10$nhoca0uMG8Xgk6Opb5EUreRRKxYcdM1fLjutlGhPBKj6a72usUtyG','follower','OP-313','Test Account','@test','5000.00',NULL,'Error','2026-02-03 19:59:27');

TRUNCATE TABLE `risk_settings`;
INSERT INTO `risk_settings` (`id`,`account_id`,`is_global`,`lot_multiplier`,`daily_loss_limit`,`max_exposure_per_symbol`,`created_at`) VALUES ('1',NULL,'1','1.000','5000.00','10000.00','2026-02-03 19:59:27');
INSERT INTO `risk_settings` (`id`,`account_id`,`is_global`,`lot_multiplier`,`daily_loss_limit`,`max_exposure_per_symbol`,`created_at`) VALUES ('2','2','0','1.000','2000.00','5000.00','2026-02-03 19:59:27');

TRUNCATE TABLE `trades`;
INSERT INTO `trades` (`id`,`account_id`,`symbol`,`type`,`order_type`,`quantity`,`price`,`created_at`) VALUES ('1','2','AAPL','Buy','Limit','100','172.2500','2026-02-03 19:59:27');
INSERT INTO `trades` (`id`,`account_id`,`symbol`,`type`,`order_type`,`quantity`,`price`,`created_at`) VALUES ('2','3','GOOGL','Sell','Market','50','135.5000','2026-02-03 19:59:27');
INSERT INTO `trades` (`id`,`account_id`,`symbol`,`type`,`order_type`,`quantity`,`price`,`created_at`) VALUES ('3','4','TSLA','Buy','Stop','200','245.0000','2026-02-03 19:59:27');

TRUNCATE TABLE `logs`;
INSERT INTO `logs` (`id`,`type`,`message`,`meta`,`created_at`) VALUES ('1','system','Database seeded','{"by":"codespaces-66d8de"}','2026-02-03 19:59:27');
INSERT INTO `logs` (`id`,`type`,`message`,`meta`,`created_at`) VALUES ('2','trade','Master executed BUY AAPL','{"symbol":"AAPL","qty":100}','2026-02-03 19:59:27');

SET FOREIGN_KEY_CHECKS=1;
