# Deploy (shared PHP hosting)

Target: document root **`public/`** (Laravel front controller). PHP **8.2+**, MySQL 8 recommended on the host.

## Build artifact

```bash
./scripts/build-deploy-zip.sh
```

Output: `storage/app/deploy/uc-service-desk-*.zip` (includes `vendor/` and `public/build/`). Upload via FTP/SFTP; extract so `artisan` sits **above** the web root; point the subdomain at `.../public`.

Do **not** upload `.env`, `node_modules/`, or `.git`.

## Server `.env`

Copy [deploy/env.production.template](../deploy/env.production.template). Minimum:

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL` = your HTTPS URL (no trailing slash)
- `APP_KEY` from `php artisan key:generate --show` on your machine
- MySQL `DB_*` credentials from the hosting panel (`DB_HOST` usually `localhost`)

Optional for `/portfolio` links:

```env
DEMO_PORTFOLIO_LIVE_URL=https://your-demo.example.com
DEMO_PORTFOLIO_GITHUB_URL=https://github.com/you/connectwave-desk
```

## After upload

Writable: `storage/`, `bootstrap/cache/`.

```bash
php artisan migrate --force
php artisan db:seed --class=UcDemoSeeder --force
php artisan config:cache
php artisan route:cache
```

## Smoke test

| URL | Expect |
|-----|--------|
| `/` | Styled Vue desk |
| `/portfolio` | Reviewer index |
| `/api/v1/dashboard` | JSON |
| `/legacy/ticket_summary.php` | JSON queue |

Unstyled UI → missing `public/build/` or wrong document root. **500** → check `storage/logs/laravel.log` and permissions.
