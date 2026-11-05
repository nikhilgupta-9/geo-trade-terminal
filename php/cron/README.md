# Cron jobs (replaces Laravel queue workers / Reverb)

No queue workers and no WebSocket daemon run on shared Hostinger hosting —
everything that was a Laravel job becomes a PHP script invoked by **cPanel's
Cron Jobs** feature (Hostinger: hPanel → Advanced → Cron Jobs).

Set these up once the ingestion script exists (Stage 3):

| Script | Suggested schedule | Replaces |
|---|---|---|
| `cron/ingest_gazettes.php` | `*/15 * * * *` (every 15 min) | `IngestGazetteNotificationJob` + Redis queue |
| `cron/dispatch_alerts.php` | `*/15 * * * *`, right after ingest | `MatchWatchlistsAndDispatchAlertsJob` |

Hostinger's cron UI runs a command line, not a URL — point it at the PHP
CLI binary directly, e.g.:

```
/usr/bin/php /home/u123456789/public_html/geotrade/cron/ingest_gazettes.php
```

Shared hosting caps how often a cron entry can fire (commonly every 5 minutes
minimum) and how long a script may run (commonly 30–90 seconds) — each cron
script must finish fast and pick up where it left off next run, not assume
it owns a long-lived process. `ingest_gazettes.php` tracks a `parse_status`
column on `gazette_notifications` for exactly this reason: a run that times
out mid-notification leaves it `pending`, and the next run just continues.
