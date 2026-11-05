# Why there's a `php/` folder next to the Laravel app

This repo's existing root (`app/`, `routes/`, `database/migrations/`, Alpine.js
frontend) is the original Laravel 12 build and is **untouched** by this folder.

`php/` is a from-scratch rebuild of the same product on **plain PHP + PDO +
MySQL + AJAX**, targeting Hostinger shared (cPanel) hosting, which cannot run
Laravel's queue workers, Redis, or the Reverb WebSocket server that the
original build depends on for its live ticker and background gazette
ingestion.

Status: **Stage 1 of 6** (foundation — config, DB connection, admin auth,
full schema). See `php/README.md` for what's built and what isn't yet.

This is a deliberate full rewrite, not a partial migration — once later
stages land and the plain-PHP build is verified working end to end, the
Laravel tree at the repo root is expected to be removed. Until then, treat
`php/` as the active development line and the root Laravel app as frozen.
