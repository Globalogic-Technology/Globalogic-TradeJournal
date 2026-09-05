-- Phase 11.1: allow P&L goals at account level and trading-system level.
-- Run after 009_phase11_goals.sql.
USE trading_journal;

ALTER TABLE pnl_goals
    ADD COLUMN trading_system_id BIGINT UNSIGNED NULL AFTER account_id,
    DROP INDEX uq_pnl_goal_account,
    ADD UNIQUE KEY uq_pnl_goal_account_system(account_id, trading_system_id),
    ADD KEY idx_pnl_goals_system(trading_system_id),
    ADD CONSTRAINT fk_pnl_goals_system FOREIGN KEY(trading_system_id) REFERENCES trading_systems(id) ON DELETE CASCADE;
