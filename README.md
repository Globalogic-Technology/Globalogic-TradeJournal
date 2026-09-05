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
   ↓
Phase 10 UI Redesign
   ↓
Phase 11 Goals + Performance
   ↓
Phase 11.1 Account/System Goals + Goal Visualization
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

- Trading systems and strategies.
- Assets and asset fees.
- Trading sessions and time zones.
- Risk settings and account configuration.
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

- Win rate, net P&L, profit factor and expectancy.
- Maximum drawdown and trade Sharpe.
- Total fees and fee impact.
- Equity curve.
- Average ideal/actual risk and risk deviation.
- Over-risk trades and average R Multiple.
- System, strategy, session and day-of-week breakdowns.

## Phase 6 — Import pipeline and data management

- CSV preview and validation.
- Duplicate detection and import result reporting.
- Import history and error summaries.
- Destructive-operation confirmation.
- Delete all trades and import history.
- PHP 8.4-compatible `fgetcsv()` usage.

## Phase 7 — Security, reliability and audit trail

- Strict sessions and secure cookie settings.
- CSRF protection.
- Content Security Policy with nonces.
- Security headers and HSTS on HTTPS.
- User-owned queries.
- Audit log and auditing for data operations.

## Phase 8 — Review workflow and notifications

- Review queue.
- Pending/reviewed/needs-follow-up states.
- Review due dates and notes.
- Automatic review creation for closed trades.
- In-app notifications.

## Phase 9 — Original feature parity

Phase 9 closes the major functionality gaps identified by comparing the PHP application with the original Trading Journal project.

### Trade grading

The trade model supports:

`A++++`, `A+++`, `A++`, `A+`, `A`, `B`, `C`, `D`, `E`, `F`

with grade-based risk multipliers, including 2.50x for A++++, 2.00x for A+++, 1.25x for A++, 1.00x for A+, and progressively smaller multipliers through F.

### Other parity features

- Grade-adjusted risk.
- Ideal stop-loss calculator.
- Timeframes: `1m`, `5m`, `15m`, `30m`, `1h`, `4h`, `1d`, `1w`.
- Advanced trade filters.
- Bulk trade updates.
- JSON restore with duplicate detection.
- Balance adjustments.
- Broker paste quick entry.
- Secure trade screenshots.

## Phase 10 — UI redesign

The application UI was redesigned around the original project's compact trading-journal workflow:

- Clean white canvas.
- Compact horizontal primary navigation.
- Dense KPI cards and report panels.
- Responsive trade grids and tables.
- Dashboard, Advanced Analytics, System Report and Fee Report.
- Trade screenshots integrated with the trade workflow.

## Phase 11 — P&L Goals and Performance

The Dashboard now provides goal-oriented performance tracking for:

- Daily P&L.
- Weekly P&L.
- Monthly P&L.
- Yearly P&L.
- Monthly P&L calendar.
- Year Performance chart.
- Monthly Performance chart with goal reference.
- Goal progress cards with goal-hit indicators.

The monthly calendar makes the daily target visible directly on each day.

## Phase 11.1 — Goals by Account and Trading System

P&L goals can now be configured independently by **Account** and optionally by **Trading System**.

Examples of trading systems include:

- Day Trade
- Swing Trade
- Position Trade
- Buy & Hold

The application uses the systems configured in the `trading_systems` table, so these names can be created or renamed to match the user's trading methodology.

### Goal scopes

- **Account total:** applies to all systems for an account.
- **Account + System:** applies only to trades assigned to that trading system for that account.
- Multiple account/system combinations can have independent daily, weekly, monthly and yearly targets.

### Goal editing and deletion

The **Current Goals** table supports:

- **Edit:** loads the existing goal into the form and updates the same database record instead of creating a duplicate.
- **Delete:** removes the selected goal after confirmation.

The server validates ownership for accounts, trading systems and goals before modifying data.

### Goal visualization

Dashboard goal references make progress easier to read:

- Goal reference lines are shown on performance charts.
- A green check mark indicates that the target has been reached.
- Calendar days that reach the daily target receive a visual goal-hit treatment and check mark.
- Goal cards show actual performance alongside the configured target.

### Phase 11 migrations

```text
009_phase11_goals.sql
010_phase11_goals_by_system.sql
```

Migration 010 changes the goal uniqueness model from one goal per account to one goal per account/system combination while preserving the existing account-level goal represented by a `NULL` trading system.

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
009_phase11_goals.sql
010_phase11_goals_by_system.sql
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
| 9 | Feature Parity | Grading, adjusted risk, filters, bulk operations, JSON restore, balance adjustments, broker paste and screenshots |
| 10 | UI | Compact trading-journal dashboard and responsive workflow |
| 11 | Goals / Performance | Daily/weekly/monthly/yearly targets, calendar P&L and yearly performance |
| 11.1 | Account/System Goals | Independent goals, editing/deletion and goal-hit visualization |

## Reference project

Original project:

https://github.com/coderkhalide/Trading-Journal
