<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Geo Trade Terminal | Institutional Regulatory & Tariff Engine')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0B0F17] text-[#F8FAFC] antialiased min-h-screen flex flex-col font-sans selection:bg-[#00D2FF]/30 selection:text-[#00D2FF]">

    <!-- Top Institutional Nav & System Status Bar -->
    <header class="border-b border-[#1E293B] bg-[#151B26]/90 backdrop-blur sticky top-0 z-40">
        <div class="px-4 py-2.5 flex flex-wrap items-center justify-between gap-3">
            <!-- Left: Terminal Branding & Indicators -->
            <div class="flex items-center gap-4">
                <a href="{{ route('terminal.index') }}" class="flex items-center gap-2 group">
                    <div class="w-7 h-7 rounded border border-[#00D2FF]/40 bg-[#00D2FF]/10 flex items-center justify-center font-mono-code font-bold text-[#00D2FF] text-sm shadow-[0_0_12px_rgba(0,210,255,0.2)]">
                        G
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5 leading-none">
                            <span class="font-bold tracking-wider text-base text-[#F8FAFC]">GEO TRADE</span>
                            <span class="text-[#00D2FF] font-semibold text-xs tracking-widest px-1 py-0.5 rounded bg-[#00D2FF]/10 border border-[#00D2FF]/30">TERMINAL</span>
                        </div>
                        <span class="text-[10px] text-[#94A3B8] font-mono-code tracking-tight">GEOPOLITICAL & TARIFF INTELLIGENCE</span>
                    </div>
                </a>

                <div class="hidden lg:flex items-center gap-3 pl-4 border-l border-[#1E293B] text-xs font-mono-code">
                    <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>CORE: ONLINE</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-[#00D2FF]/10 border border-[#00D2FF]/20 text-[#00D2FF]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#00D2FF]"></span>
                        <span>CBIC FEED: LIVE</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        <span>DGFT: SYNCED</span>
                    </div>
                </div>
            </div>

            <!-- Center: Dual Institutional Precision Clocks -->
            <div class="hidden md:flex items-center gap-4 text-xs font-mono-code bg-[#0B0F17] px-3 py-1 rounded border border-[#1E293B]">
                <div class="flex items-center gap-2">
                    <span class="text-[#94A3B8]">UTC:</span>
                    <span id="utc-clock" class="text-[#F8FAFC] font-semibold">00:00:00</span>
                </div>
                <div class="w-px h-3 bg-[#1E293B]"></div>
                <div class="flex items-center gap-2">
                    <span class="text-[#94A3B8]">IST (DELHI):</span>
                    <span id="ist-clock" class="text-[#00D2FF] font-semibold">00:00:00</span>
                </div>
            </div>

            <!-- Right: Alert Trigger Center & Role Switcher -->
            <div class="flex items-center gap-3">
                <!-- Alerts Button -->
                <button type="button" 
                        onclick="document.getElementById('alertsModal').showModal()"
                        class="relative px-2.5 py-1.5 rounded border border-[#1E293B] hover:border-[#00D2FF]/50 bg-[#0B0F17] text-xs font-mono-code text-[#F8FAFC] hover:text-[#00D2FF] transition flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>ALERTS</span>
                    <span id="global-unread-badge" class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-[#EF4444] text-white">
                        {{ $unreadAlertsCount ?? 0 }}
                    </span>
                </button>

                <!-- Persona Switcher -->
                <div class="flex items-center gap-1.5 text-xs font-mono-code bg-[#0B0F17] px-2.5 py-1 rounded border border-[#1E293B]">
                    <span class="text-[#94A3B8] hidden sm:inline">ROLE:</span>
                    <form method="POST" action="" id="role-switch-form" class="inline">
                        @csrf
                        <select onchange="this.form.action='{{ url('switch-user') }}/' + this.value; this.form.submit();"
                                class="bg-transparent text-[#00D2FF] font-semibold focus:outline-none cursor-pointer border-none py-0.5">
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ ($currentUser->id ?? 1) === $u->id ? 'selected' : '' }} class="bg-[#151B26] text-white">
                                    [{{ $u->role }}] {{ $u->name }} ({{ $u->company }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Live Regulatory Revisions Ticker Marquee -->
        <div class="bg-[#0B0F17] border-t border-[#1E293B] py-1 px-4 overflow-hidden relative flex items-center text-[11px] font-mono-code">
            <div class="bg-[#151B26] text-[#00D2FF] px-2 py-0.5 mr-3 rounded border border-[#00D2FF]/30 font-bold tracking-wider shrink-0 flex items-center gap-1 z-10 shadow">
                <span class="w-1.5 h-1.5 rounded-full bg-[#00D2FF] animate-ping"></span>
                <span>GAZETTE TICKER</span>
            </div>
            <div class="whitespace-nowrap overflow-hidden flex-1 relative">
                <div class="animate-ticker inline-flex gap-8">
                    @forelse($recentRevisions as $rev)
                        <div class="inline-flex items-center gap-2 cursor-pointer hover:text-[#00D2FF] transition"
                             onclick="openRevisionDetails({{ $rev->id }})">
                            <span class="text-[#94A3B8]">[{{ $rev->circular->source ?? 'CBIC' }} {{ $rev->circular->notification_number ?? '' }}]</span>
                            <span class="text-white font-semibold">HS {{ $rev->hsCode->code ?? '' }}</span>
                            <span class="text-slate-300">BCD: {{ $rev->old_rate }}% &rarr; {{ $rev->new_rate }}%</span>
                            @if($rev->action === 'INCREASE')
                                <span class="px-1 py-0.2 rounded bg-rose-500/20 text-[#EF4444] border border-rose-500/30 font-bold">
                                    +{{ $rev->delta }}% HIKE
                                </span>
                            @elseif($rev->action === 'DECREASE')
                                <span class="px-1 py-0.2 rounded bg-emerald-500/20 text-[#10B981] border border-emerald-500/30 font-bold">
                                    {{ $rev->delta }}% RELIEF
                                </span>
                            @elseif($rev->action === 'EXEMPTED')
                                <span class="px-1 py-0.2 rounded bg-cyan-500/20 text-[#00D2FF] border border-cyan-500/30 font-bold">
                                    EXEMPTED
                                </span>
                            @endif
                            <span class="text-[#94A3B8]">Eff: {{ optional($rev->effective_from)->format('d M Y') }}</span>
                            <span class="text-slate-600">|</span>
                        </div>
                    @empty
                        <div class="text-[#94A3B8]">Awaiting circular streams...</div>
                    @endforelse
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col">
        @yield('content')
    </main>

    <!-- Institutional Footer -->
    <footer class="border-t border-[#1E293B] bg-[#151B26] text-[11px] font-mono-code text-[#94A3B8] px-4 py-2 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-3">
            <span class="text-slate-300 font-semibold">GEO TRADE TERMINAL</span>
            <span>&bull;</span>
            <span>Valuation Rules: Customs Valuation (Determination of Value of Imported Goods) Rules, 2007</span>
            <span>&bull;</span>
            <span>HS Revision Track: ITC (HS) 2022/2026</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-emerald-400">Deterministic Engine v2.4</span>
            <span>&bull;</span>
            <span>Redis Queue: Active</span>
            <span>&bull;</span>
            <span class="text-[#00D2FF]">&copy; {{ date('Y') }} Geo Trade Terminal</span>
        </div>
    </footer>

    <!-- Native Accessible Dialogs per modern-web-guidance -->
    <!-- 1. Alert Notification Modal -->
    <dialog id="alertsModal" closedby="any" aria-labelledby="alertsModalTitle" class="rounded-md border border-[#1E293B] bg-[#151B26] text-[#F8FAFC] shadow-2xl p-0 w-full max-w-2xl">
        <div class="border-b border-[#1E293B] p-4 flex items-center justify-between bg-[#0B0F17]/80">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <h3 id="alertsModalTitle" class="font-bold text-sm text-white tracking-wide">PORTFOLIO REGULATORY ALERTS</h3>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="markAllAlertsRead()" class="text-xs font-mono-code text-[#00D2FF] hover:underline px-2 py-1">
                    Mark All Read
                </button>
                <button onclick="document.getElementById('alertsModal').close()" class="text-[#94A3B8] hover:text-white p-1">
                    ✕
                </button>
            </div>
        </div>
        <div id="alerts-modal-content" class="p-4 max-h-[60vh] overflow-y-auto space-y-3 font-mono-code text-xs">
            <div class="text-[#94A3B8] text-center py-4">Loading active portfolio alerts...</div>
        </div>
    </dialog>

    <!-- 2. Revision Drilldown Modal -->
    <dialog id="revisionModal" closedby="any" aria-labelledby="revModalTitle" class="rounded-md border border-[#1E293B] bg-[#151B26] text-[#F8FAFC] shadow-2xl p-0 w-full max-w-3xl">
        <div class="border-b border-[#1E293B] p-4 flex items-center justify-between bg-[#0B0F17]/80">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#00D2FF]"></span>
                <h3 id="revModalTitle" class="font-bold text-sm text-white tracking-wide font-mono-code">TARIFF REVISION DRILLDOWN</h3>
            </div>
            <button onclick="document.getElementById('revisionModal').close()" class="text-[#94A3B8] hover:text-white p-1">
                ✕
            </button>
        </div>
        <div id="revision-modal-body" class="p-5 font-mono-code text-xs space-y-4">
            <div class="text-[#94A3B8] text-center py-8">Fetching gazette circular verification details...</div>
        </div>
    </dialog>

    <!-- Precision UTC & IST Clock Script + Dialog Light-Dismiss Fallback -->
    <script>
        function updateClocks() {
            const now = new Date();
            const utc = now.toUTCString().split(' ')[4];
            document.getElementById('utc-clock').textContent = utc + ' UTC';
            
            // IST is UTC + 5:30
            const istOptions = { timeZone: 'Asia/Kolkata', hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const istTime = new Intl.DateTimeFormat('en-GB', istOptions).format(now);
            document.getElementById('ist-clock').textContent = istTime + ' IST';
        }
        setInterval(updateClocks, 1000);
        updateClocks();

        // Modern Web Guidance: Fallback for dialog light-dismiss for browsers without native closedby support
        document.querySelectorAll('dialog').forEach(dialog => {
            if (!('closedBy' in HTMLDialogElement.prototype)) {
                dialog.addEventListener('click', (event) => {
                    if (event.target !== dialog) return;
                    const rect = dialog.getBoundingClientRect();
                    const isDialogContent = (
                        rect.top <= event.clientY &&
                        event.clientY <= rect.top + rect.height &&
                        rect.left <= event.clientX &&
                        event.clientX <= rect.left + rect.width
                    );
                    if (!isDialogContent) dialog.close();
                });
            }
        });

        // Load Alerts on Click
        document.getElementById('alertsModal').addEventListener('show', loadModalAlerts);
        async function loadModalAlerts() {
            try {
                const res = await fetch('/api/v1/alerts');
                const data = await res.json();
                const container = document.getElementById('alerts-modal-content');
                if (!data.data || data.data.length === 0) {
                    container.innerHTML = '<div class="text-[#94A3B8] text-center py-6">No unread regulatory alerts for your current watchlist.</div>';
                    return;
                }
                container.innerHTML = data.data.map(alert => {
                    const isHike = alert.delta > 0;
                    const badgeClass = isHike ? 'text-rose-400 bg-rose-500/10 border-rose-500/30' : 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30';
                    const sign = isHike ? '+' : '';
                    return `
                        <div class="p-3 rounded border border-[#1E293B] bg-[#0B0F17] hover:border-[#00D2FF]/40 transition flex items-start justify-between gap-3 ${alert.status === 'UNREAD' ? 'border-l-2 border-l-[#00D2FF]' : 'opacity-70'}">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-white">HS ${alert.hs_code ? alert.hs_code.code : 'CODE'}</span>
                                    <span class="px-1.5 py-0.5 rounded border text-[10px] font-bold ${badgeClass}">
                                        ${sign}${alert.delta}% ${isHike ? 'HIKE' : 'CUT'}
                                    </span>
                                    <span class="text-[10px] text-[#94A3B8]">${alert.revision && alert.revision.circular ? alert.revision.circular.notification_number : ''}</span>
                                </div>
                                <div class="text-[11px] text-slate-300">
                                    Duty Shift: ${alert.old_rate}% &rarr; ${alert.new_rate}% | Est. Monthly Exposure: 
                                    <span class="${isHike ? 'text-rose-400' : 'text-emerald-400'} font-bold">
                                        ₹${Number(alert.estimated_monthly_impact_inr).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Triggered: ${new Date(alert.created_at).toLocaleString()}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                ${alert.status === 'UNREAD' ? `
                                    <button onclick="markAlertRead(${alert.id}, this)" class="px-2 py-1 rounded bg-[#151B26] hover:bg-[#1E293B] text-[#00D2FF] text-[10px] border border-[#1E293B]">
                                        Mark Read
                                    </button>
                                ` : '<span class="text-[10px] text-slate-600">READ</span>'}
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function markAlertRead(alertId, btn) {
            try {
                await fetch(`/api/v1/alerts/${alertId}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (btn) btn.outerHTML = '<span class="text-[10px] text-slate-600">READ</span>';
                // Decrement global badge
                const badge = document.getElementById('global-unread-badge');
                if (badge) {
                    const current = parseInt(badge.textContent) || 0;
                    badge.textContent = Math.max(0, current - 1);
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function markAllAlertsRead() {
            try {
                await fetch('/api/v1/alerts/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const badge = document.getElementById('global-unread-badge');
                if (badge) badge.textContent = '0';
                loadModalAlerts();
            } catch (e) {
                console.error(e);
            }
        }

        async function openRevisionDetails(revisionId) {
            const modal = document.getElementById('revisionModal');
            modal.showModal();
            const body = document.getElementById('revision-modal-body');
            body.innerHTML = '<div class="text-center py-8 text-[#94A3B8]">Loading gazette details...</div>';

            try {
                const res = await fetch(`/api/v1/revisions/${revisionId}`);
                const json = await res.json();
                const r = json.data;

                const isHike = r.delta > 0;
                const deltaBadge = isHike
                    ? '<span class="text-rose-400 bg-rose-500/10 border border-rose-500/30 px-2 py-0.5 rounded font-bold">+' + r.delta + '% TARIFF HIKE</span>'
                    : '<span class="text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded font-bold">' + r.delta + '% TARIFF RELIEF</span>';

                body.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-[#0B0F17] p-4 rounded border border-[#1E293B]">
                        <div>
                            <div class="text-[#94A3B8] text-[10px]">HS CODE & COMMODITY</div>
                            <div class="text-base font-bold text-white font-mono-code">${r.hs_code.code}</div>
                            <div class="text-slate-300 text-xs mt-1">${r.hs_code.description}</div>
                            <div class="mt-2 text-[11px] text-[#94A3B8]">
                                Chapter: <span class="text-white font-bold">${r.hs_code.chapter}</span> | Unit: <span class="text-white">${r.hs_code.unit}</span>
                            </div>
                        </div>
                        <div class="space-y-2 border-t md:border-t-0 md:border-l border-[#1E293B] pt-2 md:pt-0 md:pl-4">
                            <div class="text-[#94A3B8] text-[10px]">GAZETTE SOURCE & NOTIFICATION</div>
                            <div class="text-sm font-bold text-[#00D2FF]">${r.circular.source}: ${r.circular.notification_number}</div>
                            <div class="text-slate-400 text-[11px]">${r.circular.title}</div>
                            <div class="text-[10px] text-[#94A3B8] flex items-center gap-3 pt-1">
                                <span>Issued: <b class="text-white">${r.circular.issue_date}</b></span>
                                <span>Effective: <b class="text-[#00D2FF]">${r.effective_from}</b></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded bg-[#0B0F17] border border-[#1E293B]">
                            <div class="text-[#94A3B8] text-[10px]">PREVIOUS RATE (${r.duty_type})</div>
                            <div class="text-lg font-bold text-slate-300">${r.old_rate}%</div>
                        </div>
                        <div class="p-3 rounded bg-[#0B0F17] border border-[#1E293B]">
                            <div class="text-[#94A3B8] text-[10px]">REVISED RATE (${r.duty_type})</div>
                            <div class="text-lg font-bold text-white">${r.new_rate}%</div>
                        </div>
                        <div class="p-3 rounded bg-[#0B0F17] border border-[#1E293B] flex flex-col items-center justify-center">
                            <div class="text-[#94A3B8] text-[10px] mb-1">IMPACT CLASSIFICATION</div>
                            ${deltaBadge}
                        </div>
                    </div>

                    ${r.condition_notes ? `
                        <div class="p-3 rounded bg-[#0B0F17] border border-[#1E293B]">
                            <div class="text-[#94A3B8] text-[10px] mb-1">CONDITION & POLICY NOTES</div>
                            <div class="text-slate-300 text-[11px] leading-relaxed">${r.condition_notes}</div>
                        </div>
                    ` : ''}

                    <div class="flex items-center justify-between pt-3 border-t border-[#1E293B]">
                        <a href="${r.circular.gazette_url || '#'}" target="_blank" class="text-xs text-[#00D2FF] hover:underline flex items-center gap-1">
                            <span>Open Official Gazette Document</span> &rarr;
                        </a>
                        <button onclick="document.getElementById('revisionModal').close(); loadHsIntoSimulator('${r.hs_code.code}', ${r.old_rate}, ${r.new_rate})" class="px-4 py-2 rounded bg-[#00D2FF] text-[#0B0F17] font-bold text-xs hover:bg-[#00D2FF]/90 transition shadow-[0_0_15px_rgba(0,210,255,0.3)]">
                            Launch in Landed Cost Simulator
                        </button>
                    </div>
                `;
            } catch (e) {
                body.innerHTML = '<div class="text-rose-400 py-4">Failed to load revision details.</div>';
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
