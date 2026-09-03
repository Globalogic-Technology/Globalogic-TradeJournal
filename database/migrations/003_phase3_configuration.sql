-- Trade Journal Phase 3: trading configuration and risk settings.
-- Run after database/schema.sql and the Phase 2 changes.

USE trading_journal;

CREATE TABLE IF NOT EXISTS trading_systems (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    ideal_risk DECIMAL(18,2) NOT NULL DEFAULT 0,
    risk_tolerance DECIMAL(8,4) NOT NULL DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trading_system_user_name(user_id, name),
    KEY idx_trading_system_user(user_id),
    CONSTRAINT fk_trading_system_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS strategies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    trading_system_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_strategy_system_name(trading_system_id, name),
    KEY idx_strategy_user(user_id),
    CONSTRAINT fk_strategy_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_strategy_system FOREIGN KEY(trading_system_id) REFERENCES trading_systems(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    symbol VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    configuration JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asset_user_symbol(user_id, symbol),
    KEY idx_asset_user(user_id),
    CONSTRAINT fk_asset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asset_fees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    fee_type VARCHAR(50) NOT NULL,
    fee_amount DECIMAL(18,8) NOT NULL DEFAULT 0,
    fee_currency CHAR(3) NOT NULL DEFAULT 'USD',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asset_fee(asset_id, fee_type),
    KEY idx_asset_fee_user(user_id),
    CONSTRAINT fk_asset_fee_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_asset_fee_asset FOREIGN KEY(asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trading_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trading_session_user_name(user_id, name),
    KEY idx_trading_session_user(user_id),
    CONSTRAINT fk_trading_session_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS risk_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NULL,
    trading_system_id BIGINT UNSIGNED NULL,
    ideal_risk DECIMAL(18,2) NOT NULL DEFAULT 0,
    risk_tolerance DECIMAL(8,4) NOT NULL DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_risk_settings_user(user_id),
    KEY idx_risk_settings_account(account_id),
    KEY idx_risk_settings_system(trading_system_id),
    CONSTRAINT fk_risk_setting_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_risk_setting_account FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_risk_setting_system FOREIGN KEY(trading_system_id) REFERENCES trading_systems(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE accounts
    ADD COLUMN default_system_id BIGINT UNSIGNED NULL,
    ADD COLUMN ideal_risk DECIMAL(18,2) NOT NULL DEFAULT 0,
    ADD COLUMN risk_tolerance DECIMAL(8,4) NOT NULL DEFAULT 10;

ALTER TABLE accounts
    ADD KEY idx_accounts_default_system(default_system_id),
    ADD CONSTRAINT fk_accounts_default_system FOREIGN KEY(default_system_id)
        REFERENCES trading_systems(id) ON DELETE SET NULL;

ALTER TABLE trades
    ADD COLUMN trading_system_id BIGINT UNSIGNED NULL,
    ADD COLUMN strategy_id BIGINT UNSIGNED NULL,
    ADD COLUMN asset_id BIGINT UNSIGNED NULL,
    ADD COLUMN trading_session_id BIGINT UNSIGNED NULL;

ALTER TABLE trades
    ADD KEY idx_trades_system(trading_system_id),
    ADD KEY idx_trades_strategy(strategy_id),
    ADD KEY idx_trades_asset(asset_id),
    ADD KEY idx_trades_session(trading_session_id),
    ADD CONSTRAINT fk_trades_system FOREIGN KEY(trading_system_id) REFERENCES trading_systems(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_trades_strategy FOREIGN KEY(strategy_id) REFERENCES strategies(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_trades_asset FOREIGN KEY(asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_trades_session FOREIGN KEY(trading_session_id) REFERENCES trading_sessions(id) ON DELETE SET NULL;

-- Keep the current Phase 2 P&L engine authoritative. These columns are configuration/relationships;
-- calculated P&L, risk, Expected R and risk deviation remain derived values.
