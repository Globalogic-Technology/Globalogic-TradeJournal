-- Trade Journal Phase 12: Pre/Post reviews and reusable goals.
-- Run after migrations 001-012.

USE trading_journal;

CREATE TABLE IF NOT EXISTS review_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    goal_type VARCHAR(40) NOT NULL DEFAULT 'process',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_goal_user_name(user_id,name),
    KEY idx_review_goal_user(user_id),
    CONSTRAINT fk_review_goal_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS period_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    period_type VARCHAR(12) NOT NULL,
    review_type VARCHAR(8) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    scheduled_at DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    responses_json JSON NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_period_review(user_id,period_type,review_type,period_start),
    KEY idx_period_review_user_status(user_id,status,scheduled_at),
    CONSTRAINT fk_period_review_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS period_review_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id BIGINT UNSIGNED NOT NULL,
    goal_id BIGINT UNSIGNED NOT NULL,
    target_value DECIMAL(18,6) NULL,
    target_unit VARCHAR(20) NULL,
    status VARCHAR(12) NOT NULL DEFAULT 'pending',
    achieved_value DECIMAL(18,6) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_period_review_goal(review_id,goal_id),
    KEY idx_period_review_goals_review(review_id),
    CONSTRAINT fk_period_review_goal_review FOREIGN KEY(review_id) REFERENCES period_reviews(id) ON DELETE CASCADE,
    CONSTRAINT fk_period_review_goal_goal FOREIGN KEY(goal_id) REFERENCES review_goals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS review_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'America/Sao_Paulo',
    day_pre_time TIME NOT NULL DEFAULT '08:00:00',
    day_post_time TIME NOT NULL DEFAULT '18:00:00',
    week_pre_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
    week_pre_time TIME NOT NULL DEFAULT '08:00:00',
    week_post_day TINYINT UNSIGNED NOT NULL DEFAULT 5,
    week_post_time TIME NOT NULL DEFAULT '18:00:00',
    month_pre_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
    month_pre_time TIME NOT NULL DEFAULT '08:00:00',
    month_post_time TIME NOT NULL DEFAULT '18:00:00',
    year_pre_month TINYINT UNSIGNED NOT NULL DEFAULT 1,
    year_pre_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
    year_pre_time TIME NOT NULL DEFAULT '08:00:00',
    year_post_month TINYINT UNSIGNED NOT NULL DEFAULT 12,
    year_post_day TINYINT UNSIGNED NOT NULL DEFAULT 31,
    year_post_time TIME NOT NULL DEFAULT '18:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_settings_user(user_id),
    CONSTRAINT fk_review_settings_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed reusable goals for every existing user. CROSS JOIN is intentional:
-- the goal catalog is applied to each user, and the unique key prevents duplicates.
INSERT INTO review_goals(user_id,name,goal_type)
SELECT u.id,v.name,v.goal_type
FROM users u
CROSS JOIN (
    SELECT 'Follow my trading plan' AS name,'process' AS goal_type
    UNION ALL SELECT 'Take only planned setups','process'
    UNION ALL SELECT 'Avoid revenge trading','psychology'
    UNION ALL SELECT 'Avoid FOMO','psychology'
    UNION ALL SELECT 'Avoid overtrading','process'
    UNION ALL SELECT 'Respect my stop loss','risk'
    UNION ALL SELECT 'Do not move my stop loss','risk'
    UNION ALL SELECT 'Respect my maximum number of trades','risk'
    UNION ALL SELECT 'Stop when my loss limit is reached','risk'
    UNION ALL SELECT 'Stay emotionally neutral','psychology'
    UNION ALL SELECT 'Maintain focus','psychology'
    UNION ALL SELECT 'Trade only when mentally prepared','psychology'
    UNION ALL SELECT 'Reach my P&L target','performance'
    UNION ALL SELECT 'Achieve positive R','performance'
) v
ON DUPLICATE KEY UPDATE goal_type=VALUES(goal_type), is_active=1;
