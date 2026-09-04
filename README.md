# Trading Journal — PHP + MySQL

A server-side PHP + MySQL trading journal based on the feature set and workflow of [coderkhalide/Trading-Journal](https://github.com/coderkhalide/Trading-Journal). The project progressively evolves from a basic trade journal into a configurable risk-management, analytics, import, audit and review platform.

## Technology

- PHP 8.2+
- MySQL 8+
- PDO with prepared statements
- Server-rendered PHP views
- Apache rewrite support
- PHP built-in server compatible route shims
- Chart.js for analytics visualization

## Project flow

The application is built incrementally. Each phase is based on the previous phase and adds a specific functional layer:

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
```

---

## Phase 1 — Working foundation

Phase 1 establishes the core PHP/MySQL application and the basic trade lifecycle.

### Main flow

```text
Register
  ↓
Login
  ↓
Create trading account
  ↓
Create trade
  ↓
Edit / view / delete trade
  ↓
Dashboard + trade history
```

### Features

- User registration and login/logout.
- Session-based authentication.
- CSRF protection.
- Trading accounts.
- Trade CRUD.
- Long and Short trades.
- Open and Closed trade status.
- Duplicate ticket protection per account.
- Server-side validation.
- Automatic P&L calculation.
- Dashboard account balances.
- Trade filtering and pagination.
- PDO prepared statements.
- Apache rewrite support.
- PHP built-in server route compatibility.

### Trade lifecycle

```text
Open trade
   ↓
Update trade while open
   ↓
Close trade
   ↓
P&L calculated
   ↓
Balance updated in dashboard calculations
```

---

## Phase 2 — Import, backup and basic analytics

Phase 2 adds the first data-management and analysis capabilities.

### Features

- Exness-style CSV trade import.
- JSON backup/export.
- Closed-trade analytics.
- Initial performance calculations.
- Imported trades become normal journal trades and participate in later phases.

### Flow

```text
CSV file
  ↓
Parse trade records
  ↓
Validate data
  ↓
Prevent duplicate tickets
  ↓
Create trades
  ↓
Analytics / dashboard
```

---

## Phase 3 — Trading configuration and risk engine

Phase 3 turns the journal into a configurable risk-management system.

### Configuration

- Trading systems.
- Strategies.
- Assets.
- Asset fees.
- Trading sessions.
- Risk settings.
- Account configuration.
- Default trading system configuration.
- System-specific ideal risk.

### Risk flow

```text
Account
  ↓
Trading System
  ↓
Strategy / Asset / Session
  ↓
Risk configuration
  ↓
Trade inputs
  ↓
Risk calculation
  ↓
Trade risk metrics
```

### Trade-level calculations

- Ideal risk.
- Actual risk.
- Risk percentage.
- Position size.
- Expected R.
- R Multiple.
- Risk deviation.
- Balance before.
- Balance after.

The risk engine uses contract-size and point-value configuration where available.

### Important terminology

**Expected R** in this project is the normalized realized result:

```text
Expected R = Net P&L / Ideal Risk
```

It is not the same thing as a planned reward-to-risk ratio.

---

## Phase 4 — Advanced trade journal

Phase 4 adds the qualitative side of trade analysis: why the trade was taken, how it was executed and what was learned from it.

### Journal flow

```text
Closed trade
  ↓
Open journal
  ↓
Record setup and market context
  ↓
Record thesis / entry / exit reasons
  ↓
Record emotions and confidence
  ↓
Grade execution and discipline
  ↓
Record mistakes / lessons
  ↓
Review later
```

### Journal fields

- Setup.
- Market context.
- Thesis.
- Entry reason.
- Exit reason.
- Emotion before.
- Emotion after.
- Confidence.
- Execution quality.
- Discipline score.
- Mistakes.
- Lessons.
- What went well.
- What to change.
- Review timestamp.

### Tags

Reusable journal tags can be associated with trades through the journal tagging system.

---

## Phase 5 — Performance and risk analytics

Phase 5 turns the accumulated trades and journal data into performance information.

### Performance metrics

- Win rate.
- Net P&L.
- Profit factor.
- Expectancy per trade.
- Maximum drawdown.
- Trade Sharpe.
- Total fees.
- Equity curve.

### Risk analytics

- Average ideal risk.
- Average actual risk.
- Average risk deviation.
- Over-risk trades.
- Average R Multiple.

### Breakdowns

Performance can be analyzed by:

- Trading system.
- Strategy.
- Trading session.
- Day of week.
- Fee impact.

### Analytics flow

```text
Trades
  +
Risk configuration
  ↓
Performance calculations
  ↓
Risk calculations
  ↓
Breakdowns
  ↓
Equity curve + analytics dashboard
```

Analytics are derived from the existing trade and configuration data; no separate analytics database is required.

---

## Phase 6 — Import pipeline and data management

Phase 6 replaces the simple import flow with a safer, auditable data-management workflow.

### Import flow

```text
Upload CSV
  ↓
Preview
  ↓
Validate headers / values / dates
  ↓
Identify duplicates and errors
  ↓
Import valid records
  ↓
Store import history
  ↓
Review results
```

### Features

- CSV preview.
- CSV validation.
- Import result reporting.
- Duplicate-ticket protection.
- Import history.
- Import error summaries.
- Data management screen.
- Deliberate confirmation for destructive operations.
- Delete all user trades.
- Delete import history.

### Import history

Each import records information such as:

- Account.
- Filename.
- Total rows.
- Imported rows.
- Skipped rows.
- Error rows.
- Status.
- Error summary.
- Creation time.

The CSV parser explicitly supplies the escape argument to `fgetcsv()` for PHP 8.4 compatibility.

---

## Phase 7 — Security, reliability and audit trail

Phase 7 hardens the application and introduces traceability for important operations.

### Security hardening

- Strict PHP session mode.
- Cookie-only sessions.
- HttpOnly session cookies.
- SameSite=Lax cookies.
- Secure cookies when HTTPS is detected.
- Content Security Policy with per-request nonce support.
- `X-Content-Type-Options: nosniff`.
- `X-Frame-Options: SAMEORIGIN`.
- Strict referrer policy.
- Restrictive Permissions Policy.
- HSTS when HTTPS is detected.

### Audit trail

The `/audit-log` screen shows user-owned audit events.

Important operations are recorded, including:

- CSV preview.
- CSV import.
- Delete all trades.
- Delete import history.

The audit service is fail-safe: a logging failure is recorded server-side and does not prevent the primary user operation from completing.

### Security flow

```text
Authenticated request
  ↓
Session security
  ↓
CSRF validation for state changes
  ↓
User-owned database query
  ↓
Primary operation
  ↓
Audit event
```

---

## Phase 8 — Review workflow and notifications

Phase 8 adds a formal post-trade review workflow around the Advanced Trade Journal.

The goal is to ensure that closed trades are not merely stored — they are reviewed, evaluated and followed up.

### Review lifecycle

```text
Trade closes
   ↓
Review record created
   ↓
Pending review
   ↓
Review becomes due
   ↓
Notification generated
   ↓
Trader opens Review Queue
   ↓
Review trade
   ↓
┌───────────────────────┐
│ Reviewed              │
│ or                    │
│ Needs follow-up       │
└───────────────────────┘
```

### Review Queue

Route:

```text
/review-queue
```

The queue provides a centralized list of trade reviews with:

- Trade.
- Symbol.
- Side.
- Closed date.
- Review due date.
- Review status.
- Link to perform the review.

### Review statuses

```text
pending
reviewed
needs_followup
```

### Individual review

A review can contain:

- Review status.
- Review due date.
- Review notes.
- Reviewed timestamp.

The review workflow is intentionally separate from the detailed journal content. The Phase 4 journal stores the analysis; Phase 8 controls whether that analysis has been reviewed and whether follow-up is required.

### Notifications

Route:

```text
/notifications
```

Phase 8 provides in-app notifications for overdue/pending journal reviews and allows the user to mark notifications as read.

### Automatic review creation

Closed trades without an existing review record are automatically added to the review workflow when an authenticated application page is requested.

The initial implementation uses a one-day review interval after trade closure and does not require an external cron job.

### Phase 8 data flow

```text
Existing trades
      ↓
Closed trades
      ↓
Review records
      ↓
Due-date evaluation
      ↓
Notifications
      ↓
Review Queue
      ↓
Trade Review
      ↓
Reviewed / Follow-up
```

### Phase 8 database

Migration:

```text
database/migrations/007_phase8_notifications.sql
```

Tables:

- `journal_reviews` — stores review status, due date, notes and review timestamps.
- `user_notifications` — stores user-specific in-app notifications and read state.

---

## Database migrations

Run migrations in order.

```text
001 / base schema
002 / Phase 2 changes
003_phase3_configuration.sql
004_phase4_advanced_journal.sql
005_phase6_import_pipeline.sql
006_phase7_audit_and_hardening.sql
007_phase8_notifications.sql
```

For a new installation, start with `database/schema.sql` and then apply the required migrations in sequence.

For an existing installation, do not skip migrations.

---

## Setup

1. Copy `.env.example` to `.env`.
2. Configure the MySQL connection in `.env`.
3. Import `database/schema.sql`.
4. Apply migrations in order.
5. Start the application:

```bash
php -S localhost:8000 -t public
```

6. Open:

```text
http://localhost:8000
```

### Phase 8 setup

After Phase 7, apply:

```text
database/migrations/007_phase8_notifications.sql
```

No external cron job is required for the initial Phase 8 implementation because review notifications are generated when an authenticated application page is requested.

---

## Architecture by phase

| Phase | Focus | Main result |
|---|---|---|
| 1 | Foundation | Users, accounts, trades, authentication |
| 2 | Import / Backup | CSV import, JSON backup, basic analytics |
| 3 | Configuration / Risk | Systems, strategies, assets, sessions, risk engine |
| 4 | Journal | Qualitative trade journal and tags |
| 5 | Analytics | Performance, risk and breakdown analytics |
| 6 | Data Management | Validated imports, history and destructive-operation controls |
| 7 | Security / Audit | Hardening and audit trail |
| 8 | Review / Notifications | Review queue, review status, due dates and notifications |

## Reference project

This project is inspired by the feature set and workflow of:

https://github.com/coderkhalide/Trading-Journal
