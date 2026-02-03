-- EquityMirror schema suitable for importing into an existing database
-- (Remove CREATE DATABASE/USE lines so you can import into your Hostinger DB)

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
