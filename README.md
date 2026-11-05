# GeoTrade Terminal — Bloomberg-Grade Geopolitical & Trade Intelligence

An institutional-grade regulatory, geopolitical, and tariff intelligence engine engineered specifically for **Hostinger shared (cPanel / Apache / PHP 8.2+) hosting**:
* **Zero build steps required** (no Node.js, Vite, Composer, or artisan daemons required on the production server).
* **Self-Healing Auto-Migrations**: Every numbered `.sql` migration in `sql/migrations/` automatically executes on page load (`includes/migrate.php`). No manual phpMyAdmin import needed.
* **Bloomberg Terminal Dark-First UI**: High information density (`#0B0F17` Obsidian Canvas, `#151B26` Panel Surface, `#1E293B` Border, `#00D2FF` Cyan, `#F59E0B` Amber, `#10B981` Green, `#EF4444` Red, monospace typography).

---

## 1. Core Platform Capabilities

### [F1] Geopolitical Crisis Monitor & Radar
Real-time tracking of global and national events moving trade flows and capital:
* **Venezuela Regime Change & Maduro Indictment**: US interim governance impact on heavy sour Orinoco crude, OFAC General License 41, PDVSA debt.
* **Israel-Iran Escalation & Red Sea Chokepoint Disruption**: Bab-el-Mandeb closure, Cape of Good Hope rerouting, 35% ton-mile demand surge, jet fuel crack spreads.
* **Russia-Ukraine US-Brokered Peace Framework Talks**: European reconstruction ($480B fund), Black Sea ammonia/grain corridor normalization, European defense multiple cooling.
* **Binance Hot-Wallet Security Exploitation**: $840M drain, capital flight to US SEC-regulated Qualified Custodians (Coinbase Prime).
* **Alphabet / Google DOJ Antitrust Remedies**: Forced AdTech unbundling, open web auction migration to independent DSPs (The Trade Desk).
* **2027 Uttar Pradesh State Assembly Elections**: Predictive political sensitivity analysis for India's 8.4% GDP industrial powerhouse.
* **France Political Gridlock & EU Sovereign Spread Crisis**: French OAT-Bund 10Y spread widening, banking capital adequacy stress (BNP Paribas).
* **Critical Minerals & Lithium Realignment**: IRA-compliant brine extractors (Albemarle, SQM) vs non-FTA exclusion.

### [F2] Winners & Losers Matrix & Interactive Corporate Dossiers
* **Dual-Column Corporate Ledger**: Side-by-side comparison of beneficiaries (gainers) vs suffering entities (losers).
* **Interactive Corporate Profile Drawer**: Click `[+] VIEW CORPORATE PROFILE` on any company (Chevron, Lockheed Martin, Frontline, L&T, UltraTech, Coinbase, The Trade Desk, Rosneft, Lufthansa) to open:
  * Multi-Year Historical Geopolitical Activity Track Record
  * Geopolitical Revenue Risk Exposure breakdown
  * Key Financial & Geopolitical Risk Metrics (Operating Margin, Debt/Equity, Free Cash Flow, Geopolitical Beta)
  * Actionable Institutional Verdict

### [F3] Predictive Radar: 2027 Uttar Pradesh State Elections
* **Scenario Alpha (65% Probability) — "Industrial Continuity & Expressway Megaprojects"**:
  * Uninterrupted capex continuity for Ganga Expressway, Jewar Aerotropolis, and Defence Corridor.
  * Gainers: Larsen & Toubro, UltraTech Cement, Heavy EPC.
* **Scenario Beta (35% Probability) — "Populist Agrarian Rebalancing & Direct Subsidies"**:
  * State budget reorientation to farmer loan waivers, free electricity, and rural transfers.
  * Gainers: Dabur India, Mahindra & Mahindra Tractors, Rural FMCG.
* **Capital Flow Vector ("Market ka rukh")**: Directional guidance for portfolio allocation.

### [F4] Published Intelligence Dossiers
* Deep analytical research papers with Executive Summaries, Strategic Commodities Affected, and printable dossier export.

### [F5] Customs Tariff Landed Cost Calculator
Deterministic valuation engine for Indian Customs (CBIC / DGFT):
$$\text{CIF (Assessable Value)} = \text{FOB Value} + \text{Freight} + \text{Insurance}$$
$$\text{BCD} = \text{CIF} \times \text{BCD Rate}$$
$$\text{SWS} = \text{BCD} \times 10\%$$
$$\text{IGST} = (\text{CIF} + \text{BCD} + \text{SWS} + \text{AIDC}) \times \text{IGST Rate}$$
* Instant computation of Total Customs Duty, Landed Cost in INR & USD, Effective Duty Rate, and Tariff Variance Delta & Margin Impact %.
* Indexed autocomplete for Master HS Codes (Chapters 28, 29, 84, 85).

### [F6] System Admin & Editorial Review
* Session-secured backend review queue (`/admin/index.php`) to approve, request changes, or reject incoming reports.

---

## 2. Directory Structure

```
config/
  config.sample.php    # Copy to config.php on Hostinger (never commit config.php)
  database.php         # PDO singleton with automatic migration execution
includes/
  helpers.php          # JSON responses, CSRF token generation, escaping
  auth.php             # Session-based admin authentication
  migrate.php          # Auto-migration runner (idempotent, quote-aware statement parser)
assets/
  css/terminal.css     # Bloomberg Dark CSS variables & tokens
  js/terminal-app.js   # Reactive terminal controller (Tabs F1-F6, drawer, calculator)
  js/public.js         # Dedicated dossier page renderer
  js/admin.js          # Admin review queue controller
ajax/
  health.php           # DB connection & applied migrations status endpoint
  reports.php          # Public published reports feed
  report.php           # Public single report detail with company dossiers
  calculator.php       # Customs valuation & delta calculator engine
  hs_codes.php         # HS Code autocomplete search endpoint
  admin/               # Session-protected review & moderation endpoints
admin/
  index.php            # Editorial review queue
  login.php / logout.php # Admin authentication
sql/migrations/
  0001_initial_schema.sql
  0002_seed_admin_and_duty_sample.sql
  0003_seed_macro_reports.sql
  0004_comprehensive_geopolitical_events_and_reports.sql
index.php              # Main Bloomberg Terminal Dashboard
report.php             # Deep-link dossier detail view
```

---

## 3. Deployment on Hostinger

1. **Create MySQL Database**:
   In Hostinger hPanel &rarr; **Databases** &rarr; **MySQL Databases**, create a database and database user (note the `u123456789_...` names).
2. **Push to GitHub**:
   Pushing to `main` auto-deploys via your Hostinger Git deployment webhook into `public_html/`.
3. **Configure Database Credentials**:
   In Hostinger File Manager, copy `config/config.sample.php` to `config/config.php` and fill in:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_your_db');
   define('DB_USER', 'u123456789_your_user');
   define('DB_PASS', 'your_db_password');
   ```
4. **Visit Your Domain**:
   Open `https://yourdomain.com/` in your browser. The first page load automatically runs all 4 SQL migrations in order, creates all tables, seeds the master data, and boots the terminal live.
5. **Verify**:
   Hit `https://yourdomain.com/ajax/health.php` to confirm `status: "ok"` and `tables_found: 14`.
