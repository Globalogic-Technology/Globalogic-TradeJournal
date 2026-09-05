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
Phase 11.1 Account/System Goals + Goal Visualization + Calendar Navigation
   ↓
Phase 11.2 Best Trading Times + Strategy Performance + Bulk Trade Filters
   ↓
Phase 11.3 Account-Specific Trade CSV Templates
```

## Phase 11.3 — Account-Specific Trade CSV Templates

The CSV importer now supports broker/account-specific CSV formats instead of requiring every account to export the same column layout.

### Account Trade CSV Import section

The **Accounts** page now contains a **Trade CSV Import** section where each account can have its own template. A template stores:

- Template name.
- CSV delimiter: comma, semicolon, pipe or tab.
- Whether the CSV has a header row.
- Source date/time zone.
- Default-template flag.
- Mapping from broker CSV columns to the journal's standard trade fields.

Supported standard trade fields include Symbol, Type/Side, Opening Time, Closing Time, Quantity/Lots, Entry Price, Stop Loss, Take Profit, Exit Price, Profit, Fees/Commission, Close Reason and Ticket/Trade ID.

A default Exness-style mapping is provided as the starting point, but the CSV column names can be changed to match any broker/export format.

### Import workflow

The **Import Trades** page now works in this order:

1. Select an Account.
2. The account's default CSV template is selected automatically when available.
3. Select another template for that account when needed.
4. Upload the broker CSV.
5. Preview the CSV using the selected mapping.
6. Import the normalized trades into the existing `trades` table.

The selected account/template is validated server-side, so a template belonging to another account cannot be used for an import.

The importer converts broker-specific column names into the application's canonical trade structure. Stop loss and take profit mappings are optional; the existing duplicate-ticket protection remains active.

Multiple templates can be stored for the same account, while one can be marked as the default. This makes it possible to keep older broker export formats alongside a current format without changing PHP code.

## Phase 11.2 — Best Trading Times, Strategy Performance and Bulk Filters

The Dashboard now provides deeper time-of-day and strategy analytics. The Bulk Trade Update page also mirrors the Trade History filtering workflow.

### Best Trading Times of the Day

The **Best Trading Times of the Day** ranking appears below **Best Trading Days of the Week** and:

- Groups closed trades by the **opening hour** stored in `trades.opened_at`.
- Uses one-hour windows such as `09:00–10:00` and `10:00–11:00`.
- Ranks periods by **average P&L per closed trade**.
- Shows rank, time window, number of trades, total P&L, average P&L and win rate.
- Respects the selected Dashboard Account and Trading System filters.
- Shows **Most Strategy Used**, based on the strategy with the highest number of trades in that time window.

### Strategy Performance

A new **Strategy Performance** chart shows net P&L grouped by strategy for the selected Dashboard scope. It uses the same account and trading-system filters as the rest of the Dashboard, making it easier to compare which strategies contribute most to overall performance.

### Strategy information in weekday ranking

The **Best Trading Days of the Week** table now includes **Most Strategy Used**, identifying the strategy with the highest trade count for each weekday.

If a trade has no strategy assigned, it is reported as **Unassigned** rather than being omitted from the analytics.

Times are based on the timezone represented by the stored `opened_at` value/database session; the feature does not silently convert timestamps to another timezone.

### Bulk Trade Update filters

The **Bulk Trade Update** page now provides the same filtering dimensions used by Trade History before selecting trades for a bulk operation:

- Symbol
- Side
- Status
- Trading System
- Asset
- Strategy
- Trading Session
- Grade
- Timeframe
- Outcome
- From / To dates
- Minimum / Maximum P&L
- Minimum / Maximum R Multiple

Filters are applied server-side before the selectable trade list is rendered. Trade ownership remains enforced when the bulk update is submitted, so selected IDs cannot be used to modify another user's trades.

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
- Multi-select Tags and Mistakes controls with visible option lists and custom tag support.

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
- Account-specific CSV templates and broker-to-canonical trade-field mapping are implemented in Phase 11.3.

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
- The Monthly P&L Calendar legend uses larger, color-coded status symbols: green **✓** for goal hit, blue **↑** for positive below goal, red **✕** for loss, and gray **•** for no P&L.
- The calendar has **Prev**, **Today** and **Next** controls so historical and future months can be reviewed without leaving the Dashboard.
- Calendar navigation preserves the selected Account and Trading System filters.
- Calendar navigation loads only the calendar section through an asynchronous request and preserves the user's current page scroll position.

### Weekday P&L ranking

A **Best Trading Days of the Week** summary appears below the calendar. It ranks weekdays by **average P&L per closed trade** for the selected dashboard scope and displays:

- Rank (`1º`, `2º`, `3º`, etc.).
- Weekday.
- Number of closed trades.
- Total P&L.
- Average P&L per trade.
- Most Strategy Used for that weekday.

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
011_phase11_account_csv_templates.sql
```

Phase 11.2 does not require a database migration because its time and strategy analytics and bulk filtering are derived from existing trade, strategy, asset, session and trading-system data.

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
| 4 | Journal | Qualitative journal, tags and multi-select review fields |
| 5 | Analytics | Performance, risk and breakdown analytics |
| 6 | Data Management | Validated imports and import history |
| 7 | Security / Audit | Hardening and audit trail |
| 8 | Review / Notifications | Review queue and notifications |
| 9 | Feature Parity | Grading, adjusted risk, filters, bulk operations, JSON restore, balance adjustments, broker paste and screenshots |
| 10 | UI | Compact trading-journal dashboard and responsive workflow |
| 11 | Goals / Performance | Daily/weekly/monthly/yearly targets, calendar P&L and yearly performance |
| 11.1 | Account/System Goals | Independent goals, editing/deletion, goal-hit visualization, calendar navigation and weekday ranking |
| 11.2 | Time / Strategy / Bulk Analytics | Best trading times, most-used strategy by weekday/time, Strategy Performance chart and Trade History filters for bulk updates |
| 11.3 | Account CSV Templates | Account-specific broker mappings, multiple templates, default-template selection and canonical CSV import |

## Reference project

Original project:

https://github.com/coderkhalide/Trading-Journal
