# Migration history

This repo originally held a Laravel 12 build (Eloquent models, Reverb
WebSockets, Redis queue workers, an Alpine.js frontend) targeting a VPS
environment.

That build was replaced outright with a plain PHP + PDO + MySQL + AJAX
rebuild targeting **Hostinger shared (cPanel) hosting**, which cannot run
persistent processes, WebSockets, Redis, or queue workers. The Laravel app
and its Alpine.js frontend have been removed from `main`; the old code is
still reachable in this repo's git history if it's ever needed for
reference (see the commits before this migration, or the
`plain-php-stage1-foundation` branch/PR that introduced the replacement).

Current status: **Stage 1 of 6** (foundation — config, DB connection, admin
auth, full schema). See `README.md` for what's built and what isn't yet.
