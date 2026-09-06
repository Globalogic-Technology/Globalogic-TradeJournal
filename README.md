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
   ↓
Phase 12 Pre/Post Reviews + Review Goals + Review Enforcement + Schedule Exceptions
```

## Phase 12 — Pre/Post Reviews, Review Goals and Enforcement

Phase 12 adds a unified review workflow for **Day, Week, Month and Year**, each with a **Pre Review** and **Post Review**.

### Review Center

- `/pre-post-review` provides Day, Week, Month and Year tabs plus Pre/Post tabs.
- Review schedules are generated for the current period and use a configurable timezone.
- Review start times are configurable from **Review Schedule**.
- Weekday, monthly and yearly review dates can be configured.
- `/review-goals` manages reusable goals.
- `/review-settings` manages review schedule and timezone.
- `/review-exceptions` skips review creation for Day Off, PTO, personal problems, holidays, or other no-trading periods.

### Review Goals

Every review can select multiple reusable goals. Predefined goals include:

- Follow my trading plan.
- Take only planned setups.
- Avoid revenge trading.
- Avoid FOMO.
- Avoid overtrading.
- Respect my stop loss.
- Do not move my stop loss.
- Respect maximum trade/risk limits.
- Stop when the loss limit is reached.
- Stay emotionally neutral.
- Maintain focus.
- Trade only when mentally prepared.
- Reach my P&L target.
- Achieve positive R.

Users can add their own goals and reuse them later, similar to the journal Tags workflow. Goals have types: Process, Risk, Psychology, Performance or Custom.

Pre Review defines the commitment. When a Pre Review is completed, its selected goals are carried into the corresponding Post Review. The Post Review records each goal as **Hit**, **Missed**, **Pending** or **N/A**, with optional evidence/notes and target/unit fields.

Day Pre Review setup fields now use controlled inputs where useful: market condition and higher-timeframe bias are comboboxes, while Primary Setup and Secondary Setup use a type-or-select combobox with common trading setups such as Breakout, Pullback, Trend Continuation, Liquidity Sweep, VWAP Reversion and Opening Range Breakout.

### Goal achievement percentage

For every completed Post Review:

`Goal Achievement % = Goals Hit / Goals Selected × 100`

The Dashboard includes a **Review Goal Achievement** line chart with Day/Week/Month/Year selection and a 0–100% scale, showing the historical percentage of goals achieved in completed Post Reviews.

### Review Queue and Notifications

Review Queue now combines:

- Trade
- Day
- Week
- Month
- Year

The queue has filters for each type and displays both trade reviews and period reviews. Notifications use the same type filters.

An unread notification bell appears beside **Logout**. A pending/actionable review count appears beside **Review Queue**.

### Review popups

The application checks review status every **60 seconds** while the authenticated layout is open. When a scheduled review becomes due, a modal opens with a direct link to the review.

- Day Pre Review popup: required before entering/importing trade data.
- Day Post Review popup: reminder to complete the day's Post Review.
- Late login is handled because the server checks whether the scheduled time has already passed.
- The review status endpoint explicitly loads the notification service so the popup check returns valid JSON instead of failing silently.
- The popup can be temporarily dismissed, but the Day Pre Review server-side restriction remains active until the review is completed.

### Day Pre Review enforcement

Trade creation/editing and trade imports are blocked server-side while a scheduled Day Pre Review is pending. This protects the rule even if JavaScript is disabled or bypassed.

The enforcement is applied to:

- Manual Trade entry.
- CSV Import, including Interactive Brokers Smart CSV Import.

CSV Preview remains available because preview does not write trade data.

### Schedule exceptions / missed reviews

A trader should not be forced to create meaningless reviews for a non-trading day. `/review-exceptions` allows a date or date range to be marked as an exception with a reason such as **Day Off**, **PTO**, **Personal Issue**, **Holiday** or another custom reason.

When an exception is created:

- Matching period review records are removed.
- The scheduler will not recreate those reviews.
- The exception is retained as the source of truth for that period.
- A review can also be deleted directly from the Pre/Post Review page; deleting it creates an exception so it is not immediately recreated.

Migration:

`014_phase12_review_exceptions.sql`

### Suggested review content

Day Pre Reviews include mindset/readiness, energy/focus, market condition, higher-timeframe bias, markets/assets, important levels, primary/secondary setups, risk limits, maximum trades, no-trade conditions and a personal commitment.

Day Post Reviews include plan adherence, risk adherence, outside-plan trades, revenge/FOMO checks, stop discipline, grade, best/worst trades and detailed lessons learned.

Week, Month and Year reviews include period objectives and process/skill reflection in addition to the reusable Goal system.

### Database migration

Phase 12 uses:

`013_phase12_reviews_goals.sql`
`014_phase12_review_exceptions.sql`

The migration adds:

- `review_goals`
- `period_reviews`
- `period_review_goals`
- `review_settings`
- `review_exceptions`

The Phase 12 migration intentionally keeps default goal seeding in the application layer using `INSERT IGNORE`, avoiding MariaDB/phpMyAdmin parser differences around `INSERT ... SELECT ... ON DUPLICATE KEY UPDATE`.

## Phase 11.4 — Smart CSV Import

Phase 11.4 adds broker-aware import behavior without removing the account-specific manual CSV mapping system from Phase 11.3.

### Account CSV template modes

The Accounts page provides two template modes:

- **Standard Trade CSV** — normal manual mapping. Every required canonical trade field must be mapped to a CSV column.
- **Smart CSV Import** — manual mappings remain visible and are stored exactly like the standard mappings. Smart behavior is activated on the Import Trades page for supported broker accounts.

Selecting Smart CSV Import never hides the CSV column mapping fields. Users can manually map every field that exists in their broker export.

### Interactive Brokers Smart Import

When the selected account is an **Interactive Brokers** account and its selected template uses **Smart CSV Import**, the Import Trades page:

1. Uses the account template's manual mappings first.
2. Automatically falls back to supported Interactive Brokers order columns only for fields that were not manually mapped (or have an empty mapped value).
3. Reconstructs broker order rows into the journal's canonical trade records.
4. Matches filled entries with Take Profit and Stop Loss exits.
5. Correctly accounts for the fact that IBKR exit orders normally have the opposite Side from the position they close (Sell closes Long; Buy closes Short).
6. Supports partial exits by reducing the remaining position quantity.
7. Uses Avg fill price for entry/exit prices when no manual value is available.
8. Uses Stop price / Stop loss and Limit price / Take profit for protective levels when available.
9. Uses Last update time when opening/closing timestamps are not manually mapped.
10. Uses Order ID when Ticket / Trade ID is not manually mapped.
11. Uses manually mapped Profit and Fees when populated; otherwise calculates realized P&L from entry/exit fills and defaults fees to zero when unavailable.
12. Creates Open trades for filled entries that remain unmatched.
13. Shows generated trades during Preview before writing them to the database.

The supported Interactive Brokers order export contains fields such as:

`Symbol, Side, Type, Quantity, Avg fill price, Limit price, Stop price, Take profit, Stop loss, Status, Last update time, Instruction, Duration, Order ID`

The important distinction is that the broker's **Type** column (`Limit`, `Market`, `Take Profit`, `Stop Loss`, etc.) is used to understand the order, while the broker's **Side** column (`Buy` / `Sell`) is used to determine the trade direction. This prevents an IBKR `Type=Limit` value from being incorrectly treated as a journal Buy/Sell side.

### Database migration

Phase 11.4 uses:

`012_phase11_smart_csv_import.sql`

## Phase 11.3 — Account-Specific Trade CSV Templates

The CSV importer supports broker/account-specific CSV formats instead of requiring every account to export the same column layout.

The **Accounts** page contains the **Create/Edit Trade CSV template** form above one consolidated **Trade CSV template list**. Templates can be stored multiple times per account while one is marked as the default. The account-specific template is automatically selected on the Import Trades page.

## Phase 11.2 — Best Trading Times, Strategy Performance and Bulk Filters

The Dashboard provides deeper time-of-day and strategy analytics. The Bulk Trade Update page also mirrors the Trade History filtering workflow.

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
013_phase12_reviews_goals.sql
014_phase12_review_exceptions.sql
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
| 11.4 | Smart CSV Import | Manual-first Interactive Brokers mapping, broker-aware fallback, order-to-trade conversion, exit matching, partial exits and smart preview |
| 12 | Pre/Post Reviews + Review Goals | Period reviews, reusable goals, goal achievement percentage, notifications, popups, setup comboboxes, trade-entry enforcement and schedule exceptions |

## Reference project

Original project:

https://github.com/coderkhalide/Trading-Journal
