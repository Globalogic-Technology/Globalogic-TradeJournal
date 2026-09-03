CREATE DATABASE IF NOT EXISTS trading_journal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trading_journal;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    initial_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_accounts_user_name(user_id, name),
    KEY idx_accounts_user(user_id),
    KEY idx_accounts_id_user(id, user_id),

    CONSTRAINT fk_accounts_user
        FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE trades (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 account_id BIGINT UNSIGNED NOT NULL,
 ticket VARCHAR(100) NULL,
 symbol VARCHAR(50) NOT NULL,
 side ENUM('long','short') NOT NULL,
 status ENUM('open','closed') NOT NULL DEFAULT 'closed',
 opened_at DATETIME NOT NULL,
 closed_at DATETIME NULL,
 quantity DECIMAL(18,8) NOT NULL,
 entry_price DECIMAL(20,10) NOT NULL,
 stop_loss DECIMAL(20,10) NULL,
 take_profit DECIMAL(20,10) NULL,
 exit_price DECIMAL(20,10) NULL,
 fees DECIMAL(18,8) NOT NULL DEFAULT 0,
 notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_trades_account_ticket(account_id,ticket),
 KEY idx_trades_user_opened(user_id,opened_at),
 KEY idx_trades_account_opened(account_id,opened_at),
 CONSTRAINT fk_trades_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_trades_account_user FOREIGN KEY(account_id,user_id)
   REFERENCES accounts(id,user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
