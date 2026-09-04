# Trading Journal — PHP + MySQL

## Phase 1 — Working foundation

PHP 8.2+ / MySQL 8+. Based on the feature set of coderkhalide/Trading-Journal, rebuilt as a server-side PHP/MySQL application.

Includes registration/login/logout, session authentication and CSRF protection, trading accounts, trade CRUD, Long/Short, open/closed trades, duplicate-ticket protection, validation, automatic P&L, dashboard balances, filters/pagination, PDO prepared statements and Apache rewrite support.

## Setup

1. Copy `.env.example` to `.env`.
2. Import `database/schema.sql`.
3. Run the Phase 2 migrations if starting from an existing Phase 1 database.
4. Run `database/migrations/003_phase3_configuration.sql`.
5. Run `database/migrations/004_phase4_advanced_journal.sql`.
6. Run `database/migrations/005_phase6_import_pipeline.sql`.
7. Run `database/migrations/006_phase7_audit_and_hardening.sql`.
8. Run `php -S localhost:8000 -t public`.
9. Open http://localhost:8000.

## Phase 2

Added Exness-style CSV import, JSON backup export and closed-trade analytics.

## Phase 3 — Trading configuration and risk engine

Adds persistent user-owned configuration and trade-level risk calculations.

## Phase 4 — Advanced trade journal

Adds qualitative trade reviews and reusable journal tags.

## Phase 5 — Performance and risk analytics

Adds performance, equity, risk, breakdown and fee-impact analysis while reusing the trade risk service.

## Phase 6 — Import pipeline and data management

Adds CSV preview/validation, import history, duplicate protection and deliberate destructive data-management operations.

## Phase 7 — Security, reliability and audit trail

Phase 7 hardens the web application and adds traceability for sensitive operations.

### Security hardening
- Strict session mode and cookie-only sessions.
- HttpOnly and SameSite session cookies.
- Secure session cookies when HTTPS is detected.
- Content-Security-Policy with a per-request nonce for the inline analytics chart.
- `X-Content-Type-Options: nosniff`.
- `X-Frame-Options: SAMEORIGIN`.
- Strict referrer policy.
- Restrictive Permissions Policy.
- HSTS when served over HTTPS.

### Audit trail
`/audit-log` shows user-owned security/data events, including CSV previews, CSV imports and destructive data-management actions. The audit service is fail-safe: a logging failure is recorded server-side and does not break the user's primary operation.

### Migration
Phase 7 adds `database/migrations/006_phase7_audit_and_hardening.sql`.

## Database migrations

Run migrations in order. Phase 7 requires the earlier schema, configuration, journal and import migrations.
