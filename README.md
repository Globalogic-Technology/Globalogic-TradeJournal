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

Setup:
1. Copy `.env.example` to `.env`.
2. Import `database/schema.sql`.
3. Run the Phase 2 migrations if starting from an existing Phase 1 database.
4. Run `database/migrations/003_phase3_configuration.sql`.
5. Run `php -S localhost:8000 -t public`.
6. Open http://localhost:8000.

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
- `/assets` — asset CRUD with JSON broker/contract configuration.
- `/asset-fees` — fee configuration per asset.
- `/sessions` — trading sessions with start/end time and IANA time zone.
- `/risk-settings` — account- or system-scoped risk overrides.
- `/account-settings` — account default system and account risk configuration.

### Trade integration
New trades inherit the selected account's default trading system when no system is explicitly selected. An asset is automatically matched by symbol when possible. Strategy ownership and system/strategy consistency are validated server-side.

### Risk engine
`App\\Services\\TradeRiskService` is the dedicated calculation service. It does not duplicate the existing P&L formula.

For a trade with a stop loss:
- Actual Risk = `abs(Entry - Stop Loss) × Quantity × Contract Size × Point Value`
- Risk % = `Actual Risk / Balance Before × 100`
- Position Size = `Ideal Risk / Risk Per Unit`
- Expected R = `Net P&L / Ideal Risk`
- R Multiple = `Net P&L / Actual Risk`
- Risk Deviation = `((Actual Risk - Ideal Risk) / Ideal Risk) × 100`
- Balance After = `Balance Before + Net P&L`

The risk configuration is resolved from account/system settings through `TradingConfigurationService`. Asset configuration defaults to a multiplier of `1` when contract/point values are not supplied.

### Database
Run `database/migrations/003_phase3_configuration.sql` after the existing schema and Phase 2 migrations. The migration extends accounts and trades with Phase 3 relationships/configuration.

### Apache
Phase 3 routes are handled by `public/phase3.php` and mapped from `.htaccess`. `/trades` now uses the Phase 3 trade workflow while the legacy front controller remains available for the other Phase 1/2 routes.
