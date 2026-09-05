-- Phase 11.3: account-specific CSV import templates.
-- Run after 010_phase11_goals_by_system.sql.
USE trading_journal;

CREATE TABLE IF NOT EXISTS account_csv_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    delimiter_char VARCHAR(1) NOT NULL DEFAULT ',',
    has_header TINYINT(1) NOT NULL DEFAULT 1,
    date_timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    mapping_json JSON NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_account_csv_templates_user(user_id),
    KEY idx_account_csv_templates_account(account_id),
    UNIQUE KEY uq_account_csv_template_name(account_id,name),
    CONSTRAINT fk_account_csv_templates_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_account_csv_templates_account FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
