-- Phase 11: P&L goals.
-- Run after Phase 9 migration.
USE trading_journal;

CREATE TABLE IF NOT EXISTS pnl_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    daily_goal DECIMAL(18,2) NOT NULL DEFAULT 0,
    weekly_goal DECIMAL(18,2) NOT NULL DEFAULT 0,
    monthly_goal DECIMAL(18,2) NOT NULL DEFAULT 0,
    yearly_goal DECIMAL(18,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pnl_goal_account(account_id),
    KEY idx_pnl_goals_user(user_id),
    CONSTRAINT fk_pnl_goals_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pnl_goals_account FOREIGN KEY(account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;
