# Trading Journal — PHP + MySQL

## Phase 1 — Working foundation

PHP 8.2+ / MySQL 8+. Based on the feature set of coderkhalide/Trading-Journal, rebuilt as a server-side PHP/MySQL application.

Includes:
- Registration/login/logout
- Session authentication and CSRF protection
- Trading accounts
- Add/edit/delete trades
- Long/short and open/closed trades
- Ticket number and duplicate-ticket protection per account
- Server-side validation
- Automatic P&L
- Dashboard balances and summary
- Trade filtering and pagination
- PDO prepared statements
- Apache rewrite support

## Setup

1. Copy `.env.example` to `.env`.
2. Import `database/schema.sql`.
3. Run the Phase 2 migrations if starting from an existing Phase 1 database.
4. Run `database/migrations/003_phase3_configuration.sql`.
5. Run `database/migrations/004_phase4_advanced_journal.sql`.
6. Run `database/migrations/005_phase6_import_pipeline.sql`.
7. Run `php -S localhost:8000 -t public`.
8. Open http://localhost:8000.

Composer is optional because the current application has no required third-party runtime dependencies.

P&L uses price difference × quantity minus fees. Broker-specific contract sizes and point values can be supplied through the Phase 3 asset JSON configuration.

## Phase 2

Added Exness-style CSV import, JSON backup export and closed-trade analytics.

## Phase 3 — Trading configuration and risk engine

Phase 3 adds persistent user-owned configuration and makes it drive trade-level risk calculations.

## Phase 4 — Advanced trade journal

Phase 4 adds qualitative trade reviews and reusable journal tags.

## Phase 5 — Performance and risk analytics

Phase 5 adds a dedicated analytics dashboard with performance, equity, risk, breakdown and fee-impact analysis. Analytics reuse `TradeRiskService` and existing P&L calculations.

## Phase 6 — Import pipeline and data management

Phase 6 makes CSV ingestion safer and more operationally useful.

### CSV workflow
- Preview a CSV before importing it.
- Validate required columns and row values.
- Inspect up to 1,000 rows during preview without writing trades.
- Import valid rows with duplicate-ticket protection.
- Keep broker `profit_usd` and close reason in trade notes for traceability.
- Record every completed import in `trade_imports`.
- Show total, imported, skipped and error counts.

### Import history
`/imports` provides the last 100 import records with filename, account, status and row counts.

### Data management
`/data-management` provides deliberately destructive operations for deleting all of a user's trades or import history. Both operations require CSRF protection and typing `DELETE` as confirmation.

### Migration
Phase 6 adds `database/migrations/005_phase6_import_pipeline.sql` and does not alter existing trade rows automatically.

## Database migrations

Run migrations in order. Phase 6 requires the earlier schema/configuration/journal migrations because import history references users and accounts.
