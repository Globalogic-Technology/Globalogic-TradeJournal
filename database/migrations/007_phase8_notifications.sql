-- Trade Journal Phase 8: alerts, reminders and review workflow.
-- Run after migrations 001-006.
USE trading_journal;

CREATE TABLE IF NOT EXISTS user_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(160) NOT NULL,
    message VARCHAR(500) NOT NULL,
    entity_type VARCHAR(60) NULL,
    entity_id BIGINT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    KEY idx_notification_user_read(user_id,is_read,created_at),
    CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS journal_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    trade_id BIGINT UNSIGNED NOT NULL,
    review_status ENUM('pending','reviewed','needs_followup') NOT NULL DEFAULT 'pending',
    review_due_at DATETIME NULL,
    review_note TEXT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_journal_review_trade(trade_id),
    KEY idx_journal_review_user_status(user_id,review_status,review_due_at),
    CONSTRAINT fk_journal_review_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_journal_review_trade FOREIGN KEY(trade_id) REFERENCES trades(id) ON DELETE CASCADE
) ENGINE=InnoDB;
