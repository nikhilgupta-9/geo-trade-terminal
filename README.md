# GeoTrade Terminal — Plain PHP / AJAX / MySQL build

Rebuilt for **Hostinger shared (cPanel) hosting**: no Laravel, no Redis, no
queue workers, no WebSockets. Plain PHP files, PDO prepared statements,
AJAX endpoints returning JSON, MySQL (InnoDB). Real-time features (ticker,
alerts) are **polling**, not push — see `cron/README.md` for why.

## What's in this delivery

This is the **core product**: global/national events (wars, sanctions,
elections, leadership changes, new regulation, resource discoveries, …) ->
which industries and named companies gain or lose, with a market-direction
read -> published to a public report feed, after a required human
review/approve step. The landed-cost duty engine is one event *feed* within
this, not the product itself, and hasn't been built yet in this rebuild.

```
config/        config.sample.php (copy to config.php), database.php
includes/      helpers.php, auth.php (session-based admin login)
assets/        css/terminal.css (Bloomberg-dark theme), js/public.js, js/admin.js
ajax/          health.php, reports.php, report.php (public, published-only)
ajax/admin/    reports.php, report.php, review.php, events.php (session-protected)
admin/         login.php, logout.php, index.php (review queue/all reports/all events), report.php (review panel)
index.php      public report feed (homepage)
report.php     public report detail page
sql/           schema.sql, seed.sql (duty engine sample data), seed_macro_reports.sql (report engine sample data)
cron/          README.md (cPanel Cron Jobs setup, replaces Laravel queue workers)
```

## Setup on Hostinger

1. Create a MySQL database + user in hPanel (Databases → MySQL Databases); note the generated `u123456789_...` names.
2. Import, in order: phpMyAdmin → your DB → Import → `sql/schema.sql`, then `sql/seed.sql`, then `sql/seed_macro_reports.sql`.
3. Upload this folder's contents to `public_html/` (or a subfolder) via File Manager or FTP.
4. Copy `config/config.sample.php` → `config/config.php`, fill in the real DB host/name/user/pass hPanel gave you, and a `GEMINI_API_KEY` once Stage 3/5 AI drafting is wired up. **Never commit config.php.**
5. Immediately change the seeded admin password (`admin@geotrade.local` / `ChangeMe123!`) — that hash must never reach a real deployment unchanged.
6. Visit `yourdomain.com/ajax/health.php` to confirm the DB connection, then `yourdomain.com/` for the live report feed and `yourdomain.com/admin/login.php` to review/approve reports.

## What's NOT built yet

- AI-drafted report generation itself (Stage 5 backend) — reports are added manually/by seed for now; the review workflow (approve/request changes/reject) is fully working against whatever rows exist in `macro_reports`.
- `ajax/calculator.php` + the landed-cost simulator page (duty engine — one event feed among many, not the core product)
- `cron/ingest_gazettes.php`, the gazette parser (duty engine ingestion)
- Admin forms to create a new event/report by hand (currently DB-only, via seed or direct SQL)
