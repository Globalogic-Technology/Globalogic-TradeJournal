-- Phase 11.4: smart CSV import modes for broker order exports.
-- Run after 011_phase11_account_csv_templates.sql.
USE trading_journal;

ALTER TABLE account_csv_templates
    ADD COLUMN import_mode VARCHAR(40) NOT NULL DEFAULT 'standard' AFTER name,
    ADD KEY idx_account_csv_templates_mode(import_mode);
