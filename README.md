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

Phase 3 adds persistent user-owned configuration and makes it drive trade-level risk calculations.

### Configuration pages
- `/systems` — trading system CRUD with ideal risk and risk tolerance.
- `/strategies` — strategy CRUD linked to a trading system.
- `/assets` — asset configuration and broker/contract metadata.
- `/asset-fees` — optional fee configuration per asset.
- `/sessions` — trading sessions with start/end time and IANA time zone.
- `/risk-settings` — account- or system-scoped risk overrides.
- `/account-settings` — account default system and account risk configuration.

### Risk engine
`App\\Services\\TradeRiskService` is the dedicated calculation service.

For a trade with a stop loss:
- Actual Risk = `abs(Entry - Stop Loss) × Quantity × Contract Size × Point Value`
- Risk % = `Actual Risk / Balance Before × 100`
- Position Size = `Ideal Risk / Risk Per Unit`
- Expected R = `Net P&L / Ideal Risk`
- R Multiple = `Net P&L / Actual Risk`
- Risk Deviation = `((Actual Risk - Ideal Risk) / Ideal Risk) × 100`
- Balance After = `Balance Before + Net P&L`

## Phase 4 — Advanced trade journal

Phase 4 adds the qualitative journal layer without duplicating existing trade fields.

### Trade review
Each trade can have a dedicated journal review containing setup/pattern, market context, thesis, entry/exit reason, emotions, confidence, execution quality, discipline, mistakes, lessons, what went well and what to change.

### Tags
Trades can be tagged with reusable user-owned labels.

### Data model
- `trade_journals` stores one journal review per trade.
- `journal_tags` stores user-owned tags.
- `trade_journal_tags` provides the many-to-many relationship.

## Phase 5 — Performance and risk analytics

Phase 5 turns the existing trade and risk data into a dedicated analytics dashboard.

### Dashboard metrics
- Closed trades, wins, losses and breakevens
- Win rate
- Net P&L, gross profit and gross loss
- Profit factor
- Average win/loss and expectancy per trade
- Maximum drawdown
- Trade-level Sharpe using realized Expected R values
- Total and average fees
- Cumulative equity curve

### Breakdown analysis
Performance can be filtered by date range, trading system and trading session, then compared by:
- Trading system
- Strategy
- Trading session
- Day of week

Each breakdown includes trade count, win rate, net P&L, average R, average risk deviation and fees.

### Risk analysis
The dashboard summarizes average ideal risk, average actual risk, average risk deviation, over-risk trades and average R multiple.

### Fee impact
Gross P&L, total fees, net P&L and average fee per trade are shown together so trading costs can be evaluated separately from execution performance.

The Phase 5 analytics service reuses `TradeRiskService` for risk metrics and the application's existing P&L formula for realized results.

## Database migrations

Run migrations in order. Phase 4 requires Phase 3 because journal records reference existing trades. Phase 5 does not add a new database table; analytics are derived from existing trade, account, configuration and journal data.
