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
3. Run `php -S localhost:8000 -t public`.
4. Open http://localhost:8000.

Composer is optional in Phase 1 because there are no third-party runtime dependencies.
P&L currently uses price difference × quantity minus fees. Broker-specific contract sizes,
pip values, swaps and FX conversion are intentionally deferred to the next phase.

## Phase 2

Added Exness-style CSV import (5 MB limit, validation and duplicate-ticket skipping), JSON backup export, and closed-trade analytics for win rate, net P&L, profit factor, maximum drawdown and trade-level Sharpe.

The CSV source `profit_usd` is retained in notes for traceability. The journal still calculates P&L from entry/exit, quantity and fees. Broker-specific contract sizes, swaps, pip values and currency conversion remain a later calculation-engine phase.

## Phase 3 — Trading configuration

Phase 3 adds persistent, user-owned configuration for trading systems, strategies, assets, asset fees, trading sessions, risk settings and account configuration.

### New pages
- `/systems` — trading system CRUD: name, description, ideal risk, risk tolerance.
- `/strategies` — strategy CRUD linked to a trading system.
- `/assets` — asset CRUD with JSON configuration for broker/contract-specific metadata.
- `/asset-fees` — fee CRUD linked to an asset, including fee type, amount and currency.
- `/sessions` — trading session CRUD with start/end time and IANA time zone.
- `/risk-settings` — account- or system-scoped ideal risk and risk tolerance.
- `/account-settings` — account balance/currency, default system and account risk configuration.

### Database
Run `database/migrations/003_phase3_configuration.sql` after the existing schema/Phase 2 database changes.

Phase 3 extends accounts with `default_system_id`, `ideal_risk` and `risk_tolerance`, and extends trades with optional system, strategy, asset and session relationships. Existing calculated P&L remains derived from the existing Phase 2 implementation rather than being duplicated.

### Configuration resolution
`App\\Services\\TradingConfigurationService` resolves risk settings from account/system configuration and asset fees. It is intentionally a configuration resolver, not a second calculation engine.

### Apache
Phase 3 routes are handled by `public/phase3.php` and mapped from `.htaccess`. The existing Phase 1/2 front controller remains unchanged.
