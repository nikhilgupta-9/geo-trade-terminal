/* GeoTrade Terminal — public report feed + detail page rendering.
   Vanilla JS, no build step, no framework — fetch() against the ajax/
   JSON endpoints and render into the DOM directly. */

const CATEGORY_LABELS = {
    war_conflict: 'War / Conflict',
    sanctions_peace_deal: 'Sanctions / Peace Deal',
    election: 'Election',
    leadership_change: 'Leadership Change',
    regulation_law: 'Regulation / Law',
    civil_unrest: 'Civil Unrest',
    market_shock: 'Market Shock',
    resource_discovery: 'Resource Discovery',
    trade_policy: 'Trade Policy',
};

const STANCE_LABELS = {
    risk_on: 'Risk-On',
    risk_off: 'Risk-Off',
    sector_rotation: 'Sector Rotation',
    neutral_uncertain: 'Neutral / Uncertain',
};

const DIRECTION_LABELS = { gainer: 'Gainer', loser: 'Loser', mixed: 'Mixed', watch: 'Watch' };

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function fmtDate(value) {
    if (!value) return '';
    const d = new Date(value.replace(' ', 'T'));
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

async function fetchJson(url, opts) {
    const res = await fetch(url, opts);
    const body = await res.json();
    if (!res.ok) throw new Error(body?.error?.message || `Request failed (${res.status})`);
    return body;
}

/* ---------- Feed page ---------- */
async function loadFeed() {
    const container = document.getElementById('gt-feed');
    if (!container) return;

    try {
        const { reports } = await fetchJson('/ajax/reports.php');

        if (reports.length === 0) {
            container.innerHTML = '<div class="gt-empty">No published reports yet.</div>';
            return;
        }

        container.innerHTML = reports.map((r) => `
            <a class="gt-report-card" href="/report.php?id=${r.id}">
                <div class="gt-report-card__meta">
                    <span class="gt-badge">${escapeHtml(CATEGORY_LABELS[r.event.category] || r.event.category)}</span>
                    <span class="gt-badge gt-badge--timing-${r.event.timing}">${r.event.timing}</span>
                    <span class="gt-badge gt-badge--${r.market_stance}">${escapeHtml(STANCE_LABELS[r.market_stance] || r.market_stance)}</span>
                    <span class="gt-report-card__date tabular">${fmtDate(r.published_at)}</span>
                </div>
                <div class="gt-report-card__headline">${escapeHtml(r.headline)}</div>
                <div class="gt-report-card__event">${escapeHtml(r.event.title)} — ${escapeHtml((r.event.countries || []).join(', '))}</div>
            </a>
        `).join('');
    } catch (e) {
        container.innerHTML = `<div class="gt-alert gt-alert--error">Could not load reports: ${escapeHtml(e.message)}</div>`;
    }
}

/* ---------- Detail page ---------- */
function severityDots(severity, directionClass) {
    let dots = '';
    for (let i = 1; i <= 5; i++) {
        dots += `<span class="gt-severity-dot ${i <= severity ? 'filled' : ''}" style="color:var(--gt-${directionClass === 'gainer' ? 'success' : directionClass === 'loser' ? 'critical' : directionClass === 'mixed' ? 'mixed' : 'warning'})"></span>`;
    }
    return dots;
}

async function loadReportDetail() {
    const container = document.getElementById('gt-report-detail');
    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) {
        container.innerHTML = '<div class="gt-alert gt-alert--error">No report id given.</div>';
        return;
    }

    try {
        const { report: r } = await fetchJson(`/ajax/report.php?id=${encodeURIComponent(id)}`);

        document.title = `${r.headline} — GeoTrade Terminal`;

        const industryHtml = r.industry_impacts.map((i) => `
            <div class="gt-industry-row">
                <div class="gt-industry-row__name">${escapeHtml(i.industry)}</div>
                <span class="gt-badge gt-badge--${i.direction}">${escapeHtml(DIRECTION_LABELS[i.direction] || i.direction)}</span>
                <div class="gt-industry-row__severity">${severityDots(i.severity, i.direction)}</div>
                <div class="gt-industry-row__rationale">${escapeHtml(i.rationale)}</div>
            </div>
        `).join('') || '<div class="gt-empty">No industry-level detail yet.</div>';

        const companyHtml = r.company_impacts.map((c, idx) => {
            const metrics = c.financial_metrics || {};
            let metricsText = Object.entries(metrics).map(([k, v]) => `${k.replace(/_/g, ' ').toUpperCase()}: ${v}`).join(' • ');

            return `
            <div class="gt-company-card expanded" id="company-${idx}">
                <div class="gt-company-card__head" onclick="document.getElementById('company-${idx}').classList.toggle('expanded')">
                    <span class="gt-badge gt-badge--${c.direction}">${escapeHtml(DIRECTION_LABELS[c.direction] || c.direction)}</span>
                    <span class="gt-company-card__name">${escapeHtml(c.company_name)}</span>
                    ${c.ticker ? `<span class="gt-company-card__ticker">${escapeHtml(c.ticker)}</span>` : ''}
                    <span class="gt-text-dim" style="font-size:12px;">${escapeHtml(c.country)} ${c.market_cap ? '• Cap: ' + escapeHtml(c.market_cap) : ''}</span>
                </div>
                ${c.overview ? `<p style="font-size:12px;color:var(--gt-text-dim);margin:6px 0 10px;">${escapeHtml(c.overview)}</p>` : ''}
                <p class="gt-company-card__rationale">${escapeHtml(c.rationale)}</p>
                ${metricsText ? `<div style="font-family:var(--gt-font-mono);font-size:11px;color:var(--gt-accent);margin-bottom:8px;">${escapeHtml(metricsText)}</div>` : ''}
                <div class="gt-company-card__detail">
                    ${c.recent_activity.length ? `<strong style="font-size:12px;color:var(--gt-text-dim);">Historical Multi-Year Activity Track Record</strong>` + c.recent_activity.map((a) => `
                        <div class="gt-activity-item"><span class="gt-activity-item__period">${escapeHtml(a.period)}</span>${escapeHtml(a.note)}</div>
                    `).join('') : '<div class="gt-activity-item">No historical activity notes on file.</div>'}
                </div>
            </div>
            `;
        }).join('') || '<div class="gt-empty">No company-level detail yet.</div>';

        const resourcesHtml = (r.event.resources_affected || []).length > 0
            ? `<div style="margin-top:10px;"><strong style="font-size:11px;color:var(--gt-text-dim);">Affected Commodities / Resources:</strong> ` +
              r.event.resources_affected.map(res => `<span class="gt-badge" style="background:#2d2105;border:1px solid #78350f;color:#fbbf24;margin-right:6px;">⚡ ${escapeHtml(res)}</span>`).join('') +
              `</div>`
            : '';

        const scenariosHtml = r.event.scenarios && r.event.scenarios.scenario_alpha ? `
            <div class="gt-card" style="margin-bottom:20px;border:1px solid #00D2FF;">
                <h2 style="margin-top:0;font-size:14px;color:#00D2FF;">Predictive Scenario Radar</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div style="background:#0F141C;padding:12px;border:1px solid #1E2633;border-radius:4px;">
                        <span class="gt-badge gt-badge--gainer">Scenario Alpha (${escapeHtml(r.event.scenarios.scenario_alpha.probability)})</span>
                        <div style="font-weight:700;margin:6px 0;">${escapeHtml(r.event.scenarios.scenario_alpha.name)}</div>
                        <p style="font-size:12px;color:#94a3b8;">${escapeHtml(r.event.scenarios.scenario_alpha.summary)}</p>
                        <div style="font-size:11px;color:#fbbf24;margin-top:6px;"><strong>Capital Flow:</strong> ${escapeHtml(r.event.scenarios.scenario_alpha.capital_flow)}</div>
                    </div>
                    <div style="background:#0F141C;padding:12px;border:1px solid #1E2633;border-radius:4px;">
                        <span class="gt-badge gt-badge--mixed">Scenario Beta (${escapeHtml(r.event.scenarios.scenario_beta.probability)})</span>
                        <div style="font-weight:700;margin:6px 0;">${escapeHtml(r.event.scenarios.scenario_beta.name)}</div>
                        <p style="font-size:12px;color:#94a3b8;">${escapeHtml(r.event.scenarios.scenario_beta.summary)}</p>
                        <div style="font-size:11px;color:#fbbf24;margin-top:6px;"><strong>Capital Flow:</strong> ${escapeHtml(r.event.scenarios.scenario_beta.capital_flow)}</div>
                    </div>
                </div>
            </div>
        ` : '';

        container.innerHTML = `
            <a class="gt-back-link" href="/">&larr; Back to Terminal</a>
            <div class="gt-page__header">
                <div class="gt-report-card__meta" style="margin-bottom:12px;">
                    <span class="gt-badge">${escapeHtml(CATEGORY_LABELS[r.event.category] || r.event.category)}</span>
                    <span class="gt-badge gt-badge--timing-${r.event.timing}">${r.event.timing}</span>
                    <span class="gt-report-card__date tabular">${fmtDate(r.published_at)}</span>
                </div>
                <h1 class="gt-page__title">${escapeHtml(r.headline)}</h1>
                <p class="gt-page__subtitle">${escapeHtml(r.event.title)} — ${escapeHtml((r.event.countries || []).join(', '))}</p>
                ${resourcesHtml}
            </div>

            <div class="gt-market-banner gt-market-banner--${r.market_stance}">
                <div class="gt-market-banner__label">Market Direction // ${escapeHtml(STANCE_LABELS[r.market_stance] || r.market_stance)}</div>
                <p class="gt-market-banner__summary">${escapeHtml(r.market_summary)}</p>
                <p class="gt-market-banner__flow"><strong>Capital Flow Vector:</strong> ${escapeHtml(r.market_flow_note)}</p>
            </div>

            ${scenariosHtml}

            <div class="gt-card" style="margin-bottom:20px;">
                <h2 style="margin-top:0;font-size:14px;">Event background</h2>
                <p style="color:var(--gt-text-dim);">${escapeHtml(r.event.summary)}</p>
            </div>

            <div class="gt-card" style="margin-bottom:20px;">
                <h2 style="margin-top:0;font-size:14px;">Industry transmission mechanisms</h2>
                ${industryHtml}
            </div>

            <div class="gt-card">
                <h2 style="margin-top:0;font-size:14px;">Corporate impact ledger</h2>
                ${companyHtml}
            </div>
        `;
    } catch (e) {
        container.innerHTML = `<div class="gt-alert gt-alert--error">Could not load report: ${escapeHtml(e.message)}</div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadFeed();
    loadReportDetail();
});
