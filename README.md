# ConnectWave operations desk (portfolio demo)

Fictional **Australian cloud voice** operator (**ConnectWave** is not a real company): internal desk for **corporate pricing/bids**, **provisioning tracking**, **partner resellers**, and **business customers** — support tickets (faults, provisioning, number ports), accounts, and service lines (SIP trunk, DID, hosted PBX). Shaped like typical **hosted UC / VoIP** ops (plus corporate bid workflows similar to large-carrier internal tools), without copying any real provider. **Customer names** are funny parodies with a **different first letter** than the brand they riff on; **NSW suburb** labels appear on sample service lines (`config/demo.php` → `nsw_suburbs`).

Built as a code sample for senior full-stack PHP and Laravel roles (legacy PHP, Laravel API, Vue, jQuery, reseller-style portals).

**For recruiters and hiring managers:** open **`/portfolio`** on the running app (checklist, live surface links, code paths, production-work summaries).

**For engineers / technical reviewers:** [docs/REVIEWER.md](./docs/REVIEWER.md) (10-minute path, what to read in the tree, intentional demo limits) and [docs/architecture.md](./docs/architecture.md).

## Stack

| Layer | Tech |
|-------|------|
| API | **Laravel 12**, REST `/api/v1/*` |
| UI | **Vue 3** + Vite + Tailwind 4 |
| DB | **SQLite** locally; **MySQL 8** on deploy (legacy PHP reads same `.env`) |
| Legacy | `public/legacy/ticket_summary.php`, `admin.html` (jQuery → same API / DB) |

**How it is organized:** [docs/architecture.md](./docs/architecture.md).

## Quick start

Requires **PHP 8.2+**, **Composer**, **Node 20+**. No database server needed locally (SQLite file).

```bash
# Standalone GitHub clone: use the repo root. Portfolio monorepo: cd demos/uc-service-desk first.
composer install
cp .env.example .env   # if needed
php artisan key:generate

touch database/database.sqlite   # or let migrate create it
php artisan migrate:fresh --seed

npm install
npm run dev          # Vite
php artisan serve    # http://127.0.0.1:8000
```

Open http://127.0.0.1:8000 — API at http://127.0.0.1:8000/api/v1/tickets .

### Optional: local MySQL (production parity)

Create database `uc_service_desk`, or use Docker:

```bash
# In .env: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3307, credentials from .env.example comments
docker compose up -d
php artisan migrate:fresh --seed
```

Without Docker (Herd / DBngin / local server), use port **3306** and your local user/password.

Legacy endpoint: http://127.0.0.1:8000/legacy/ticket_summary.php  
jQuery admin: http://127.0.0.1:8000/legacy/admin.html

### Sample data volume

`migrate:fresh --seed` loads **~48 accounts**, **~200 service lines**, **~3,600 tickets**, **~120 corporate bids**, **~180 provisioning orders**, plus pinned showcase refs. Tune in `.env`:

```env
UC_DEMO_ACCOUNTS=48
UC_DEMO_TICKETS=3600
UC_DEMO_QUOTES=120
UC_DEMO_PROVISIONING=180
```

## Tests

PHPUnit uses **in-memory SQLite** (no MySQL required):

```bash
composer test
# or: php artisan test
```

Eleven feature/unit tests cover dashboard, tickets (validation, audit history), corporate quotes, provisioning, and the `/portfolio` page.

## GitHub / clone note

This tree is meant for **code review** on GitHub (and local clone). Live demo: [laraveldemo.jorap.com](https://laraveldemo.jorap.com) — reviewers can use that link without running the app.

`public/build/` is not committed. After `git clone`, run `npm ci && npm run build` (or `composer run setup` for a full first-time setup). Interview prep under `_notes/` stays in the portfolio monorepo only, not in the public repo. Optional FTP/hosting steps: [docs/DEPLOY.md](./docs/DEPLOY.md).

**Before `git push`:** no `.env` (only `.env.example`), no `bootstrap/cache/*.php` from `config:cache`, no `storage/logs/`, no deploy zips under `storage/app/deploy/`. Quick check: `git status` should not list those paths; `git diff --cached` must not contain `APP_KEY=base64:` or real `DB_PASSWORD` values.

## Troubleshooting

**Browser or axios shows `500` / `Request failed with status code 500`**

Usually migrations are behind or the DB file is missing. From the project root:

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

If you use **MySQL** locally, start it first (`docker compose up -d` or your server), then migrate.

Hard-refresh the desk. Check `storage/logs/laravel.log` — `no such table` → migrate again; `Connection refused` → MySQL not running.
