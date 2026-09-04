-- Trade Journal Phase 6: import pipeline and data management.
USE trading_journal;

CREATE TABLE IF NOT EXISTS trade_imports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'csv',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
    skipped_rows INT UNSIGNED NOT NULL DEFAULT 0,
    error_rows INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('previewed','completed','failed') NOT NULL DEFAULT 'completed',
    error_summary TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_trade_import_user_created(user_id, created_at),
    CONSTRAINT fk_trade_import_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_import_account FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
