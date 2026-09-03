-- Trade Journal Phase 4: advanced journal and trade review.
-- Run after database/schema.sql and migrations 001-003.

USE trading_journal;

CREATE TABLE IF NOT EXISTS trade_journals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    trade_id BIGINT UNSIGNED NOT NULL,
    setup VARCHAR(160) NULL,
    market_context TEXT NULL,
    thesis TEXT NULL,
    entry_reason TEXT NULL,
    exit_reason TEXT NULL,
    emotion_before VARCHAR(80) NULL,
    emotion_after VARCHAR(80) NULL,
    confidence TINYINT UNSIGNED NULL,
    execution_quality TINYINT UNSIGNED NULL,
    discipline_score TINYINT UNSIGNED NULL,
    mistakes TEXT NULL,
    lessons TEXT NULL,
    what_went_well TEXT NULL,
    what_to_change TEXT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trade_journal_trade(trade_id),
    KEY idx_trade_journal_user(user_id),
    CONSTRAINT fk_trade_journal_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_journal_trade FOREIGN KEY(trade_id) REFERENCES trades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS journal_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_journal_tag_user_name(user_id,name),
    KEY idx_journal_tag_user(user_id),
    CONSTRAINT fk_journal_tag_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trade_journal_tags (
    journal_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(journal_id,tag_id),
    CONSTRAINT fk_trade_journal_tag_journal FOREIGN KEY(journal_id) REFERENCES trade_journals(id) ON DELETE CASCADE,
    CONSTRAINT fk_trade_journal_tag_tag FOREIGN KEY(tag_id) REFERENCES journal_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;
