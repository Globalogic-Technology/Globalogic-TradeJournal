-- Phase 12.1: skip reviews for Day Off, PTO, personal events, or other no-trading periods.
-- Run after migration 013.
USE trading_journal;

CREATE TABLE IF NOT EXISTS review_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    period_type VARCHAR(12) NOT NULL DEFAULT 'day',
    period_start DATE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_exception(user_id,period_type,period_start),
    KEY idx_review_exception_user(user_id,period_start),
    CONSTRAINT fk_review_exception_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
