/* GeoTrade Terminal — admin review queue + report review panel. Vanilla JS. */

const CATEGORY_LABELS = {
    war_conflict: 'War / Conflict', sanctions_peace_deal: 'Sanctions / Peace Deal', election: 'Election',
    leadership_change: 'Leadership Change', regulation_law: 'Regulation / Law', civil_unrest: 'Civil Unrest',
    market_shock: 'Market Shock', resource_discovery: 'Resource Discovery', trade_policy: 'Trade Policy',
};
const STANCE_LABELS = { risk_on: 'Risk-On', risk_off: 'Risk-Off', sector_rotation: 'Sector Rotation', neutral_uncertain: 'Neutral / Uncertain' };
const DIRECTION_LABELS = { gainer: 'Gainer', loser: 'Loser', mixed: 'Mixed', watch: 'Watch' };
const STATUS_LABELS = {
    ai_drafted: 'AI Drafted', in_review: 'In Review', changes_requested: 'Changes Requested',
    approved: 'Approved', published: 'Published', rejected: 'Rejected',
};

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function fmtDate(value) {
    if (!value) return '—';
    const d = new Date(value.replace(' ', 'T'));
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function fetchJson(url, opts) {
    const res = await fetch(url, opts);
    const body = await res.json();
    if (!res.ok) throw new Error(body?.error?.message || `Request failed (${res.status})`);
    return body;
}

/* ---------- admin/index.php: tabbed queue / all reports / all events ---------- */
async function initAdminIndex() {
    const content = document.getElementById('gt-admin-content');
    if (!content) return;

    const tabs = document.querySelectorAll('.gt-tab');
    let activeTab = 'queue';

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');
            activeTab = tab.dataset.tab;
            render();
        });
    });

    async function render() {
        content.innerHTML = '<div class="gt-loading">Loading…</div>';
        try {
            if (activeTab === 'queue') {
                await renderReportsTable(content, "status=ai_drafted,in_review,changes_requested", true);
            } else if (activeTab === 'all-reports') {
                await renderReportsTable(content, null, false);
            } else {
                await renderEventsTable(content);
            }
        } catch (e) {
            content.innerHTML = `<div class="gt-alert gt-alert--error">${escapeHtml(e.message)}</div>`;
        }
    }

    async function renderReportsTable(container, statusFilter, queueMode) {
        // The queue tab needs three statuses at once; the admin endpoint
        // takes a single ?status=, so for queue mode fetch all and filter
        // client-side rather than firing three requests.
        const { reports } = await fetchJson('/ajax/admin/reports.php');
        const queueStatuses = ['ai_drafted', 'in_review', 'changes_requested'];
        const rows = queueMode ? reports.filter((r) => queueStatuses.includes(r.status)) : reports;

        if (rows.length === 0) {
            container.innerHTML = '<div class="gt-empty">Nothing here.</div>';
            return;
        }

        container.innerHTML = `
            <table class="gt-table">
                <thead><tr>
                    <th class="gt-th">Headline</th><th class="gt-th">Event</th><th class="gt-th">Status</th>
                    <th class="gt-th">Stance</th><th class="gt-th">Drafted</th><th class="gt-th">Reviewed</th>
                </tr></thead>
                <tbody>
                ${rows.map((r) => `
                    <tr style="cursor:pointer" onclick="window.location.href='/admin/report.php?id=${r.id}'">
                        <td class="gt-td">${escapeHtml(r.headline)}</td>
                        <td class="gt-td">${escapeHtml(r.event_title)}</td>
                        <td class="gt-td"><span class="gt-badge gt-badge--status-${r.status}">${escapeHtml(STATUS_LABELS[r.status] || r.status)}</span></td>
                        <td class="gt-td"><span class="gt-badge gt-badge--${r.market_stance}">${escapeHtml(STANCE_LABELS[r.market_stance] || r.market_stance)}</span></td>
                        <td class="gt-td tabular">${fmtDate(r.ai_drafted_at)}</td>
                        <td class="gt-td tabular">${fmtDate(r.reviewed_at)}</td>
                    </tr>
                `).join('')}
                </tbody>
            </table>
        `;
    }

    async function renderEventsTable(container) {
        const { events } = await fetchJson('/ajax/admin/events.php');

        if (events.length === 0) {
            container.innerHTML = '<div class="gt-empty">No events tracked yet.</div>';
            return;
        }

        container.innerHTML = `
            <table class="gt-table">
                <thead><tr>
                    <th class="gt-th">Title</th><th class="gt-th">Category</th><th class="gt-th">Timing</th>
                    <th class="gt-th">Countries</th><th class="gt-th">Reports</th><th class="gt-th">Added</th>
                </tr></thead>
                <tbody>
                ${events.map((e) => `
                    <tr>
                        <td class="gt-td">${escapeHtml(e.title)}</td>
                        <td class="gt-td"><span class="gt-badge">${escapeHtml(CATEGORY_LABELS[e.category] || e.category)}</span></td>
                        <td class="gt-td"><span class="gt-badge gt-badge--timing-${e.timing}">${e.timing}</span></td>
                        <td class="gt-td">${escapeHtml((e.countries || []).join(', '))}</td>
                        <td class="gt-td tabular">${e.report_count}</td>
                        <td class="gt-td tabular">${fmtDate(e.created_at)}</td>
                    </tr>
                `).join('')}
                </tbody>
            </table>
        `;
    }

    render();
}

/* ---------- admin/report.php?id=: detail + review decision panel ---------- */
async function initAdminReportDetail() {
    const container = document.getElementById('gt-admin-report-detail');
    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) {
        container.innerHTML = '<div class="gt-alert gt-alert--error">No report id given.</div>';
        return;
    }

    async function load() {
        try {
            const { report: r } = await fetchJson(`/ajax/admin/report.php?id=${encodeURIComponent(id)}`);
            renderDetail(r);
        } catch (e) {
            container.innerHTML = `<div class="gt-alert gt-alert--error">${escapeHtml(e.message)}</div>`;
        }
    }

    function renderDetail(r) {
        document.title = `${r.headline} — Review — GeoTrade Terminal Admin`;

        const industryHtml = r.industry_impacts.map((i) => `
            <div class="gt-industry-row">
                <div class="gt-industry-row__name">${escapeHtml(i.industry)}</div>
                <span class="gt-badge gt-badge--${i.direction}">${escapeHtml(DIRECTION_LABELS[i.direction] || i.direction)}</span>
                <div class="gt-industry-row__rationale">${escapeHtml(i.rationale)}</div>
            </div>
        `).join('') || '<div class="gt-empty">No industry-level detail yet.</div>';

        const companyHtml = r.company_impacts.map((c) => `
            <div class="gt-company-card expanded">
                <div class="gt-company-card__head">
                    <span class="gt-badge gt-badge--${c.direction}">${escapeHtml(DIRECTION_LABELS[c.direction] || c.direction)}</span>
                    <span class="gt-company-card__name">${escapeHtml(c.company_name)}</span>
                    ${c.ticker ? `<span class="gt-company-card__ticker">${escapeHtml(c.ticker)}</span>` : ''}
                    <span style="font-size:12px;color:var(--gt-text-dim);">${escapeHtml(c.country)}</span>
                </div>
                <p class="gt-company-card__rationale">${escapeHtml(c.rationale)}</p>
            </div>
        `).join('') || '<div class="gt-empty">No company-level detail yet.</div>';

        const reviewHistory = r.reviewed_at ? `
            <div class="gt-alert ${r.status === 'rejected' ? 'gt-alert--error' : 'gt-alert--success'}">
                Last reviewed by <strong>${escapeHtml(r.reviewed_by_name || 'unknown')}</strong> on ${fmtDate(r.reviewed_at)} — ${escapeHtml(r.review_notes || '')}
            </div>
        ` : '';

        container.innerHTML = `
            <a class="gt-back-link" href="/admin/index.php">&larr; Review queue</a>
            <div class="gt-page__header">
                <span class="gt-badge gt-badge--status-${r.status}" style="margin-bottom:10px;display:inline-flex;">${escapeHtml(STATUS_LABELS[r.status] || r.status)}</span>
                <h1 class="gt-page__title">${escapeHtml(r.headline)}</h1>
                <p class="gt-page__subtitle">${escapeHtml(r.event.title)} — ${escapeHtml((r.event.countries || []).join(', '))}</p>
            </div>

            ${reviewHistory}

            <div class="gt-market-banner gt-market-banner--${r.market_stance}">
                <div class="gt-market-banner__label">Market Direction — ${escapeHtml(STANCE_LABELS[r.market_stance] || r.market_stance)}</div>
                <p class="gt-market-banner__summary">${escapeHtml(r.market_summary)}</p>
                <p class="gt-market-banner__flow">${escapeHtml(r.market_flow_note)}</p>
            </div>

            <div class="gt-card" style="margin-bottom:20px;">
                <h2 style="margin-top:0;font-size:14px;">Event background</h2>
                <p style="color:var(--gt-text-dim);">${escapeHtml(r.event.summary)}</p>
            </div>

            <div class="gt-card" style="margin-bottom:20px;">
                <h2 style="margin-top:0;font-size:14px;">Industry impact</h2>
                ${industryHtml}
            </div>

            <div class="gt-card" style="margin-bottom:20px;">
                <h2 style="margin-top:0;font-size:14px;">Company impact</h2>
                ${companyHtml}
            </div>

            <div class="gt-card">
                <h2 style="margin-top:0;font-size:14px;">Review decision</h2>
                <p style="color:var(--gt-text-dim);font-size:13px;">Required before this can go live. Notes are mandatory on every decision, including approvals.</p>
                <form class="gt-form" id="gt-review-form">
                    <label class="gt-label">Notes
                        <textarea class="gt-textarea" name="notes" required placeholder="What did you check? Any corrections made?"></textarea>
                    </label>
                    <div id="gt-review-error"></div>
                    <div style="display:flex;gap:10px;">
                        <button type="button" class="gt-btn gt-btn--success" data-decision="approve">Approve &amp; Publish</button>
                        <button type="button" class="gt-btn gt-btn--warning" data-decision="request_changes">Request Changes</button>
                        <button type="button" class="gt-btn gt-btn--danger" data-decision="reject">Reject</button>
                    </div>
                </form>
            </div>
        `;

        const form = document.getElementById('gt-review-form');
        form.querySelectorAll('[data-decision]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const notes = form.notes.value.trim();
                const errorEl = document.getElementById('gt-review-error');
                errorEl.innerHTML = '';

                if (!notes) {
                    errorEl.innerHTML = '<div class="gt-alert gt-alert--error">Notes are required.</div>';
                    return;
                }

                form.querySelectorAll('button').forEach((b) => (b.disabled = true));

                try {
                    await fetchJson('/ajax/admin/review.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ report_id: Number(id), decision: btn.dataset.decision, notes, csrf_token: csrfToken() }),
                    });
                    window.location.href = '/admin/index.php';
                } catch (e) {
                    errorEl.innerHTML = `<div class="gt-alert gt-alert--error">${escapeHtml(e.message)}</div>`;
                    form.querySelectorAll('button').forEach((b) => (b.disabled = false));
                }
            });
        });
    }

    load();
}

document.addEventListener('DOMContentLoaded', () => {
    initAdminIndex();
    initAdminReportDetail();
});
