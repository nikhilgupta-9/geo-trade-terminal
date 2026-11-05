# GeoTrade Terminal — Plain PHP / AJAX / MySQL build

Rebuilt for **Hostinger shared (cPanel) hosting**: no Laravel, no Redis, no
queue workers, no WebSockets. Plain PHP files, PDO prepared statements,
AJAX endpoints returning JSON, MySQL (InnoDB). Real-time features (ticker,
alerts) are **polling**, not push — see `cron/README.md` for why.

## Stage 1 — what's in this delivery

```
config/
  config.sample.php   copy to config.php, fill in real DB creds (gitignored)
  database.php         db() -> shared PDO connection
includes/
  helpers.php          json_response(), require_fields(), to_decimal_string(), csrf_*()
  auth.php             session-based admin login (require_admin(), attempt_admin_login())
ajax/
  health.php           GET -> confirms DB connection + lists tables, for setup verification
sql/
  schema.sql            full schema: duty engine (Stage 1/3) + macro report engine (Stage 5)
  seed.sql              sample admin user + a few HS codes + one duty-rate history, run ONCE
cron/
  README.md             cPanel Cron Jobs setup (replaces Laravel queue workers)
```

## Setup on Hostinger

1. Create a MySQL database + user in hPanel (Databases → MySQL Databases); note the generated `u123456789_...` names.
2. Import the schema: phpMyAdmin → your DB → Import → `sql/schema.sql`, then `sql/seed.sql`.
3. Upload this folder's contents to `public_html/` (or a subfolder) via File Manager or FTP.
4. Copy `config/config.sample.php` → `config/config.php`, fill in the real DB host/name/user/pass hPanel gave you. **Never commit config.php.**
5. Immediately change the seeded admin password (`admin@geotrade.local` / `ChangeMe123!`) — that hash must never reach a real deployment unchanged.
6. Visit `yourdomain.com/ajax/health.php` — should return `{"status":"ok","db_connected":true,...}` listing every table from the schema.

## What's NOT built yet (later stages)

- `ajax/calculator.php` + the simulator page (Stage 2)
- `cron/ingest_gazettes.php`, the gazette parser (Stage 3)
- `ajax/ticker.php` polling endpoint (Stage 4)
- Macro event/report AJAX endpoints + admin review-queue pages (Stage 5)
- All Bloomberg-style frontend pages, vanilla JS/jQuery (Stage 6)
