# Technical review guide

This repo is a **portfolio sample**, not production software. It is meant to show how Jonathan Rapusas structures Laravel APIs, Vue desks, tests, and legacy PHP on one database.

## Fast path (about 10 minutes)

1. Read [architecture.md](./architecture.md) (one database, three UI layers).
2. Run locally:

   ```bash
   composer install
   cp .env.example .env && php artisan key:generate
   touch database/database.sqlite
   php artisan migrate:fresh --seed
   npm ci && npm run build
   php artisan serve
   ```

3. Open `/portfolio` — checklist and links for hiring managers.
4. Open `/` — Vue desk (queue, corporate bids, provisioning).
5. Open `/legacy/ticket_summary.php` and `/legacy/admin.html` — legacy stack on the same data.
6. Run `composer test` (PHPUnit, in-memory SQLite).

## What to look at in code

| Area | Path | Notes |
|------|------|--------|
| API validation + transactions | `app/Http/Controllers/Api/TicketController.php` | Filters, cross-account line check, status note required on change |
| Audit trail | `app/Models/Ticket.php` | `recordStatusChange()` + `ticket_status_histories` |
| Corporate domain | `Quote*`, `ProvisioningOrder*` models + controllers | Read-heavy list/detail |
| Legacy parity | `public/legacy/db.php`, `ticket_summary.php` | PDO from Laravel `.env`; static SQL only |
| Front end | `resources/js/App.vue`, `api.js` | Axios to `/api/v1` |
| Demo volume | `database/seeders/UcDemoSeeder.php` | Tunable via `UC_DEMO_*` env vars |
| Tests | `tests/Feature/TicketApiTest.php`, `CorporateApiTest.php` | API behaviour, not UI snapshots |

## Intentional scope limits (not oversights)

- **No authentication** — public demo; `/portfolio` states this. Do not treat as a security baseline.
- **Writable API** (`POST`/`PATCH` tickets) — acceptable for a seeded sandbox; production would add auth, rate limits, and roles.
- **Fictional data** — parody AU brand names and NSW suburb labels (`config/demo.php`); not a real operator.
- **Legacy PHP** — deliberate “maintain old scripts” sample; queries are fixed strings (no user input in SQL).

## Deploy

Production notes: [DEPLOY.md](./DEPLOY.md). Live demo URL (when deployed) is set via `DEMO_PORTFOLIO_LIVE_URL` in `.env`.
