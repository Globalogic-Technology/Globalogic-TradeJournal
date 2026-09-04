# Trading Journal — PHP + MySQL

A server-side PHP + MySQL trading journal based on the feature set and workflow of https://github.com/coderkhalide/Trading-Journal. The project progressively evolves from a basic trade journal into a configurable risk-management, analytics, import, audit, review and feature-parity platform.

## Technology

- PHP 8.2+
- MySQL 8+
- PDO with prepared statements
- Server-rendered PHP views
- Apache rewrite support
- PHP built-in server compatible route shims
- Chart.js for analytics visualization

## Project flow

```text
Phase 1  Foundation
   ↓
Phase 2  Import / Backup / Basic Analytics
   ↓
Phase 3  Trading Configuration + Risk Engine
   ↓
Phase 4  Advanced Trade Journal
   ↓
Phase 5  Performance + Risk Analytics
   ↓
Phase 6  Import Pipeline + Data Management
   ↓
Phase 7  Security + Reliability + Audit Trail
   ↓
Phase 8  Review Workflow + Notifications
   ↓
Phase 9  Original Feature Parity
```

## Phase 1 — Working foundation

- User registration and authentication.
- Accounts and account balances.
- Trade CRUD.
- Long/Short and Open/Closed status.
- Duplicate ticket protection.
- Server-side validation and CSRF protection.
- Automatic P&L.
- Dashboard, filtering and pagination.
- Apache and PHP built-in server routing compatibility.

## Phase 2 — Import, backup and basic analytics

- Exness-style CSV import.
- JSON backup/export.
- Closed-trade analytics.
- Initial performance calculations.

## Phase 3 — Trading configuration and risk engine

- Trading systems.
- Strategies.
- Assets and asset configuration.
- Asset fees.
- Trading sessions and time zones.
- Risk settings.
- Account configuration and default systems.
- Ideal risk, actual risk, risk %, position size, Expected R, R Multiple, risk deviation and balance calculations.
- Contract-size and point-value support.

**Expected R** is the normalized realized result: `Net P&L / Ideal Risk`.

## Phase 4 — Advanced trade journal

- Setup and market context.
- Thesis and entry/exit reasons.
- Emotions and confidence.
- Execution quality and discipline scores.
- Mistakes, lessons, what went well and what to change.
- Review timestamps.
- Journal tags.

## Phase 5 — Performance and risk analytics

- Win rate.
- Net P&L.
- Profit factor.
- Expectancy per trade.
- Maximum drawdown.
- Trade Sharpe.
- Total fees and fee impact.
- Equity curve.
- Average ideal/actual risk.
- Risk deviation and over-risk trades.
- Average R Multiple.
- System, strategy, session and day-of-week breakdowns.

## Phase 6 — Import pipeline and data management

- CSV preview and validation.
- Duplicate detection.
- Import result reporting.
- Import history.
- Error summaries.
- Destructive-operation confirmation.
- Delete all trades.
- Delete import history.
- PHP 8.4-compatible `fgetcsv()` usage.

## Phase 7 — Security, reliability and audit trail

- Strict sessions and secure cookie settings.
- CSRF protection.
- Content Security Policy with nonces.
- Security headers and HSTS on HTTPS.
- User-owned queries.
- Audit log.
- Auditing for CSV preview/import and destructive data operations.

## Phase 8 — Review workflow and notifications

- Review queue.
- Pending/reviewed/needs-follow-up states.
- Review due dates and notes.
- Automatic review creation for closed trades.
- In-app notifications.
- Mark notifications as read.

## Phase 9 — Original feature parity

Phase 9 closes the major functionality gaps identified by comparing the PHP application with the original Trading Journal project.

### 9.1 Trade grading

The trade model now supports the original grade scale:

```text
A++++  A+++  A++  A+  A  B  C  D  E  F
```

Each grade has a risk multiplier:

| Grade | Multiplier |
|---|---:|
| A++++ | 2.50x |
| A+++ | 2.00x |
| A++ | 1.25x |
| A+ | 1.00x |
| A | 0.80x |
| B | 0.50x |
| C | 0.30x |
| D | 0.10x |
| E | 0.05x |
| F | 0.01x |

### 9.2 Grade-adjusted risk

```text
Base Ideal Risk
      ↓
Grade multiplier
      ↓
Grade-adjusted Ideal Risk
      ↓
Actual Risk / Position Size / Expected R
```

The trade form displays base ideal risk, adjusted ideal risk and the multiplier.

### 9.3 Ideal stop-loss calculator

The Trade form now includes **Calculate Ideal Stop Loss**. It calculates a stop level from entry, direction, quantity, effective ideal risk, fees and asset contract/point configuration.

### 9.4 Timeframes

Supported timeframes:

`1m`, `5m`, `15m`, `30m`, `1h`, `4h`, `1d`, `1w`

### 9.5 Advanced trade filtering

Trade History now supports filters for:

- Symbol.
- Side.
- Status.
- Trading system.
- Grade.
- Timeframe.
- Outcome.
- Date range.
- Minimum/maximum P&L.
- Minimum/maximum Expected R.

### 9.6 Bulk updates

The `/bulk-trades` workflow supports selecting multiple trades and updating:

- Trading system.
- Strategy.
- Session.
- Grade.
- Timeframe.

### 9.7 JSON restore

The `/json-import` workflow provides:

```text
JSON backup
    ↓
Upload
    ↓
Validate / preview
    ↓
Detect duplicate tickets
    ↓
Restore trades
```

### 9.8 Balance adjustments

The `/balance-adjustments` workflow records deposits, withdrawals and other balance corrections with:

- Account.
- Positive/negative amount.
- Date/time.
- Reason.
- History.

### 9.9 Broker paste

The `/broker-paste` quick-entry workflow parses the original broker multiline format, including asset, direction, dates/times, position size, entry/exit prices, P&L, commission and ticket.

### 9.10 Navigation

Phase 9 exposes the new functionality directly in the application navigation:

- Bulk Update.
- Restore JSON.
- Balance.
- Broker Paste.
- Existing Import, Backup, Review Queue, Notifications and Audit Log remain available.

### Phase 9 data flow

```text
Trade Entry
   ├── Grade
   ├── Timeframe
   ├── Broker Paste
   └── Ideal Stop Loss
          ↓
Grade-adjusted Risk Engine
          ↓
Trade History + Advanced Filters
          ↓
Bulk Operations / JSON Restore / Balance Adjustments
          ↓
Analytics + Review Workflow
```

### Phase 9 migration

```text
database/migrations/008_phase9_original_feature_parity.sql
```

The migration adds grade, timeframe and screenshot metadata to trades, plus balance-adjustment and trade-tag tables.

## Database migrations

Run migrations in order:

```text
001 / base schema
002 / Phase 2 changes
003_phase3_configuration.sql
004_phase4_advanced_journal.sql
005_phase6_import_pipeline.sql
006_phase7_audit_and_hardening.sql
007_phase8_notifications.sql
008_phase9_original_feature_parity.sql
```

For existing installations, do not skip migrations.

## Setup

1. Copy `.env.example` to `.env`.
2. Configure the MySQL connection.
3. Import `database/schema.sql`.
4. Apply migrations in sequence.
5. Start the application:

```bash
php -S localhost:8000 -t public
```

6. Open `http://localhost:8000`.

## Architecture summary

| Phase | Focus | Main result |
|---|---|---|
| 1 | Foundation | Users, accounts, trades, authentication |
| 2 | Import / Backup | CSV import, JSON backup, basic analytics |
| 3 | Configuration / Risk | Systems, strategies, assets, sessions, risk engine |
| 4 | Journal | Qualitative journal and tags |
| 5 | Analytics | Performance, risk and breakdown analytics |
| 6 | Data Management | Validated imports and import history |
| 7 | Security / Audit | Hardening and audit trail |
| 8 | Review / Notifications | Review queue and notifications |
| 9 | Feature Parity | Grading, adjusted risk, advanced filters, bulk operations, JSON restore, balance adjustments and broker paste |

## Reference project

Original project:

https://github.com/coderkhalide/Trading-Journal
