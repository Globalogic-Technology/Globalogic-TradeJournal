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
6. Run `php -S localhost:8000 -t public`.
7. Open http://localhost:8000.

Composer is optional because the current application has no required third-party runtime dependencies.

P&L uses price difference × quantity minus fees. Broker-specific contract sizes and point values can be supplied through the Phase 3 asset JSON configuration.

## Phase 2

Added Exness-style CSV import (5 MB limit, validation and duplicate-ticket skipping), JSON backup export, and closed-trade analytics for win rate, net P&L, profit factor, maximum drawdown and trade-level Sharpe.

The CSV source `profit_usd` is retained in notes for traceability. The journal still calculates P&L from entry/exit, quantity and fees.

## Phase 3 — Trading configuration and risk engine

Phase 3 is complete on the `phase-3` branch. It adds persistent, user-owned configuration and makes that configuration drive trade-level risk calculations.

### Configuration pages
- `/systems` — trading system CRUD with ideal risk and risk tolerance.
- `/strategies` — strategy CRUD linked to a trading system.
- `/assets` — asset configuration and broker/contract metadata.
- `/asset-fees` — optional fee configuration per asset.
- `/sessions` — trading sessions with start/end time and IANA time zone.
- `/risk-settings` — account- or system-scoped risk overrides.
- `/account-settings` — account default system and account risk configuration.

### Risk engine
`App\\Services\\TradeRiskService` is the dedicated calculation service and does not duplicate the existing P&L formula.

For a trade with a stop loss:
- Actual Risk = `abs(Entry - Stop Loss) × Quantity × Contract Size × Point Value`
- Risk % = `Actual Risk / Balance Before × 100`
- Position Size = `Ideal Risk / Risk Per Unit`
- Expected R = `Net P&L / Ideal Risk`
- R Multiple = `Net P&L / Actual Risk`
- Risk Deviation = `((Actual Risk - Ideal Risk) / Ideal Risk) × 100`
- Balance After = `Balance Before + Net P&L`

## Phase 4 — Advanced trade journal

Phase 4 adds the qualitative journal layer without duplicating the existing trade fields.

### Trade review
Each trade can now have a dedicated journal review containing:
- Setup / pattern
- Market context
- Pre-trade thesis
- Entry reason
- Exit reason
- Emotion before and after the trade
- Confidence score (1–5)
- Execution quality score (1–5)
- Discipline score (1–5)
- Mistakes
- What went well
- Lessons learned
- What to change next time
- Review timestamp

### Tags
Trades can be tagged with reusable, user-owned labels such as `breakout`, `FOMO`, `A+ setup`, or `news`.

### Data model
- `trade_journals` stores one journal review per trade.
- `journal_tags` stores user-owned tags.
- `trade_journal_tags` provides the many-to-many relationship between reviews and tags.

### Trade workflow
The Trades page now exposes a **Review** action. The review is protected by the same authenticated session and CSRF protection as the rest of the application. A journal record is created on first save and updated thereafter.

For PHP's built-in development server, the review is also available as `/trades?journal=TRADE_ID`, avoiding dependence on Apache rewrite rules.

## Database migrations

Run migrations in order. Phase 4 requires Phase 3 because journal records reference existing trades and users.
