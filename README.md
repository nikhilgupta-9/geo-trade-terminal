# Vectis Trade Intelligence Terminal (MVP)

Institutional-grade regulatory and tariff intelligence engine designed for mid-market export-import houses, customs brokers (CHAs), and commodity traders.

---

## 1. System Architecture & Tech Stack
* **Backend Framework:** PHP 8.2+ / Laravel 12.x (Clean OOP, Strict Types)
* **Database:** MySQL 8.0+ (InnoDB, strict foreign keys, stored generated column for tariff delta, indexed on HS codes and timestamps)
* **Frontend:** Tailwind CSS v4, Alpine.js, Chart.js (Dark-First institutional terminal aesthetics `#0B0F17`)
* **Queue & Task Scheduling:** Laravel Task Scheduler running `php artisan vectis:poll-gazette --parse` every minute via Linux Cron / cron runner.

---

## 2. Core Customs Valuation Engine (`App\Services\LandedCostEngine`)
Compliant with the **Customs Valuation (Determination of Value of Imported Goods) Rules, 2007** (CBIC / Customs Act, 1962):

$$\text{Assessable Value (CIF)} = \text{FOB Value} + \text{Freight (20\% or actual)} + \text{Insurance (1.125\% or actual)}$$
$$\text{Basic Customs Duty (BCD)} = \text{CIF} \times \frac{\text{BCD Rate}}{100}$$
$$\text{Social Welfare Surcharge (SWS)} = (\text{BCD} + \text{AIDC}) \times 10\%$$
$$\text{Integrated GST (IGST)} = (\text{CIF} + \text{BCD} + \text{AIDC} + \text{SWS}) \times \frac{\text{IGST Rate}}{100}$$
$$\text{Total Duty Payable} = \text{BCD} + \text{AIDC} + \text{SWS} + \text{IGST}$$
$$\text{Total Landed Cost} = \text{CIF} + \text{Total Duty} + \text{Landing/CHA Charges}$$
$$\text{Gross Margin \%} = \frac{\text{Selling Price} - \text{Landed Cost}}{\text{Selling Price}} \times 100$$

---

## 3. Database Schema Overview
1. `hs_codes`: Code (6/8/10 digits), chapter, description, unit, base_bcd_rate, sws_rate, igst_rate, aidc_rate, is_active.
2. `regulatory_circulars`: Source (`CBIC_CUSTOMS`, `DGFT`, `MINISTRY_COMMERCE`), notification_number, title, gazette_url, local_pdf_path, issue_date, effective_date, parse_status, raw_payload.
3. `tariff_revisions`: circular_id, hs_code_id, duty_type (`BCD`, `AIDC`, `ADD`, `SAFEGUARD`, `CESS`), old_rate, new_rate, delta (`new_rate - old_rate` stored generated column), action (`INCREASE`, `DECREASE`, `UNCHANGED`, `EXEMPTED`), condition_notes, effective_from.
4. `user_watchlists`: user_id, hs_code_id, baseline_fob_inr, monthly_volume_units, notify_on_increase_only, is_active.
5. `tariff_alerts`: user_id, hs_code_id, revision_id, old_rate, new_rate, delta, estimated_monthly_impact_inr, status (`UNREAD`, `READ`, `DISMISSED`), channel.

---

## 4. API Endpoints
* `GET /api/v1/revisions` - High-density filterable tariff feed (by chapter, action, source, keyword).
* `GET /api/v1/revisions/{id}` - Drilldown into notification details and condition notes.
* `POST /api/v1/calculator/compare` - Side-by-side landed cost & gross margin erosion simulator.
* `GET /api/v1/calculator/simulate/{code}` - Instant simulation preloaded by HS Code.
* `GET /api/v1/watchlist` - Current user's tracked portfolio with calculated monthly financial exposure.
* `POST /api/v1/watchlist` - Add item to watchlist.
* `PUT /api/v1/watchlist/{id}` - Update container baseline FOB or volume.
* `DELETE /api/v1/watchlist/{id}` - Remove tracked item.
* `GET /api/v1/alerts` - Portfolio duty shift alerts.
* `POST /api/v1/alerts/{id}/read` - Mark alert as read.
* `POST /api/v1/alerts/read-all` - Mark all alerts as read.
* `POST /api/v1/alerts/{id}/dismiss` - Dismiss alert.
* `GET /api/v1/circulars` - Ingested circulars registry.
* `POST /api/v1/circulars/poll` - Poll CBIC & DGFT feeds.
* `POST /api/v1/circulars/simulate` - Live Gazette Parser Testing Studio.

---

## 5. CLI Commands
* `php artisan vectis:poll-gazette {--parse}` - Scrapes circulars, parses tariff revisions, and dispatches alerts.
* `php artisan vectis:parse-circular {id?}` - Manually triggers regex/AI parser on specific circular.
* `php artisan schedule:run` - Runs scheduled ingestion tasks.
* `php artisan test` - Executes unit and feature test suites.
