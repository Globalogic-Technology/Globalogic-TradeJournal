-- Phase 9: original-project feature parity.
-- Run after Phase 8 migration.
USE trading_journal;

ALTER TABLE trades
    ADD COLUMN grade VARCHAR(10) NULL,
    ADD COLUMN timeframe VARCHAR(10) NULL,
    ADD COLUMN screenshot_path VARCHAR(255) NULL;

CREATE INDEX idx_trades_grade ON trades(grade);
CREATE INDEX idx_trades_timeframe ON trades(timeframe);

CREATE TABLE IF NOT EXISTS balance_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    adjusted_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_balance_adjustments_account(account_id,adjusted_at),
    KEY idx_balance_adjustments_user(user_id,adjusted_at),
    CONSTRAINT fk_balance_adjustments_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_balance_adjustments_account FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trade_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trade_tag_user_name(user_id,name),
    KEY idx_trade_tag_user(user_id),
    CONSTRAINT fk_trade_tag_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trade_tag_links (
    trade_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(trade_id,tag_id),
    CONSTRAINT fk_trade_tag_link_trade FOREIGN KEY(trade_id) REFERENCES trades(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_tag_link_tag FOREIGN KEY(tag_id) REFERENCES trade_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;
