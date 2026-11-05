<?php

declare(strict_types=1);

/**
 * Vectis Trade Intelligence Terminal (MVP)
 * Bloomberg-Grade Institutional Geopolitical & Tariff Terminal
 */

$dbError = null;
$dbInitialized = false;

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = db(); // connects and auto-runs pending migrations via includes/migrate.php
    $dbInitialized = true;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vectis // Geo Trade Terminal — Institutional Intelligence</title>
  
  <!-- Typography & Styling CDN -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/terminal.css">

  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            gt: {
              canvas: '#0B0F17',
              surface: '#151B26',
              border: '#1E293B',
              cyan: '#00D2FF',
              amber: '#F59E0B',
              rose: '#EF4444',
              emerald: '#10B981',
              slate: '#94A3B8',
            }
          },
          fontFamily: {
            mono: ['"JetBrains Mono"', 'monospace'],
            sans: ['Inter', 'sans-serif'],
          }
        }
      }
    }
  </script>
</head>
<body class="bg-[#0B0F17] text-[#F8FAFC] font-sans antialiased min-h-screen selection:bg-[#00D2FF] selection:text-black">

  <!-- ========================================== -->
  <!-- 1. BLOOMBERG TOP COMMAND & TICKER RIBBON  -->
  <!-- ========================================== -->
  <header class="border-b border-[#1E293B] bg-[#151B26] sticky top-0 z-40 select-none">
    <!-- Macro Indicator Ticker Ribbon -->
    <div class="px-4 py-1.5 border-b border-[#1E293B]/70 flex flex-wrap items-center justify-between text-[11px] font-mono bg-[#0B0F17]">
      <div class="flex items-center gap-4 overflow-x-auto py-0.5">
        <span class="flex items-center gap-1.5 text-[#00D2FF] font-bold">
          <span class="w-2 h-2 rounded-full bg-[#00D2FF] animate-pulse"></span>
          VECTIS TERMINAL v2.4
        </span>
        <span class="text-slate-600">|</span>
        <span class="text-[#94A3B8]">BRENT: <span class="text-[#F8FAFC] font-bold">$76.42</span> <span class="text-emerald-400">▲ +1.8%</span></span>
        <span class="text-[#94A3B8]">GOLD: <span class="text-[#F8FAFC] font-bold">$2,748/oz</span> <span class="text-emerald-400">▲ +0.9%</span></span>
        <span class="text-[#94A3B8]">US 10Y: <span class="text-[#F8FAFC] font-bold">4.28%</span> <span class="text-amber-400">▲ +4bps</span></span>
        <span class="text-[#94A3B8]">DXY: <span class="text-[#F8FAFC] font-bold">104.22</span> <span class="text-rose-400">▼ -0.2%</span></span>
        <span class="text-[#94A3B8]">BTC: <span class="text-[#F8FAFC] font-bold">$68,420</span> <span class="text-rose-400">▼ -3.4%</span></span>
      </div>
      <div id="world-clocks" class="hidden xl:flex items-center gap-2 text-slate-400">
        <!-- Live JS clocks -->
      </div>
    </div>

    <!-- Main Navigation Bar -->
    <div class="px-4 py-2.5 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-7 h-7 rounded bg-[#00D2FF]/10 border border-[#00D2FF] flex items-center justify-center font-mono font-bold text-[#00D2FF] text-sm shadow-[0_0_10px_rgba(0,210,255,0.3)]">
          V
        </div>
        <div>
          <span class="font-mono font-bold tracking-wider text-sm text-white">GEO TRADE TERMINAL</span>
          <span class="hidden sm:inline-block ml-2 px-1.5 py-0.2 rounded text-[10px] font-mono bg-cyan-950/80 text-cyan-400 border border-cyan-800">INSTITUTIONAL MACRO</span>
        </div>
      </div>

      <!-- System Diagnostic Status -->
      <div class="flex items-center gap-3 font-mono text-xs">
        <?php if ($dbInitialized): ?>
          <span class="px-2 py-0.5 rounded bg-emerald-950/70 border border-emerald-700 text-emerald-400 flex items-center gap-1.5 text-[11px]">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> DB CONNECTED (AUTO-MIGRATED)
          </span>
        <?php else: ?>
          <span class="px-2 py-0.5 rounded bg-rose-950/70 border border-rose-700 text-rose-400 flex items-center gap-1.5 text-[11px]" title="<?= htmlspecialchars((string) $dbError) ?>">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> DB ERROR (CHECK CONFIG)
          </span>
        <?php endif; ?>

        <a href="/admin/login.php" class="px-2.5 py-1 rounded border border-slate-700 bg-[#0B0F17] hover:border-[#00D2FF] text-slate-300 hover:text-white transition-all text-xs">
          ADMIN QUEUE &rarr;
        </a>
      </div>
    </div>

    <!-- Bloomberg Function Key Ribbon (F1 - F6) -->
    <div class="px-4 py-1 flex items-center gap-1 overflow-x-auto border-t border-[#1E293B] bg-[#0F141C] text-xs font-mono">
      <button data-tab-btn="f1" onclick="switchTab('f1')" class="px-3 py-1.5 rounded border border-[#00D2FF] bg-[#1E293B] text-[#00D2FF] font-bold flex items-center gap-1.5 transition-all">
        <span class="text-amber-400">[F1]</span> CRISIS RADAR
      </button>
      <button data-tab-btn="f2" onclick="switchTab('f2')" class="px-3 py-1.5 rounded border border-transparent text-[#94A3B8] hover:text-white flex items-center gap-1.5 transition-all">
        <span class="text-amber-400">[F2]</span> WINNERS &amp; LOSERS MATRIX
      </button>
      <button data-tab-btn="f3" onclick="switchTab('f3')" class="px-3 py-1.5 rounded border border-transparent text-[#94A3B8] hover:text-white flex items-center gap-1.5 transition-all">
        <span class="text-amber-400">[F3]</span> PREDICTIVE RADAR (2027 ELECTIONS)
      </button>
      <button data-tab-btn="f4" onclick="switchTab('f4')" class="px-3 py-1.5 rounded border border-transparent text-[#94A3B8] hover:text-white flex items-center gap-1.5 transition-all">
        <span class="text-amber-400">[F4]</span> PUBLISHED INTELLIGENCE DOSSIERS
      </button>
      <button data-tab-btn="f5" onclick="switchTab('f5')" class="px-3 py-1.5 rounded border border-transparent text-[#94A3B8] hover:text-white flex items-center gap-1.5 transition-all">
        <span class="text-amber-400">[F5]</span> CUSTOMS TARIFF CALCULATOR
      </button>
      <button data-tab-btn="f6" onclick="switchTab('f6')" class="px-3 py-1.5 rounded border border-transparent text-[#94A3B8] hover:text-white flex items-center gap-1.5 transition-all">
        <span class="text-amber-400">[F6]</span> SYSTEM ADMIN &amp; REVIEW
      </button>
    </div>
  </header>

  <!-- ========================================== -->
  <!-- 2. DB CONFIGURATION FALLBACK NOTICE        -->
  <!-- ========================================== -->
  <?php if ($dbError): ?>
    <div class="max-w-7xl mx-auto p-4 mt-4">
      <div class="p-4 rounded border border-rose-800 bg-rose-950/40 text-rose-300 font-mono text-xs">
        <div class="font-bold text-sm mb-1 text-white">⚠ DATABASE INITIALIZATION ALERT</div>
        <p class="mb-2">The system encountered an error connecting to MySQL: <span class="text-rose-200"><?= htmlspecialchars($dbError) ?></span></p>
        <div class="p-3 bg-black/60 rounded border border-rose-900/60 text-slate-300">
          <strong>Hostinger Quick Fix:</strong> Ensure <code>config/config.php</code> contains your hPanel MySQL database name, user, and password. The system auto-runs all migrations on next reload.
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- ========================================== -->
  <!-- 3. MAIN TERMINAL CONSOLE WORKSPACE         -->
  <!-- ========================================== -->
  <main class="max-w-[1600px] mx-auto p-3 sm:p-4">

    <!-- ---------------------------------------------------- -->
    <!-- TAB 1: [F1] CRISIS RADAR & LIVE GEOPOLITICAL MONITOR -->
    <!-- ---------------------------------------------------- -->
    <div data-tab-panel="f1" class="space-y-3">
      <!-- Category Filter Pills & Search -->
      <div class="p-2.5 bg-[#151B26] border border-[#1E293B] rounded flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-1.5 overflow-x-auto text-[11px] font-mono">
          <span class="text-[#94A3B8] mr-1">FILTER:</span>
          <button data-cat-filter="ALL" onclick="setCategoryFilter('ALL')" class="px-2.5 py-1 rounded bg-[#00D2FF] text-black font-bold">ALL EVENTS</button>
          <button data-cat-filter="war_conflict" onclick="setCategoryFilter('war_conflict')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">WAR &amp; CONFLICT</button>
          <button data-cat-filter="sanctions_peace_deal" onclick="setCategoryFilter('sanctions_peace_deal')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">SANCTIONS &amp; DEALS</button>
          <button data-cat-filter="leadership_change" onclick="setCategoryFilter('leadership_change')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">LEADERSHIP SHIFTS</button>
          <button data-cat-filter="election" onclick="setCategoryFilter('election')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">ELECTIONS</button>
          <button data-cat-filter="market_shock" onclick="setCategoryFilter('market_shock')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">MARKET SHOCKS</button>
          <button data-cat-filter="regulation_law" onclick="setCategoryFilter('regulation_law')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">TECH &amp; REGULATION</button>
          <button data-cat-filter="resource_discovery" onclick="setCategoryFilter('resource_discovery')" class="px-2.5 py-1 rounded bg-[#151B26] text-[#94A3B8] hover:text-white border border-slate-800">CRITICAL MINERALS</button>
        </div>

        <div class="relative w-full sm:w-64">
          <input type="text" oninput="filterCrisisSearch(this.value)" placeholder="Search events, commodities..." class="w-full bg-[#0B0F17] border border-[#1E293B] rounded px-3 py-1 text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:border-[#00D2FF]">
        </div>
      </div>

      <!-- Dual Pane Grid: Left Event Feed | Right Event Dossier -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        <!-- Left Pane: Event Feed -->
        <div class="lg:col-span-5 space-y-2">
          <div class="flex items-center justify-between text-xs font-mono text-[#94A3B8] px-1">
            <span>ACTIVE GEOPOLITICAL INCIDENTS</span>
            <span id="feed-loading-badge" class="hidden text-amber-400 animate-pulse">SYNCING FEED...</span>
          </div>
          <div id="crisis-list" class="max-h-[750px] overflow-y-auto pr-1">
            <!-- Rendered by JS -->
            <div class="p-8 text-center font-mono text-xs text-slate-500">INITIALIZING FEED ENGINE...</div>
          </div>
        </div>

        <!-- Right Pane: Active Incident Intelligence Briefing -->
        <div class="lg:col-span-7">
          <div id="crisis-detail-pane">
            <!-- Rendered by JS -->
          </div>
        </div>
      </div>
    </div>

    <!-- ---------------------------------------------------- -->
    <!-- TAB 2: [F2] WINNERS & LOSERS MATRIX                 -->
    <!-- ---------------------------------------------------- -->
    <div data-tab-panel="f2" class="hidden space-y-3">
      <div class="p-3 bg-[#151B26] border border-[#1E293B] rounded flex items-center justify-between">
        <div>
          <h2 class="text-sm font-bold font-mono text-white uppercase tracking-wider">CORPORATE WINNERS &amp; LOSERS MATRIX</h2>
          <div id="matrix-event-title" class="text-xs text-[#94A3B8] font-mono mt-0.5">SELECT AN INCIDENT FROM THE CRISIS MONITOR</div>
        </div>
        <div class="text-xs font-mono text-[#00D2FF]">
          HIGH-CONVICTION TRADE SECTORS
        </div>
      </div>

      <!-- Dual Column Ledger -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Beneficiaries (Gainers) -->
        <div class="p-4 bg-[#151B26] border border-[#1E293B] rounded">
          <div class="flex items-center justify-between pb-2 border-b border-emerald-900/60 mb-3">
            <span class="font-bold text-xs font-mono text-emerald-400 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              BENEFICIARY CORPORATIONS (GAINNERS)
            </span>
            <span class="text-[10px] font-mono text-slate-400">CAPITAL INFLOW</span>
          </div>
          <div id="matrix-gainers">
            <!-- Rendered by JS -->
          </div>
        </div>

        <!-- Sufferers (Losers / Restricted) -->
        <div class="p-4 bg-[#151B26] border border-[#1E293B] rounded">
          <div class="flex items-center justify-between pb-2 border-b border-rose-900/60 mb-3">
            <span class="font-bold text-xs font-mono text-rose-400 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-rose-400"></span>
              SUFFERING / RESTRICTED CORPORATIONS (LOSERS)
            </span>
            <span class="text-[10px] font-mono text-slate-400">CAPITAL OUTFLOW</span>
          </div>
          <div id="matrix-losers">
            <!-- Rendered by JS -->
          </div>
        </div>
      </div>
    </div>

    <!-- ---------------------------------------------------- -->
    <!-- TAB 3: [F3] PREDICTIVE RADAR (2027 UP ELECTIONS)     -->
    <!-- ---------------------------------------------------- -->
    <div data-tab-panel="f3" class="hidden space-y-3">
      <div class="p-4 bg-[#151B26] border border-[#1E293B] rounded">
        <div class="flex items-center justify-between mb-2">
          <h2 class="text-sm font-bold font-mono text-white">PREDICTIVE POLITICAL RADAR: 2027 UTTAR PRADESH STATE ASSEMBLY ELECTIONS</h2>
          <span class="px-2 py-0.5 rounded bg-amber-950 border border-amber-800 text-amber-400 text-xs font-mono font-bold">HORIZON: FEB 2027</span>
        </div>
        <p class="text-xs text-[#94A3B8] leading-relaxed mb-4">
          Uttar Pradesh constitutes 8.4% of Indian GDP and represents the densest civil infrastructure, expressway, and industrial corridor capital capex in the subcontinent. This radar computes sector sensitivity across Scenario Alpha (Policy Continuity) versus Scenario Beta (Agrarian Pivot).
        </p>

        <!-- Scenario Container -->
        <div id="predictive-scenarios-container">
          <!-- Rendered by JS -->
        </div>

        <!-- Macro Money Direction Deep-Dive -->
        <div class="p-4 bg-[#0B0F17] border border-slate-800 rounded">
          <div class="text-xs font-mono font-bold text-[#00D2FF] uppercase mb-2">INSTITUTIONAL ALLOCATION THESIS // "MARKET KA RUKH":</div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs font-mono">
            <div class="p-3 bg-[#151B26] border border-slate-800 rounded">
              <span class="text-emerald-400 font-bold block mb-1">CEMENT &amp; EPC INFRA (ALPHA 65%)</span>
              <p class="text-[#94A3B8] text-[11px] leading-relaxed">DIIs holding core long exposure in UltraTech Cement and L&amp;T on guaranteed dispatches for Ganga Expressway and Jewar Aerotropolis.</p>
            </div>
            <div class="p-3 bg-[#151B26] border border-slate-800 rounded">
              <span class="text-purple-400 font-bold block mb-1">RURAL FMCG HEDGE (BETA 35%)</span>
              <p class="text-[#94A3B8] text-[11px] leading-relaxed">Hedge portfolio beta by accumulating rural-facing consumer staples (Dabur, M&amp;M Tractors) 6 months ahead of state polling.</p>
            </div>
            <div class="p-3 bg-[#151B26] border border-slate-800 rounded">
              <span class="text-rose-400 font-bold block mb-1">POWER &amp; SUGAR CONCESSIONS (WATCH)</span>
              <p class="text-[#94A3B8] text-[11px] leading-relaxed">High vulnerability to state-mandated cane price revisions (SAP) and farm power tariff subsidies prior to elections.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ---------------------------------------------------- -->
    <!-- TAB 4: [F4] PUBLISHED INTELLIGENCE DOSSIERS          -->
    <!-- ---------------------------------------------------- -->
    <div data-tab-panel="f4" class="hidden space-y-3">
      <div class="p-3 bg-[#151B26] border border-[#1E293B] rounded flex items-center justify-between">
        <h2 class="text-sm font-bold font-mono text-white uppercase">INSTITUTIONAL RESEARCH &amp; MACRO DOSSIERS</h2>
        <span class="text-xs font-mono text-[#94A3B8]">FULL RESEARCH DISCLOSURES</span>
      </div>
      <div id="dossiers-list">
        <!-- Rendered by JS -->
      </div>
    </div>

    <!-- ---------------------------------------------------- -->
    <!-- TAB 5: [F5] CUSTOMS TARIFF CALCULATOR                -->
    <!-- ---------------------------------------------------- -->
    <div data-tab-panel="f5" class="hidden space-y-3">
      <div class="p-4 bg-[#151B26] border border-[#1E293B] rounded">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
          <div>
            <h2 class="text-sm font-bold font-mono text-white">CUSTOMS VALUATION &amp; LANDED COST SIMULATOR</h2>
            <div class="text-xs text-[#94A3B8] font-mono">Deterministic valuation: CIF &rarr; BCD &rarr; SWS &rarr; IGST with Margin Variance Delta</div>
          </div>
          <span class="px-2 py-0.5 rounded bg-cyan-950 border border-cyan-800 text-cyan-400 text-xs font-mono font-bold">INDIA CBIC / DGFT ENGINE</span>
        </div>

        <!-- Calculator Input Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 font-mono text-xs">
          <!-- HS Search -->
          <div class="md:col-span-4 p-3 bg-[#0B0F17] border border-slate-800 rounded">
            <label class="block text-[10px] text-[#94A3B8] mb-1">SEARCH MASTER HS CODE (AUTO-FILL RATES):</label>
            <input type="text" id="calc-hs-search" placeholder="Type 2933, 8541, 8504, 8542..." class="w-full bg-[#151B26] border border-slate-700 rounded px-3 py-1.5 text-xs text-white focus:outline-none focus:border-[#00D2FF]">
            <div id="calc-hs-desc" class="text-[10px] text-cyan-400 mt-1">[EXAMPLE] 2933.99 - Organic compounds (APIs) n.e.s.</div>
          </div>

          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">UNIT FOB VALUE (USD):</label>
            <input type="number" id="calc-fob" value="10000" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
          </div>
          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">OCEAN / AIR FREIGHT (USD):</label>
            <input type="number" id="calc-freight" value="1200" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
          </div>
          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">TRANSIT INSURANCE (USD):</label>
            <input type="number" id="calc-insurance" value="100" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
          </div>
          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">USD / INR EXCHANGE RATE:</label>
            <input type="number" id="calc-rate" value="84.50" step="0.01" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
          </div>

          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">CURRENT BCD RATE (%):</label>
            <input type="number" id="calc-bcd" value="15.0" step="0.1" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-amber-400 font-bold">
          </div>
          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">PREVIOUS BCD RATE (% - FOR DELTA):</label>
            <input type="number" id="calc-old-bcd" value="10.0" step="0.1" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-slate-400">
          </div>
          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">SWS SURCHARGE (% ON BCD):</label>
            <input type="number" id="calc-sws" value="10.0" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
          </div>
          <div>
            <label class="block text-[10px] text-[#94A3B8] mb-1">INTEGRATED GST (IGST %):</label>
            <input type="number" id="calc-igst" value="18.0" class="w-full bg-[#0B0F17] border border-slate-700 rounded px-3 py-1.5 text-xs text-white">
          </div>
        </div>

        <button id="btn-run-simulation" class="w-full py-2.5 rounded bg-[#00D2FF] hover:bg-cyan-400 text-black font-mono font-bold text-xs tracking-wider transition-all shadow-[0_0_15px_rgba(0,210,255,0.25)]">
          EXECUTE VALUATION &amp; DELTA ANALYSIS
        </button>

        <!-- Results Output Pane -->
        <div id="calculator-results" class="mt-4">
          <!-- Computed dynamically -->
        </div>
      </div>
    </div>

    <!-- ---------------------------------------------------- -->
    <!-- TAB 6: [F6] ADMIN REVIEW & SYSTEM MONITOR            -->
    <!-- ---------------------------------------------------- -->
    <div data-tab-panel="f6" class="hidden space-y-3">
      <div class="p-5 bg-[#151B26] border border-[#1E293B] rounded font-mono">
        <h2 class="text-sm font-bold text-white mb-2">SYSTEM ADMINISTRATION &amp; EDITORIAL QUEUE</h2>
        <p class="text-xs text-[#94A3B8] mb-4">
          Access the backend review panel to inspect incoming regulatory notifications, quarantine anomalous tariff revisions, review AI-drafted macro intelligence briefings, and publish research reports.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-[#0B0F17] border border-slate-800 rounded">
            <span class="text-xs text-white font-bold block mb-1">EDITORIAL REVIEW WORKFLOW</span>
            <p class="text-[11px] text-slate-400 mb-3">All geopolitical incident reports pass human review (Approve / Request Changes / Reject) before public syndication.</p>
            <a href="/admin/index.php" class="inline-block px-4 py-2 bg-[#00D2FF] text-black font-bold text-xs rounded hover:bg-cyan-400 transition-all">
              OPEN REVIEW QUEUE &rarr;
            </a>
          </div>

          <div class="p-4 bg-[#0B0F17] border border-slate-800 rounded">
            <span class="text-xs text-white font-bold block mb-1">DATABASE &amp; HEALTH CHECK</span>
            <p class="text-[11px] text-slate-400 mb-3">Confirm database connection and verify applied schema migrations.</p>
            <a href="/ajax/health.php" target="_blank" class="inline-block px-4 py-2 bg-[#1E293B] text-[#00D2FF] font-bold text-xs rounded border border-slate-700 hover:border-[#00D2FF] transition-all">
              RUN /ajax/health.php &rarr;
            </a>
          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- ========================================== -->
  <!-- 4. SLIDE-OVER CORPORATE INTELLIGENCE DRAWER -->
  <!-- ========================================== -->
  <div id="company-profile-drawer" class="fixed inset-y-0 right-0 w-full sm:w-[540px] bg-[#151B26] border-l border-[#1E293B] z-50 transform translate-x-full transition-transform duration-300 shadow-2xl overflow-y-auto">
    <div id="company-drawer-content">
      <!-- Injected by openCompanyDrawer() in JS -->
    </div>
  </div>

  <!-- Terminal JS Engine -->
  <script src="assets/js/terminal-app.js"></script>
</body>
</html>
