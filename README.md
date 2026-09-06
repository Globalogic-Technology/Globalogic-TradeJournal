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
   ↓
Phase 11.4 Smart CSV Import
```

## Phase 11.4 — Smart CSV Import

Phase 11.4 adds broker-aware CSV parsing on top of the account-specific templates from Phase 11.3.

### Interactive Brokers Orders CSV

An account CSV template can use **Interactive Brokers Orders CSV (Smart)** mode. This supports order exports containing fields such as:

`Symbol, Side, Type, Quantity, Avg fill price, Limit price, Stop price, Take profit, Stop loss, Status, Last update time, Instruction, Duration, Order ID`

The smart importer does not require the user to manually map these columns to the journal trade fields.

It automatically:

- Detects the Interactive Brokers order-export column set.
- Sorts orders chronologically before matching them.
- Identifies filled entry orders such as Buy/Sell Limit, Stop Limit or Market-style orders.
- Identifies Take Profit and Stop Loss orders as exits/protective orders.
- Matches opposite-side exits to the corresponding open position by symbol and side.
- Supports partial exits by reducing the remaining position quantity.
- Derives Long/Short from the order side.
- Uses Avg fill price as the actual entry/exit price.
- Uses Stop price for Stop Loss protection and Limit price/Take Profit for Take Profit protection when available.
- Derives Opened/Closed timestamps from Last update time using the template timezone and stores UTC timestamps.
- Creates unique trade tickets from the source entry/exit Order IDs.
- Creates Open trades when a filled entry has not yet been matched with an exit.
- Calculates realized P&L from entry and exit fill prices when the broker export does not provide a profit column.
- Shows the generated trades during CSV Preview before anything is written to the database.

The supplied Interactive Brokers sample format is therefore converted from an **order report** into the application's canonical **trade records**, rather than treating every CSV row as an independent trade.

Fees are currently set to zero for this order-export mode because the supplied Interactive Brokers order CSV does not contain a commission/fee column. The importer records the source order IDs and calculated P&L in the trade notes.

### Template modes

Accounts can now choose:

- **Standard Trade CSV** — manually map broker columns to the journal's canonical trade fields.
- **Interactive Brokers Orders CSV (Smart)** — use the broker-aware order-to-trade conversion automatically.

Smart templates do not require manual canonical field mappings.

### Import workflow

The **Import Trades** page works in this order:

1. Select an Account.
2. The account's default CSV template is selected automatically when available.
3. Select another template for that account when needed.
4. Upload the broker CSV.
5. Preview the CSV. Smart mode displays the trades it inferred from the order rows.
6. Import the normalized trades into the existing `trades` table.

The selected account/template is validated server-side, so a template belonging to another account cannot be used for an import.

### Database migration

Phase 11.4 adds:

`012_phase11_smart_csv_import.sql`

The migration adds the `import_mode` to `account_csv_templates` so each account template can choose the appropriate parser.

## Phase 11.3 — Account-Specific Trade CSV Templates

The CSV importer supports broker/account-specific CSV formats instead of requiring every account to export the same column layout.

The **Accounts** page contains the **Create/Edit Trade CSV template** form above one consolidated **Trade CSV template list**. The list contains an Account column, template mode, default status, delimiter, header setting, time zone, mapped fields and Edit/Delete actions.

Templates can be stored multiple times per account while one is marked as the default. The account-specific template is automatically selected on the Import Trades page.

## Phase 11.2 — Best Trading Times, Strategy Performance and Bulk Filters

The Dashboard provides deeper time-of-day and strategy analytics. The Bulk Trade Update page also mirrors the Trade History filtering workflow.

### Best Trading Times of the Day

The **Best Trading Times of the Day** ranking appears below **Best Trading Days of the Week** and groups closed trades by opening hour, ranks periods by average P&L, and shows trades, total P&L, average P&L, win rate and Most Strategy Used.

### Strategy Performance

The **Strategy Performance** chart shows net P&L grouped by strategy for the selected Dashboard scope and respects account and trading-system filters.

### Bulk Trade Update filters

Bulk Trade Update supports Symbol, Side, Status, Trading System, Asset, Strategy, Trading Session, Grade, Timeframe, Outcome, From/To dates, Minimum/Maximum P&L and Minimum/Maximum R Multiple. Filters are applied server-side and ownership is enforced when updates are submitted.

## Phase 11.1 — Goals by Account and Trading System

P&L goals can be configured independently by **Account** and optionally by **Trading System**. Dashboard visualization includes goal reference lines, goal-hit indicators, a color-coded Monthly P&L Calendar, Prev/Today/Next navigation without a full-page refresh, preserved filters and weekday P&L ranking.

## Phase 11 — P&L Goals and Performance

The Dashboard provides Daily, Weekly, Monthly and Yearly P&L targets, a Monthly P&L calendar, Year Performance, Monthly Performance with goal reference and goal progress cards.

## Phase 10 — UI redesign

The application UI was redesigned around a compact trading-journal workflow with a clean white canvas, compact navigation, dense KPI/report panels, responsive trade grids and Dashboard/Analytics/System/Fee reports.

## Phase 9 — Original feature parity

Phase 9 added trade grading, grade-adjusted risk, ideal stop-loss calculation, timeframes, advanced filters, bulk updates, JSON restore with duplicate detection, balance adjustments, broker paste quick entry and secure trade screenshots.

## Phase 8 — Review workflow and notifications

- Review queue and review states.
- Review due dates and notes.
- Automatic review creation for closed trades.
- In-app notifications.

## Phase 7 — Security, reliability and audit trail

- Strict sessions and secure cookie settings.
- CSRF protection.
- Content Security Policy with nonces.
- Security headers and HSTS on HTTPS.
- User-owned queries.
- Audit log and auditing for data operations.

## Phase 6 — Import pipeline and data management

- CSV preview and validation.
- Duplicate detection and import result reporting.
- Import history and error summaries.
- Destructive-operation confirmation.
- PHP 8.4-compatible `fgetcsv()` usage.
- Account-specific CSV templates and broker-to-canonical trade-field mapping are implemented in Phase 11.3.
- Smart broker-aware order conversion is implemented in Phase 11.4.

## Phase 5 — Performance and risk analytics

- Win rate, net P&L, profit factor and expectancy.
- Maximum drawdown and trade Sharpe.
- Total fees and fee impact.
- Equity curve.
- Average ideal/actual risk and risk deviation.
- Over-risk trades and average R Multiple.
- System, strategy, session and day-of-week breakdowns.

## Phase 4 — Advanced trade journal

- Setup, market context, thesis and entry/exit reasons.
- Emotions, confidence, execution quality and discipline scores.
- Mistakes, lessons, what went well and what to change.
- Review timestamps and journal tags.
- Multi-select Tags and Mistakes controls.

## Phase 3 — Trading configuration and risk engine

- Trading systems and strategies.
- Assets and asset fees.
- Trading sessions and time zones.
- Risk settings and account configuration.
- Ideal risk, actual risk, risk %, position size, Expected R, R Multiple, risk deviation and balance calculations.
- Contract-size and point-value support.

**Expected R** is the normalized realized result: `Net P&L / Ideal Risk`.

## Phase 2 — Import, backup and basic analytics

- Exness-style CSV import.
- JSON backup/export.
- Closed-trade analytics.
- Initial performance calculations.

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
012_phase11_smart_csv_import.sql
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
| 4 | Journal | Qualitative journal, tags and review fields |
| 5 | Analytics | Performance, risk and breakdown analytics |
| 6 | Data Management | Validated imports and import history |
| 7 | Security / Audit | Hardening and audit trail |
| 8 | Review / Notifications | Review queue and notifications |
| 9 | Feature Parity | Grading, filters, bulk operations, restore, balance adjustments, broker paste and screenshots |
| 10 | UI | Compact trading-journal dashboard and responsive workflow |
| 11 | Goals / Performance | Daily/weekly/monthly/yearly targets, calendar P&L and yearly performance |
| 11.1 | Account/System Goals | Independent goals, goal visualization, calendar navigation and weekday ranking |
| 11.2 | Time / Strategy / Bulk Analytics | Best trading times, strategy usage, Strategy Performance and bulk filters |
| 11.3 | Account CSV Templates | Account-specific broker mappings, multiple templates, default-template selection and canonical CSV import |
| 11.4 | Smart CSV Import | Interactive Brokers order-to-trade conversion, automatic matching, derived P&L and smart preview |

## Reference project

Original project:

https://github.com/coderkhalide/Trading-Journal
