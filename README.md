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
