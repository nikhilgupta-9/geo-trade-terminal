/**
 * Vectis Trade Intelligence Terminal - Bloomberg Institutional Controller
 * High-density reactive terminal engine: Crisis Radar, Winners & Losers Matrix,
 * Interactive Corporate Dossiers, 2027 Predictive Scenarios, and Landed Cost Engine.
 */

// Global State
window.terminalState = {
    activeTab: 'f1', // f1: crisis, f2: matrix, f3: predictive, f4: dossiers, f5: customs, f6: admin
    reports: [],
    selectedReportId: null,
    selectedReport: null,
    selectedCompany: null,
    filterCategory: 'ALL',
    searchQuery: '',
    calculatorResult: null,
    predictiveScenario: 'alpha', // alpha or beta
};

// Base path resolver (works at domain root or inside a subfolder like /geo-trade-terminal/)
const BASE_PATH = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
const apiUrl = (endpoint) => `${BASE_PATH}${endpoint.startsWith('/') ? endpoint : '/' + endpoint}`;

// Formatting helpers
function formatCurrency(val, currency = 'USD') {
    if (val === null || val === undefined || isNaN(val)) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        maximumFractionDigits: 2
    }).format(val);
}

function formatInr(val) {
    if (val === null || val === undefined || isNaN(val)) return '₹0.00';
    return '₹' + Number(val).toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Category formatting
const CATEGORY_NAMES = {
    war_conflict: 'WAR / CONFLICT',
    sanctions_peace_deal: 'SANCTIONS & PEACE DEAL',
    election: 'ELECTION / POLITICAL',
    leadership_change: 'LEADERSHIP CHANGE',
    regulation_law: 'REGULATION & LAW',
    civil_unrest: 'CIVIL UNREST / PROTESTS',
    market_shock: 'MARKET SHOCK',
    resource_discovery: 'RESOURCE DISCOVERY',
    trade_policy: 'TRADE POLICY'
};

const STANCE_NAMES = {
    risk_on: 'RISK-ON (EXPANSIONARY)',
    risk_off: 'RISK-OFF (DEFENSIVE FLIGHT)',
    sector_rotation: 'SECTOR ROTATION (CAPITAL REALIGNMENT)',
    neutral_uncertain: 'NEUTRAL / UNCERTAIN'
};

// Initialize Terminal
async function initTerminal() {
    startWorldClocks();
    setupKeyboardShortcuts();
    await loadReportsFeed();
    setupCalculator();
}

// World Clocks Ticker
function startWorldClocks() {
    function update() {
        const now = new Date();
        const fmt = (tz) => now.toLocaleTimeString('en-US', { timeZone: tz, hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        const el = document.getElementById('world-clocks');
        if (el) {
            el.innerHTML = `
                <span class="text-[#94A3B8]">UTC:</span> <span class="text-white">${fmt('UTC')}</span> &bull; 
                <span class="text-[#94A3B8]">NYC:</span> <span class="text-white">${fmt('America/New_York')}</span> &bull; 
                <span class="text-[#94A3B8]">LON:</span> <span class="text-white">${fmt('Europe/London')}</span> &bull; 
                <span class="text-[#94A3B8]">DEL:</span> <span class="text-white">${fmt('Asia/Kolkata')}</span> &bull; 
                <span class="text-[#94A3B8]">SGP:</span> <span class="text-white">${fmt('Asia/Singapore')}</span> &bull; 
                <span class="text-[#94A3B8]">TYO:</span> <span class="text-white">${fmt('Asia/Tokyo')}</span>
            `;
        }
    }
    update();
    setInterval(update, 1000);
}

// Keyboard Function Keys Navigation (F1 - F6)
function setupKeyboardShortcuts() {
    window.addEventListener('keydown', (e) => {
        if (['F1', 'F2', 'F3', 'F4', 'F5', 'F6'].includes(e.key)) {
            e.preventDefault();
            const tabKey = e.key.toLowerCase();
            switchTab(tabKey);
        }
    });
}

// Tab Switching
function switchTab(tabId) {
    window.terminalState.activeTab = tabId;
    
    // Update button styling
    document.querySelectorAll('[data-tab-btn]').forEach(btn => {
        const id = btn.getAttribute('data-tab-btn');
        if (id === tabId) {
            btn.classList.add('bg-[#1E293B]', 'text-[#00D2FF]', 'border-[#00D2FF]');
            btn.classList.remove('text-[#94A3B8]', 'border-transparent');
        } else {
            btn.classList.remove('bg-[#1E293B]', 'text-[#00D2FF]', 'border-[#00D2FF]');
            btn.classList.add('text-[#94A3B8]', 'border-transparent');
        }
    });

    // Update tab panels
    document.querySelectorAll('[data-tab-panel]').forEach(panel => {
        const id = panel.getAttribute('data-tab-panel');
        if (id === tabId) {
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    });
}

// Load Reports Feed from /ajax/reports.php
async function loadReportsFeed() {
    const feedContainer = document.getElementById('crisis-feed-container');
    const loadingBadge = document.getElementById('feed-loading-badge');
    
    try {
        if (loadingBadge) loadingBadge.classList.remove('hidden');
        const res = await fetch(apiUrl('/ajax/reports.php'));
        const data = await res.json();
        
        if (!res.ok || data.status !== 'ok') {
            throw new Error(data?.error?.message || 'Failed to fetch reports.');
        }

        window.terminalState.reports = data.reports || [];
        
        if (window.terminalState.reports.length > 0) {
            // Select first report by default if none selected
            if (!window.terminalState.selectedReportId) {
                window.terminalState.selectedReportId = window.terminalState.reports[0].id;
            }
            await selectReport(window.terminalState.selectedReportId);
        }

        renderCrisisList();
        renderDossiersList();
    } catch (err) {
        console.error('Feed error:', err);
        if (feedContainer) {
            feedContainer.innerHTML = `
                <div class="p-6 text-center border border-rose-900/60 bg-rose-950/20 text-rose-400 font-mono text-xs">
                    [ERROR] Failed to load intelligence feed: ${escapeHtml(err.message)}
                </div>
            `;
        }
    } finally {
        if (loadingBadge) loadingBadge.classList.add('hidden');
    }
}

// Select a Report and fetch full detail
async function selectReport(reportId) {
    window.terminalState.selectedReportId = reportId;
    renderCrisisList(); // update active card highlight

    try {
        const res = await fetch(apiUrl(`/ajax/report.php?id=${encodeURIComponent(reportId)}`));
        const data = await res.json();
        if (data.status === 'ok') {
            window.terminalState.selectedReport = data.report;
            renderSelectedCrisisDetails();
            renderWinnersLosersMatrix();
            renderPredictiveTab();
        }
    } catch (err) {
        console.error('Detail fetch error:', err);
    }
}

// Render Crisis Feed (Tab 1)
function renderCrisisList() {
    const container = document.getElementById('crisis-list');
    if (!container) return;

    const filtered = window.terminalState.reports.filter(r => {
        if (window.terminalState.filterCategory !== 'ALL' && r.event.category !== window.terminalState.filterCategory) {
            return false;
        }
        if (window.terminalState.searchQuery) {
            const q = window.terminalState.searchQuery.toLowerCase();
            return r.headline.toLowerCase().includes(q) || 
                   r.event.title.toLowerCase().includes(q) || 
                   (r.event.countries || []).join(' ').toLowerCase().includes(q);
        }
        return true;
    });

    if (filtered.length === 0) {
        container.innerHTML = `<div class="p-4 text-xs font-mono text-[#94A3B8] border border-slate-800 bg-[#0B0F17]">NO EVENTS FOUND MATCHING CURRENT FILTER.</div>`;
        return;
    }

    container.innerHTML = filtered.map(r => {
        const isSelected = r.id === window.terminalState.selectedReportId;
        const sevClass = r.event.severity === 'critical' ? 'text-rose-400 bg-rose-950/50 border-rose-700' : 
                         r.event.severity === 'high' ? 'text-amber-400 bg-amber-950/50 border-amber-700' : 'text-cyan-400 bg-cyan-950/50 border-cyan-800';

        const stanceClass = r.market_stance === 'risk_on' ? 'text-emerald-400 border-emerald-800 bg-emerald-950/30' :
                            r.market_stance === 'risk_off' ? 'text-rose-400 border-rose-800 bg-rose-950/30' : 'text-purple-400 border-purple-800 bg-purple-950/30';

        return `
            <div onclick="selectReport(${r.id})" class="p-3.5 mb-2.5 rounded border cursor-pointer transition-all ${isSelected ? 'bg-[#1E293B] border-[#00D2FF] shadow-[0_0_12px_rgba(0,210,255,0.15)]' : 'bg-[#151B26] border-slate-800 hover:border-slate-700'}">
                <div class="flex items-center justify-between text-[10px] font-mono mb-1.5">
                    <span class="px-1.5 py-0.5 rounded border ${sevClass} font-bold">${escapeHtml(r.event.severity ? r.event.severity.toUpperCase() : 'HIGH')}</span>
                    <span class="text-[#94A3B8]">${escapeHtml(r.event.event_date || 'RECENT')}</span>
                </div>
                <div class="font-bold text-xs text-[#F8FAFC] leading-snug mb-1 font-mono">${escapeHtml(r.headline)}</div>
                <div class="text-[11px] text-[#94A3B8] line-clamp-1 mb-2">${escapeHtml(r.event.title)}</div>
                <div class="flex items-center justify-between pt-1.5 border-t border-slate-800/80 text-[10px] font-mono">
                    <span class="text-cyan-400">${escapeHtml((r.event.countries || []).join(' / '))}</span>
                    <span class="px-1.5 py-0.5 rounded border ${stanceClass}">${escapeHtml(r.market_stance ? r.market_stance.toUpperCase() : 'SECTOR')}</span>
                </div>
            </div>
        `;
    }).join('');
}

// Filter Crisis Feed by Category
function setCategoryFilter(category) {
    window.terminalState.filterCategory = category;
    document.querySelectorAll('[data-cat-filter]').forEach(btn => {
        if (btn.getAttribute('data-cat-filter') === category) {
            btn.classList.add('bg-[#00D2FF]', 'text-black', 'font-bold');
            btn.classList.remove('bg-[#151B26]', 'text-[#94A3B8]');
        } else {
            btn.classList.remove('bg-[#00D2FF]', 'text-black', 'font-bold');
            btn.classList.add('bg-[#151B26]', 'text-[#94A3B8]');
        }
    });
    renderCrisisList();
}

// Search Crisis Feed
function filterCrisisSearch(query) {
    window.terminalState.searchQuery = query;
    renderCrisisList();
}

// Render Selected Crisis Details (Right Pane in Tab 1)
function renderSelectedCrisisDetails() {
    const r = window.terminalState.selectedReport;
    const container = document.getElementById('crisis-detail-pane');
    if (!container || !r) return;

    const stanceBadge = r.market_stance === 'risk_on' ? 'border-emerald-500 bg-emerald-950/40 text-emerald-300' :
                        r.market_stance === 'risk_off' ? 'border-rose-500 bg-rose-950/40 text-rose-300' : 'border-purple-500 bg-purple-950/40 text-purple-300';

    const resourcesHtml = (r.event.resources_affected || []).length > 0 
        ? r.event.resources_affected.map(res => `<span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-800 text-amber-300 text-[10px] font-mono mr-1.5 mb-1 inline-block">⚡ ${escapeHtml(res)}</span>`).join('')
        : '<span class="text-[10px] font-mono text-[#94A3B8]">None specified</span>';

    const industriesHtml = (r.industry_impacts || []).map(ind => {
        const dirClass = ind.direction === 'gainer' ? 'text-emerald-400 bg-emerald-950/30 border-emerald-800' :
                         ind.direction === 'loser' ? 'text-rose-400 bg-rose-950/30 border-rose-800' : 'text-purple-400 bg-purple-950/30 border-purple-800';
        return `
            <div class="p-2.5 mb-2 rounded border border-slate-800 bg-[#0B0F17] flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold font-mono text-xs text-white">${escapeHtml(ind.industry)}</span>
                        <span class="px-1.5 py-0.2 rounded border text-[9px] font-mono uppercase ${dirClass}">${escapeHtml(ind.direction)} (SEV ${ind.severity}/5)</span>
                    </div>
                    <div class="text-[11px] text-[#94A3B8] leading-relaxed">${escapeHtml(ind.rationale)}</div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = `
        <div class="p-4 bg-[#151B26] border border-slate-800 rounded">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3 text-xs font-mono">
                <span class="text-[#00D2FF] font-bold">EVENT REF #${r.id} // ${escapeHtml(CATEGORY_NAMES[r.event.category] || r.event.category)}</span>
                <span class="text-[#94A3B8]">GEO: ${escapeHtml((r.event.countries || []).join(', '))}</span>
            </div>

            <h1 class="text-base font-bold font-mono text-white mb-2 leading-snug">${escapeHtml(r.headline)}</h1>
            <p class="text-xs text-[#94A3B8] mb-4 leading-relaxed">${escapeHtml(r.event.summary)}</p>

            <!-- Market Direction Banner (Market ka rukh) -->
            <div class="p-3 mb-4 rounded border ${stanceBadge}">
                <div class="flex items-center justify-between text-xs font-mono font-bold mb-1">
                    <span>MARKET DIRECTION // ${escapeHtml(STANCE_NAMES[r.market_stance] || r.market_stance)}</span>
                    <span>SENTIMENT: ${r.market_stance.toUpperCase()}</span>
                </div>
                <div class="text-xs text-[#F8FAFC] mb-2 leading-relaxed">${escapeHtml(r.market_summary)}</div>
                <div class="text-[11px] font-mono text-amber-200/90 pt-2 border-t border-slate-700/60">
                    <span class="font-bold text-amber-400">CAPITAL FLOW VECTOR:</span> ${escapeHtml(r.market_flow_note)}
                </div>
            </div>

            <!-- Affected Natural Resources & Commodities -->
            <div class="p-3 mb-4 bg-[#0B0F17] border border-slate-800 rounded">
                <div class="text-[10px] font-mono font-bold text-[#94A3B8] uppercase mb-1.5">STRATEGIC COMMODITIES & NATURAL RESOURCES IMPACTED:</div>
                <div class="flex flex-wrap">${resourcesHtml}</div>
            </div>

            <!-- Industry Impact Breakdown -->
            <div>
                <div class="flex items-center justify-between text-xs font-mono text-[#00D2FF] font-bold mb-2">
                    <span>SECTOR-LEVEL TRANSMISSION MECHANISM</span>
                    <button onclick="switchTab('f2')" class="text-[10px] text-amber-400 hover:underline">VIEW WINNERS/LOSERS MATRIX [F2] &rarr;</button>
                </div>
                ${industriesHtml}
            </div>
        </div>
    `;
}

// Render Winners & Losers Matrix (Tab 2)
function renderWinnersLosersMatrix() {
    const r = window.terminalState.selectedReport;
    const gainersContainer = document.getElementById('matrix-gainers');
    const losersContainer = document.getElementById('matrix-losers');
    const headerTitle = document.getElementById('matrix-event-title');
    
    if (!gainersContainer || !losersContainer || !r) return;

    if (headerTitle) {
        headerTitle.innerHTML = `CRISIS CONTEXT: <span class="text-[#00D2FF]">${escapeHtml(r.headline)}</span>`;
    }

    const gainers = (r.company_impacts || []).filter(c => c.direction === 'gainer');
    const losers = (r.company_impacts || []).filter(c => c.direction === 'loser' || c.direction === 'mixed' || c.direction === 'watch');

    function renderCompanyCard(c, isGainer) {
        const borderClass = isGainer ? 'border-emerald-900/60 hover:border-emerald-500 bg-[#0F1C16]' : 'border-rose-900/60 hover:border-rose-500 bg-[#1C0F13]';
        const badgeClass = isGainer ? 'bg-emerald-950 border-emerald-700 text-emerald-300' : 'bg-rose-950 border-rose-700 text-rose-300';
        
        return `
            <div class="p-3.5 mb-3 rounded border ${borderClass} transition-all">
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-xs text-white font-mono">${escapeHtml(c.company_name)}</span>
                        ${c.ticker ? `<span class="px-1.5 py-0.2 bg-black border border-slate-700 rounded text-[10px] font-mono text-cyan-400 font-bold">${escapeHtml(c.ticker)}</span>` : ''}
                    </div>
                    <span class="px-2 py-0.5 rounded border text-[9px] font-mono uppercase ${badgeClass}">${escapeHtml(c.direction)}</span>
                </div>
                
                <div class="text-[10px] font-mono text-[#94A3B8] mb-2">
                    ${c.sector ? `SECTOR: ${escapeHtml(c.sector)}` : ''} 
                    ${c.market_cap ? `&bull; CAP: <span class="text-white">${escapeHtml(c.market_cap)}</span>` : ''}
                    ${c.exchange ? `&bull; ${escapeHtml(c.exchange)}` : ''}
                </div>

                <div class="text-xs text-[#E2E8F0] mb-3 leading-relaxed">
                    ${escapeHtml(c.rationale)}
                </div>

                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="text-[10px] font-mono text-[#94A3B8]">${escapeHtml(c.country)}</span>
                    <button onclick="openCompanyDrawer('${escapeHtml(c.company_name)}')" class="px-2.5 py-1 rounded bg-[#1E293B] hover:bg-[#00D2FF] hover:text-black text-[10px] font-mono text-[#00D2FF] transition-all flex items-center gap-1 font-bold">
                        <span>[+] VIEW CORPORATE PROFILE & ACTIVITY</span>
                    </button>
                </div>
            </div>
        `;
    }

    gainersContainer.innerHTML = gainers.length > 0 
        ? gainers.map(c => renderCompanyCard(c, true)).join('')
        : `<div class="p-4 text-xs font-mono text-[#94A3B8] border border-slate-800 bg-[#0B0F17]">NO BENEFICIARY COMPANIES LOGGED FOR THIS CRISIS YET.</div>`;

    losersContainer.innerHTML = losers.length > 0 
        ? losers.map(c => renderCompanyCard(c, false)).join('')
        : `<div class="p-4 text-xs font-mono text-[#94A3B8] border border-slate-800 bg-[#0B0F17]">NO SUFFERING / RESTRICTED COMPANIES LOGGED FOR THIS CRISIS YET.</div>`;
}

// Open Corporate Profile Intelligence Drawer
function openCompanyDrawer(companyName) {
    const r = window.terminalState.selectedReport;
    if (!r) return;

    const company = (r.company_impacts || []).find(c => c.company_name === companyName);
    if (!company) return;

    window.terminalState.selectedCompany = company;
    const drawer = document.getElementById('company-profile-drawer');
    const content = document.getElementById('company-drawer-content');
    if (!drawer || !content) return;

    // Financial Metrics Tags
    const metrics = company.financial_metrics || {};
    let metricsHtml = '';
    for (const [k, v] of Object.entries(metrics)) {
        const label = k.replace(/_/g, ' ').toUpperCase();
        metricsHtml += `
            <div class="p-2 rounded bg-[#0B0F17] border border-slate-800">
                <div class="text-[9px] font-mono text-[#94A3B8]">${escapeHtml(label)}</div>
                <div class="text-xs font-mono font-bold text-cyan-400">${escapeHtml(String(v))}</div>
            </div>
        `;
    }
    if (!metricsHtml) metricsHtml = '<div class="text-xs text-[#94A3B8] font-mono">Metrics not populated.</div>';

    // Multi-Year Activity Log
    const activities = company.recent_activity || [];
    const activitiesHtml = activities.length > 0 ? activities.map(a => `
        <div class="pl-3 border-l-2 border-[#00D2FF] mb-2.5">
            <div class="text-[10px] font-mono font-bold text-amber-400">${escapeHtml(a.period)}</div>
            <div class="text-xs text-[#E2E8F0]">${escapeHtml(a.note)}</div>
        </div>
    `).join('') : '<div class="text-xs text-[#94A3B8] font-mono">No historical multi-year records on file.</div>';

    content.innerHTML = `
        <div class="p-5">
            <!-- Header -->
            <div class="flex items-start justify-between pb-4 border-b border-slate-800 mb-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-base font-bold font-mono text-white">${escapeHtml(company.company_name)}</h2>
                        ${company.ticker ? `<span class="px-2 py-0.5 bg-black border border-cyan-500 rounded text-xs font-mono font-bold text-cyan-400">${escapeHtml(company.ticker)}</span>` : ''}
                    </div>
                    <div class="text-[11px] font-mono text-[#94A3B8]">
                        EXCHANGE: <span class="text-white">${escapeHtml(company.exchange || 'NYSE')}</span> &bull; 
                        MARKET CAP: <span class="text-emerald-400 font-bold">${escapeHtml(company.market_cap || 'N/A')}</span> &bull; 
                        HQ: <span class="text-white">${escapeHtml(company.country)}</span>
                    </div>
                </div>
                <button onclick="closeCompanyDrawer()" class="p-1 text-slate-400 hover:text-white font-mono text-sm">[ESC &times;]</button>
            </div>

            <!-- Overview -->
            <div class="mb-4">
                <div class="text-[10px] font-mono font-bold text-[#94A3B8] uppercase mb-1">COMPANY PROFILE & CORPORATE FOCUS:</div>
                <p class="text-xs text-[#E2E8F0] leading-relaxed bg-[#0B0F17] p-3 rounded border border-slate-800">
                    ${escapeHtml(company.overview || 'Institutional global corporate operator active across international trade routes.')}
                </p>
            </div>

            <!-- Multi-Year Activity Track Record (User requirement) -->
            <div class="mb-4">
                <div class="text-[10px] font-mono font-bold text-amber-400 uppercase mb-2 flex items-center justify-between">
                    <span>MULTI-YEAR GEOPOLITICAL ACTIVITY TRACK RECORD:</span>
                    <span class="text-[9px] text-[#94A3B8]">HISTORICAL PRECEDENTS</span>
                </div>
                <div class="p-3 bg-[#0B0F17] border border-slate-800 rounded">
                    ${activitiesHtml}
                </div>
            </div>

            <!-- Key Financial & Geopolitical Risk Metrics -->
            <div class="mb-4">
                <div class="text-[10px] font-mono font-bold text-[#00D2FF] uppercase mb-2">FINANCIAL RISK METRICS & GEOPOLITICAL BETA:</div>
                <div class="grid grid-cols-2 gap-2">
                    ${metricsHtml}
                </div>
            </div>

            <!-- Geopolitical Revenue Exposure -->
            <div class="mb-4">
                <div class="text-[10px] font-mono font-bold text-[#94A3B8] uppercase mb-1">REVENUE EXPOSURE & GEOPOLITICAL RISK CHANNELS:</div>
                <div class="p-2.5 rounded bg-[#0B0F17] border border-slate-800 text-xs text-[#CBD5E1] font-mono">
                    ${escapeHtml(company.revenue_exposure_geo || 'Global exposure to international shipping and foreign tariff adjustments.')}
                </div>
            </div>

            <!-- Institutional Stance Rationale -->
            <div>
                <div class="text-[10px] font-mono font-bold text-emerald-400 uppercase mb-1">VECTIS TRADE TERMINAL VERDICT:</div>
                <div class="p-3 rounded bg-emerald-950/20 border border-emerald-900/50 text-xs text-[#F1F5F9] leading-relaxed">
                    ${escapeHtml(company.rationale)}
                </div>
            </div>
        </div>
    `;

    drawer.classList.remove('translate-x-full');
}

function closeCompanyDrawer() {
    const drawer = document.getElementById('company-profile-drawer');
    if (drawer) drawer.classList.add('translate-x-full');
}

// Render Tab 3: Predictive Radar (2027 UP Elections)
function renderPredictiveTab() {
    // Locate the UP 2027 report in state
    const upReport = window.terminalState.reports.find(r => r.event.timing === 'predictive' || r.headline.includes('UP 2027'));
    const container = document.getElementById('predictive-scenarios-container');
    if (!container) return;

    if (!upReport) {
        container.innerHTML = `<div class="p-6 text-xs font-mono text-[#94A3B8]">PREDICTIVE RADAR CURRENTLY CALIBRATING.</div>`;
        return;
    }

    const scenarios = upReport.event.scenarios || {
        scenario_alpha: {
            probability: '65%',
            name: 'Industrial Continuity & Expressway Expansion',
            summary: 'Ruling party retains majority. Uninterrupted capex continuity for Ganga Expressway phase 2, Jewar Airport Aerotropolis, and UP Defence Industrial Corridor.',
            top_gainers: ['Larsen & Toubro', 'UltraTech Cement', 'PNC Infratech', 'Bharat Electronics'],
            capital_flow: 'Aggressive domestic capital allocation toward industrial EPC, cement dispatch, and urban land development.'
        },
        scenario_beta: {
            probability: '35%',
            name: 'Populist Agrarian Rebalancing & Direct Subsidies',
            summary: 'Opposition coalition victory triggers review of private infrastructure concessions, shifting state budget into farmer loan waivers, free electricity, and rural direct transfers.',
            top_gainers: ['Dabur India', 'Mahindra & Mahindra Tractors', 'ITC Ltd', 'UPL Ltd'],
            capital_flow: 'Capital rotates rapidly from high-capex infrastructure concessionaires into rural consumer staples, tractors, and fertilizers.'
        }
    };

    container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Scenario Alpha -->
            <div class="p-4 rounded border ${window.terminalState.predictiveScenario === 'alpha' ? 'border-[#00D2FF] bg-[#1E293B]' : 'border-slate-800 bg-[#151B26]'} cursor-pointer transition-all" onclick="setScenario('alpha')">
                <div class="flex items-center justify-between mb-2">
                    <span class="px-2 py-0.5 rounded bg-emerald-950 border border-emerald-700 text-emerald-300 font-mono text-[10px] font-bold">SCENARIO ALPHA // PROBABILITY ${scenarios.scenario_alpha.probability}</span>
                    <span class="text-xs font-mono text-cyan-400 font-bold">${window.terminalState.predictiveScenario === 'alpha' ? '● SELECTED' : ''}</span>
                </div>
                <h3 class="font-bold text-sm text-white font-mono mb-1">${escapeHtml(scenarios.scenario_alpha.name)}</h3>
                <p class="text-xs text-[#94A3B8] mb-3 leading-relaxed">${escapeHtml(scenarios.scenario_alpha.summary)}</p>
                <div class="text-[10px] font-mono text-[#00D2FF] mb-2 font-bold">BENEFICIARY CORPORATE PROXIES:</div>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    ${(scenarios.scenario_alpha.top_gainers || []).map(g => `<span class="px-2 py-0.5 bg-black border border-slate-700 rounded text-[10px] font-mono text-white">${escapeHtml(g)}</span>`).join('')}
                </div>
                <div class="text-[10px] font-mono text-amber-300 pt-2 border-t border-slate-700/60">
                    <span class="font-bold">CAPITAL FLOW:</span> ${escapeHtml(scenarios.scenario_alpha.capital_flow)}
                </div>
            </div>

            <!-- Scenario Beta -->
            <div class="p-4 rounded border ${window.terminalState.predictiveScenario === 'beta' ? 'border-[#00D2FF] bg-[#1E293B]' : 'border-slate-800 bg-[#151B26]'} cursor-pointer transition-all" onclick="setScenario('beta')">
                <div class="flex items-center justify-between mb-2">
                    <span class="px-2 py-0.5 rounded bg-purple-950 border border-purple-700 text-purple-300 font-mono text-[10px] font-bold">SCENARIO BETA // PROBABILITY ${scenarios.scenario_beta.probability}</span>
                    <span class="text-xs font-mono text-cyan-400 font-bold">${window.terminalState.predictiveScenario === 'beta' ? '● SELECTED' : ''}</span>
                </div>
                <h3 class="font-bold text-sm text-white font-mono mb-1">${escapeHtml(scenarios.scenario_beta.name)}</h3>
                <p class="text-xs text-[#94A3B8] mb-3 leading-relaxed">${escapeHtml(scenarios.scenario_beta.summary)}</p>
                <div class="text-[10px] font-mono text-[#00D2FF] mb-2 font-bold">BENEFICIARY CORPORATE PROXIES:</div>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    ${(scenarios.scenario_beta.top_gainers || []).map(g => `<span class="px-2 py-0.5 bg-black border border-slate-700 rounded text-[10px] font-mono text-white">${escapeHtml(g)}</span>`).join('')}
                </div>
                <div class="text-[10px] font-mono text-amber-300 pt-2 border-t border-slate-700/60">
                    <span class="font-bold">CAPITAL FLOW:</span> ${escapeHtml(scenarios.scenario_beta.capital_flow)}
                </div>
            </div>
        </div>
    `;
}

function setScenario(sc) {
    window.terminalState.predictiveScenario = sc;
    renderPredictiveTab();
}

// Render Tab 4: Published Intelligence Dossiers
function renderDossiersList() {
    const container = document.getElementById('dossiers-list');
    if (!container) return;

    container.innerHTML = window.terminalState.reports.map((r, idx) => `
        <div class="p-4 mb-3 rounded border border-slate-800 bg-[#151B26] hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between text-[10px] font-mono mb-1.5">
                <span class="text-[#00D2FF] font-bold">VECTIS DOSSIER #${r.id} // ${escapeHtml(r.event.category.toUpperCase())}</span>
                <span class="text-[#94A3B8]">PUBLISHED: ${escapeHtml(r.published_at || 'RECENT')}</span>
            </div>
            <h3 class="text-sm font-bold font-mono text-white mb-2">${escapeHtml(r.headline)}</h3>
            <p class="text-xs text-[#94A3B8] mb-3 leading-relaxed">${escapeHtml(r.market_summary || r.event.summary)}</p>
            <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs font-mono">
                <span class="text-amber-400">FLOW BIAS: ${escapeHtml(r.market_stance.toUpperCase())}</span>
                <a href="/report.php?id=${r.id}" target="_blank" class="px-3 py-1 rounded bg-[#1E293B] hover:bg-[#00D2FF] hover:text-black text-[#00D2FF] transition-all font-bold">
                    READ FULL DOSSIER &rarr;
                </a>
            </div>
        </div>
    `).join('');
}

// Setup Customs Duty Landed Cost Simulator (Tab 5)
function setupCalculator() {
    const calcBtn = document.getElementById('btn-run-simulation');
    if (!calcBtn) return;

    calcBtn.addEventListener('click', async () => {
        const fob = parseFloat(document.getElementById('calc-fob').value) || 0;
        const freight = parseFloat(document.getElementById('calc-freight').value) || 0;
        const insurance = parseFloat(document.getElementById('calc-insurance').value) || 0;
        const bcd = parseFloat(document.getElementById('calc-bcd').value) || 0;
        const sws = parseFloat(document.getElementById('calc-sws').value) || 10;
        const igst = parseFloat(document.getElementById('calc-igst').value) || 18;
        const aidc = parseFloat(document.getElementById('calc-aidc').value) || 0;
        const oldBcd = parseFloat(document.getElementById('calc-old-bcd').value) || null;
        const rate = parseFloat(document.getElementById('calc-rate').value) || 84.5;

        try {
            calcBtn.disabled = true;
            calcBtn.innerHTML = 'CALCULATING VALUATION...';

            const res = await fetch(apiUrl('/ajax/calculator.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    fob_value: fob,
                    freight: freight,
                    insurance: insurance,
                    bcd_rate: bcd,
                    sws_rate: sws,
                    igst_rate: igst,
                    aidc_rate: aidc,
                    old_bcd_rate: oldBcd,
                    currency_rate: rate,
                })
            });

            const data = await res.json();
            if (data.status === 'ok') {
                renderCalculatorResults(data);
            } else {
                alert('Calculation error: ' + (data?.error?.message || 'Failed'));
            }
        } catch (e) {
            alert('Failed to connect to calculator service: ' + e.message);
        } finally {
            calcBtn.disabled = false;
            calcBtn.innerHTML = 'EXECUTE VALUATION & DELTA ANALYSIS';
        }
    });

    // Auto-search HS codes
    const hsInput = document.getElementById('calc-hs-search');
    if (hsInput) {
        hsInput.addEventListener('input', debounce(async (e) => {
            const query = e.target.value.trim();
            if (query.length < 2) return;
            try {
                const res = await fetch(apiUrl(`/ajax/hs_codes.php?q=${encodeURIComponent(query)}`));
                const data = await res.json();
                if (data.status === 'ok' && data.data.length > 0) {
                    const first = data.data[0];
                    document.getElementById('calc-bcd').value = first.bcd_rate;
                    document.getElementById('calc-sws').value = first.sws_rate;
                    document.getElementById('calc-igst').value = first.igst_rate;
                    document.getElementById('calc-aidc').value = first.aidc_rate;
                    document.getElementById('calc-hs-desc').innerText = `[AUTO-SELECTED] ${first.code} - ${first.description}`;
                }
            } catch (err) {
                console.error('HS Code search error', err);
            }
        }, 300));
    }
}

function renderCalculatorResults(res) {
    const out = document.getElementById('calculator-results');
    if (!out) return;

    const delta = res.delta_analysis;
    const deltaHtml = delta ? `
        <div class="p-3 mb-3 rounded border ${delta.duty_delta_inr > 0 ? 'border-rose-800 bg-rose-950/30 text-rose-300' : 'border-emerald-800 bg-emerald-950/30 text-emerald-300'} font-mono text-xs">
            <div class="font-bold mb-1">TARIFF VARIANCE & MARGIN IMPACT // ${escapeHtml(delta.impact_type)}</div>
            <div>Old Landed Cost: ${formatInr(delta.old_landed_cost_inr)} &rarr; New Landed Cost: ${formatInr(res.landed_cost_inr)}</div>
            <div class="font-bold text-sm mt-1">Gross Duty Delta: ${delta.duty_delta_inr >= 0 ? '+' : ''}${formatInr(delta.duty_delta_inr)} (${delta.duty_delta_usd >= 0 ? '+' : ''}${formatCurrency(delta.duty_delta_usd)}) &bull; Margin Shift: ${delta.margin_impact_pct}%</div>
        </div>
    ` : '';

    out.innerHTML = `
        ${deltaHtml}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 font-mono text-xs">
            <div class="p-2.5 bg-[#0B0F17] border border-slate-800 rounded">
                <span class="text-[#94A3B8] text-[10px]">ASSESSABLE VALUE (CIF):</span>
                <div class="font-bold text-white text-sm">${formatInr(res.cif_inr)}</div>
                <div class="text-[10px] text-[#94A3B8]">${formatCurrency(res.cif_foreign)}</div>
            </div>
            <div class="p-2.5 bg-[#0B0F17] border border-slate-800 rounded">
                <span class="text-[#94A3B8] text-[10px]">TOTAL CUSTOMS DUTY:</span>
                <div class="font-bold text-amber-400 text-sm">${formatInr(res.total_duty_inr)}</div>
                <div class="text-[10px] text-amber-200/80">${formatCurrency(res.total_duty_usd)}</div>
            </div>
            <div class="p-2.5 bg-[#0B0F17] border border-slate-800 rounded">
                <span class="text-[#94A3B8] text-[10px]">FINAL LANDED COST:</span>
                <div class="font-bold text-emerald-400 text-sm">${formatInr(res.landed_cost_inr)}</div>
                <div class="text-[10px] text-emerald-200/80">${formatCurrency(res.landed_cost_usd)}</div>
            </div>
            <div class="p-2.5 bg-[#0B0F17] border border-slate-800 rounded">
                <span class="text-[#94A3B8] text-[10px]">EFFECTIVE DUTY RATE:</span>
                <div class="font-bold text-cyan-400 text-sm">${res.effective_duty_pct}%</div>
                <div class="text-[10px] text-[#94A3B8]">ON CIF VALUE</div>
            </div>
        </div>

        <table class="w-full text-left font-mono text-xs border border-slate-800">
            <thead class="bg-[#1E293B] text-[#94A3B8]">
                <tr>
                    <th class="p-2">COMPONENT</th>
                    <th class="p-2">RATE</th>
                    <th class="p-2">BASE AMOUNT</th>
                    <th class="p-2">DUTY AMOUNT (INR)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-white">
                <tr>
                    <td class="p-2 font-bold">Basic Customs Duty (BCD)</td>
                    <td class="p-2">${res.breakdown.bcd.rate}%</td>
                    <td class="p-2">${formatInr(res.cif_inr)}</td>
                    <td class="p-2 text-cyan-400 font-bold">${formatInr(res.breakdown.bcd.amount_inr)}</td>
                </tr>
                <tr>
                    <td class="p-2 font-bold">Social Welfare Surcharge (SWS)</td>
                    <td class="p-2">${res.breakdown.sws.rate}%</td>
                    <td class="p-2">${formatInr(res.breakdown.bcd.amount_inr)} (on BCD)</td>
                    <td class="p-2 text-cyan-400 font-bold">${formatInr(res.breakdown.sws.amount_inr)}</td>
                </tr>
                <tr>
                    <td class="p-2 font-bold">Integrated GST (IGST)</td>
                    <td class="p-2">${res.breakdown.igst.rate}%</td>
                    <td class="p-2">${formatInr(res.breakdown.igst.base_inr)} (CIF+BCD+SWS)</td>
                    <td class="p-2 text-cyan-400 font-bold">${formatInr(res.breakdown.igst.amount_inr)}</td>
                </tr>
            </tbody>
        </table>
    `;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Global bootstrap
document.addEventListener('DOMContentLoaded', () => {
    initTerminal();
});
