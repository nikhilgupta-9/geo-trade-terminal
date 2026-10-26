@extends('layouts.terminal')

@section('title', 'Vectis Institutional Trade Terminal | Regulatory & Landed Cost Intelligence')

@section('content')
<div class="px-4 py-4 space-y-4 max-w-[1700px] mx-auto w-full" x-data="terminalApp()">

    <!-- 1. KPI Metric Intelligence Bar -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <!-- Stat 1: Active HS Codes Monitored -->
        <div class="p-3.5 rounded-md border border-[#1E293B] bg-[#151B26] hover:border-[#00D2FF]/40 transition">
            <div class="flex items-center justify-between text-[#94A3B8] text-[11px] font-mono-code mb-1">
                <span>MONITORED HS CODES</span>
                <span class="text-[#00D2FF]">ITC (HS)</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono-code text-white">{{ $totalHsCodes }}</span>
                <span class="text-[10px] text-emerald-400 font-mono-code">CH 28, 29, 84, 85</span>
            </div>
            <div class="text-[10px] text-[#94A3B8] mt-1 font-mono-code">High-priority industrial chapters</div>
        </div>

        <!-- Stat 2: Gazette Circulars Verified -->
        <div class="p-3.5 rounded-md border border-[#1E293B] bg-[#151B26] hover:border-[#00D2FF]/40 transition">
            <div class="flex items-center justify-between text-[#94A3B8] text-[11px] font-mono-code mb-1">
                <span>GAZETTE NOTIFICATIONS</span>
                <span class="text-[#00D2FF]">DGFT / CBIC</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono-code text-white">{{ $circularsCount }}</span>
                <span class="text-[10px] text-[#00D2FF] font-mono-code">100% PARSED</span>
            </div>
            <div class="text-[10px] text-[#94A3B8] mt-1 font-mono-code">Automated Gazette Ingestion</div>
        </div>

        <!-- Stat 3: Duty Shift Matrix -->
        <div class="p-3.5 rounded-md border border-[#1E293B] bg-[#151B26] hover:border-[#00D2FF]/40 transition">
            <div class="flex items-center justify-between text-[#94A3B8] text-[11px] font-mono-code mb-1">
                <span>TARIFF DISRUPTIONS</span>
                <span class="text-[#EF4444] font-bold">HIKES / CUTS</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono-code text-[#EF4444]">+{{ $hikesCount }}</span>
                <span class="text-lg font-bold font-mono-code text-[#10B981]">-{{ $cutsCount }}</span>
                <span class="text-xs font-mono-code text-cyan-400">({{ $exemptionsCount }} Exempt)</span>
            </div>
            <div class="text-[10px] text-[#94A3B8] mt-1 font-mono-code">Active fiscal revisions recorded</div>
        </div>

        <!-- Stat 4: User Monthly Portfolio Exposure -->
        <div class="p-3.5 rounded-md border border-[#1E293B] bg-[#151B26] hover:border-[#00D2FF]/40 transition">
            <div class="flex items-center justify-between text-[#94A3B8] text-[11px] font-mono-code mb-1">
                <span>PORTFOLIO MONTHLY VARIANCE</span>
                <span class="text-amber-400 font-bold">{{ $currentUser->role }}</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono-code {{ $portfolioNetMonthlyImpact >= 0 ? 'text-[#EF4444]' : 'text-emerald-400' }}">
                    {{ $portfolioNetMonthlyImpact >= 0 ? '+' : '' }}₹{{ number_format(abs($portfolioNetMonthlyImpact), 0) }}
                </span>
                <span class="text-[10px] text-[#94A3B8] font-mono-code">/ mo</span>
            </div>
            <div class="text-[10px] text-[#94A3B8] mt-1 font-mono-code truncate">
                {{ $currentUser->name }} ({{ $currentUser->company }})
            </div>
        </div>

        <!-- Stat 5: Rapid Ingestion Trigger -->
        <div class="p-3.5 rounded-md border border-[#1E293B] bg-[#151B26] flex flex-col justify-between">
            <div class="flex items-center justify-between text-[#94A3B8] text-[11px] font-mono-code">
                <span>GAZETTE INGESTION</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            </div>
            <button @click="triggerLivePoll()" 
                    :disabled="isPolling"
                    class="w-full mt-2 py-1.5 px-3 rounded bg-[#00D2FF]/10 hover:bg-[#00D2FF]/20 border border-[#00D2FF]/40 text-[#00D2FF] font-mono-code text-xs font-bold transition flex items-center justify-center gap-2">
                <svg x-show="!isPolling" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span x-text="isPolling ? 'POLLING FEEDS...' : 'POLL DGFT / CBIC NOW'"></span>
            </button>
            <div class="text-[10px] text-[#94A3B8] mt-1 font-mono-code text-center" x-text="lastPollMessage || 'Scheduled cron active (1m)'"></div>
        </div>
    </div>

    <!-- 2. Terminal Mode Tabs -->
    <div class="border-b border-[#1E293B] flex items-center justify-between gap-4 font-mono-code text-xs">
        <div class="flex items-center gap-1 overflow-x-auto">
            <button @click="activeTab = 'revisions'" 
                    :class="activeTab === 'revisions' ? 'border-[#00D2FF] text-[#00D2FF] bg-[#151B26]' : 'border-transparent text-[#94A3B8] hover:text-white'"
                    class="px-4 py-2.5 border-b-2 font-bold tracking-wider transition flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#00D2FF]"></span>
                <span>1. REGULATORY TARIFF FEED</span>
                <span class="px-1.5 py-0.2 rounded bg-[#00D2FF]/10 text-[10px]">{{ $recentRevisions->count() }}</span>
            </button>

            <button @click="activeTab = 'simulator'" 
                    :class="activeTab === 'simulator' ? 'border-[#00D2FF] text-[#00D2FF] bg-[#151B26]' : 'border-transparent text-[#94A3B8] hover:text-white'"
                    class="px-4 py-2.5 border-b-2 font-bold tracking-wider transition flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span>2. LANDED COST SIMULATOR</span>
            </button>

            <button @click="activeTab = 'watchlist'; loadWatchlist()" 
                    :class="activeTab === 'watchlist' ? 'border-[#00D2FF] text-[#00D2FF] bg-[#151B26]' : 'border-transparent text-[#94A3B8] hover:text-white'"
                    class="px-4 py-2.5 border-b-2 font-bold tracking-wider transition flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>3. PORTFOLIO WATCHLIST</span>
            </button>

            <button @click="activeTab = 'pipeline'; loadCirculars()" 
                    :class="activeTab === 'pipeline' ? 'border-[#00D2FF] text-[#00D2FF] bg-[#151B26]' : 'border-transparent text-[#94A3B8] hover:text-white'"
                    class="px-4 py-2.5 border-b-2 font-bold tracking-wider transition flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>4. GAZETTE INGESTION PIPELINE</span>
            </button>
        </div>

        <div class="hidden lg:flex items-center gap-3 text-[#94A3B8]">
            <span>CBIC EXCHANGE: 1 USD = ₹{{ $exchangeRates['USD'] ?? '86.50' }}</span>
            <span>&bull;</span>
            <span>1 EUR = ₹{{ $exchangeRates['EUR'] ?? '93.20' }}</span>
        </div>
    </div>

    <!-- ================= TAB 1: REGULATORY TARIFF FEED ================= -->
    <div x-show="activeTab === 'revisions'" class="space-y-3">
        <!-- Filter Controls -->
        <div class="p-3 rounded-md bg-[#151B26] border border-[#1E293B] flex flex-wrap items-center justify-between gap-3 text-xs font-mono-code">
            <!-- Chapter Filters -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[#94A3B8] mr-1">CHAPTER:</span>
                <template x-for="ch in ['ALL', '85', '84', '29', '28', '39', '72']" :key="ch">
                    <button @click="selectedChapter = ch; filterRevisions()" 
                            :class="selectedChapter === ch ? 'bg-[#00D2FF] text-[#0B0F17] font-bold' : 'bg-[#0B0F17] text-[#94A3B8] hover:text-white border border-[#1E293B]'"
                            class="px-2.5 py-1 rounded transition">
                        <span x-text="ch === 'ALL' ? 'ALL CHAPTERS' : 'CH ' + ch"></span>
                    </button>
                </template>
            </div>

            <!-- Action Filters & Search -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1">
                    <span class="text-[#94A3B8] mr-1">ACTION:</span>
                    <select x-model="selectedAction" @change="filterRevisions()" class="bg-[#0B0F17] border border-[#1E293B] text-white px-2 py-1 rounded focus:outline-none">
                        <option value="ALL">ALL ACTIONS</option>
                        <option value="INCREASE">DUTY HIKES ONLY</option>
                        <option value="DECREASE">DUTY CUTS ONLY</option>
                        <option value="EXEMPTED">EXEMPTIONS ONLY</option>
                    </select>
                </div>

                <div class="flex items-center gap-1">
                    <span class="text-[#94A3B8] mr-1">SOURCE:</span>
                    <select x-model="selectedSource" @change="filterRevisions()" class="bg-[#0B0F17] border border-[#1E293B] text-white px-2 py-1 rounded focus:outline-none">
                        <option value="ALL">ALL SOURCES</option>
                        <option value="CBIC_CUSTOMS">CBIC CUSTOMS</option>
                        <option value="DGFT">DGFT</option>
                    </select>
                </div>

                <div class="relative">
                    <input type="text" 
                           x-model="searchTerm" 
                           @input.debounce.300ms="filterRevisions()"
                           placeholder="Search HS code, description, circular..."
                           class="bg-[#0B0F17] border border-[#1E293B] text-white pl-8 pr-3 py-1 rounded w-64 focus:border-[#00D2FF] focus:outline-none placeholder:text-slate-600">
                    <svg class="w-3.5 h-3.5 text-[#94A3B8] absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- High-Density Institutional Tariff Table -->
        <div class="rounded-md border border-[#1E293B] bg-[#151B26] overflow-x-auto shadow-xl">
            <table class="w-full text-left text-xs font-mono-code border-collapse">
                <thead>
                    <tr class="border-b border-[#1E293B] bg-[#0B0F17]/90 text-[#94A3B8] uppercase tracking-wider text-[11px]">
                        <th class="py-2.5 px-3">HS Code</th>
                        <th class="py-2.5 px-3">Chapter</th>
                        <th class="py-2.5 px-3">Commodity & Description</th>
                        <th class="py-2.5 px-2 text-center">Type</th>
                        <th class="py-2.5 px-2 text-right">Old Duty</th>
                        <th class="py-2.5 px-2 text-right">New Duty</th>
                        <th class="py-2.5 px-3 text-center">Tariff Delta</th>
                        <th class="py-2.5 px-3">Effective</th>
                        <th class="py-2.5 px-3">Gazette Source</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1E293B]/70" id="revisions-table-body">
                    <template x-for="rev in revisions" :key="rev.id">
                        <tr class="hover:bg-[#1A2232] transition group cursor-pointer" @click="openRevisionDetails(rev.id)">
                            <!-- HS Code -->
                            <td class="py-2.5 px-3 font-bold text-white whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[#00D2FF]" x-text="rev.hs_code.code"></span>
                                </div>
                            </td>
                            <!-- Chapter -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="px-1.5 py-0.5 rounded bg-[#0B0F17] border border-[#1E293B] text-slate-300 font-bold" x-text="'CH ' + rev.hs_code.chapter"></span>
                            </td>
                            <!-- Description -->
                            <td class="py-2.5 px-3 text-slate-300 max-w-md truncate" :title="rev.hs_code.description" x-text="rev.hs_code.description"></td>
                            <!-- Duty Type -->
                            <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold" 
                                      :class="rev.duty_type === 'ADD' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-300'"
                                      x-text="rev.duty_type"></span>
                            </td>
                            <!-- Old Rate -->
                            <td class="py-2.5 px-2 text-right font-medium text-slate-400 whitespace-nowrap" x-text="rev.old_rate + '%'"></td>
                            <!-- New Rate -->
                            <td class="py-2.5 px-2 text-right font-bold text-white whitespace-nowrap" x-text="rev.new_rate + '%'"></td>
                            <!-- Delta Badge -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border inline-block"
                                      :class="{
                                          'text-[#EF4444] bg-rose-500/10 border-rose-500/30': rev.action === 'INCREASE',
                                          'text-[#10B981] bg-emerald-500/10 border-emerald-500/30': rev.action === 'DECREASE',
                                          'text-[#00D2FF] bg-cyan-500/10 border-cyan-500/30': rev.action === 'EXEMPTED'
                                      }">
                                    <span x-text="rev.delta > 0 ? '+' + rev.delta + '%' : (rev.action === 'EXEMPTED' ? 'EXEMPT' : rev.delta + '%')"></span>
                                </span>
                            </td>
                            <!-- Effective Date -->
                            <td class="py-2.5 px-3 text-[#94A3B8] whitespace-nowrap" x-text="rev.effective_from"></td>
                            <!-- Gazette Circular -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] px-1 py-0.2 rounded bg-[#0B0F17] text-[#94A3B8]" x-text="rev.circular.source"></span>
                                    <span class="text-white font-semibold" x-text="rev.circular.notification_number"></span>
                                </div>
                            </td>
                            <!-- Actions -->
                            <td class="py-2.5 px-3 text-right whitespace-nowrap" @click.stop>
                                <button @click="loadHsIntoSimulator(rev.hs_code.code, rev.old_rate, rev.new_rate)"
                                        class="px-2.5 py-1 rounded bg-[#00D2FF]/10 hover:bg-[#00D2FF] hover:text-[#0B0F17] text-[#00D2FF] border border-[#00D2FF]/30 font-bold transition">
                                    Simulate &rarr;
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= TAB 2: LANDED COST SIMULATOR ================= -->
    <div x-show="activeTab === 'simulator'" class="space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            <!-- Left Config Panel (Col 4) -->
            <div class="lg:col-span-4 p-4 rounded-md border border-[#1E293B] bg-[#151B26] space-y-4 font-mono-code text-xs">
                <div class="flex items-center justify-between border-b border-[#1E293B] pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <h2 class="font-bold text-sm text-white">VALUATION PARAMETERS</h2>
                    </div>
                    <span class="text-[#94A3B8] text-[10px]">CBIC RULE 10(2)</span>
                </div>

                <!-- Quick Preset Commodity Selector -->
                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">SELECT PRESET COMMODITY / HS CODE</label>
                    <select x-model="simHsCode" @change="fetchHsData(simHsCode)" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                        <option value="">-- Choose Priority Commodity --</option>
                        @foreach($allHsCodes as $item)
                            <option value="{{ $item->code }}">
                                [CH {{ $item->chapter }}] {{ $item->code }} - {{ \Illuminate\Support\Str::limit($item->description, 45) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- FOB Unit & Currency -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[#94A3B8] text-[10px] block mb-1">UNIT FOB PRICE</label>
                        <input type="number" step="0.01" min="0" x-model.number="simUnitFob" @input.debounce.200ms="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                    </div>
                    <div>
                        <label class="text-[#94A3B8] text-[10px] block mb-1">CURRENCY</label>
                        <select x-model="simCurrency" @change="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                            <option value="USD">USD ($) (Rate: ₹86.50)</option>
                            <option value="EUR">EUR (€) (Rate: ₹93.20)</option>
                            <option value="CNY">CNY (¥) (Rate: ₹11.90)</option>
                            <option value="JPY">JPY (¥) (Rate: ₹0.58)</option>
                            <option value="GBP">GBP (£) (Rate: ₹110.40)</option>
                            <option value="INR">INR (₹) (Base 1.0)</option>
                        </select>
                    </div>
                </div>

                <!-- Shipment Quantity / Container Volume -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[#94A3B8] text-[10px] block mb-1">CONTAINER COUNT</label>
                        <input type="number" min="1" x-model.number="simContainerCount" @input="simQuantity = simContainerCount * simUnitsPerContainer; runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                    </div>
                    <div>
                        <label class="text-[#94A3B8] text-[10px] block mb-1">TOTAL QUANTITY (UNITS)</label>
                        <input type="number" min="1" x-model.number="simQuantity" @input="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                    </div>
                </div>

                <!-- Target Selling Price / Unit in INR -->
                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">TARGET DOMESTIC SELLING PRICE (₹ / UNIT)</label>
                    <input type="number" step="0.01" min="0" x-model.number="simSellingPrice" @input.debounce.200ms="runSimulation()" placeholder="e.g. 15000" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                    <span class="text-[10px] text-slate-500 mt-0.5 block">Used to calculate gross margin erosion delta</span>
                </div>

                <!-- Duty Rates Comparison Inputs -->
                <div class="border-t border-[#1E293B] pt-3 space-y-2">
                    <div class="text-[11px] font-bold text-[#00D2FF] flex items-center justify-between">
                        <span>DUTY RATE OVERRIDES</span>
                        <span class="text-[#94A3B8] font-normal text-[10px]">OLD vs NEW</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[#94A3B8] text-[10px] block mb-0.5">OLD BCD RATE (%)</label>
                            <input type="number" step="0.1" min="0" x-model.number="simOldBcd" @input.debounce.200ms="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-slate-300 p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                        </div>
                        <div>
                            <label class="text-[#94A3B8] text-[10px] block mb-0.5">NEW BCD RATE (%)</label>
                            <input type="number" step="0.1" min="0" x-model.number="simNewBcd" @input.debounce.200ms="runSimulation()" class="w-full bg-[#0B0F17] border border-[#00D2FF]/50 text-[#00D2FF] font-bold p-2 rounded focus:border-[#00D2FF] focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-[#94A3B8] text-[10px] block mb-0.5">SWS RATE</label>
                            <input type="number" step="0.1" min="0" x-model.number="simSwsRate" @input.debounce.200ms="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-slate-300 p-1.5 rounded">
                        </div>
                        <div>
                            <label class="text-[#94A3B8] text-[10px] block mb-0.5">AIDC RATE</label>
                            <input type="number" step="0.1" min="0" x-model.number="simAidcRate" @input.debounce.200ms="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-slate-300 p-1.5 rounded">
                        </div>
                        <div>
                            <label class="text-[#94A3B8] text-[10px] block mb-0.5">IGST RATE</label>
                            <input type="number" step="0.1" min="0" x-model.number="simIgstRate" @input.debounce.200ms="runSimulation()" class="w-full bg-[#0B0F17] border border-[#1E293B] text-slate-300 p-1.5 rounded">
                        </div>
                    </div>
                </div>

                <!-- Add to Watchlist Directly -->
                <button @click="addSimToWatchlist()" class="w-full py-2 rounded bg-[#1A2232] hover:bg-[#1E293B] border border-[#1E293B] text-[#00D2FF] font-bold transition flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Track This HS Code In Watchlist</span>
                </button>
            </div>

            <!-- Right Simulation Results & Visualization (Col 8) -->
            <div class="lg:col-span-8 space-y-4">
                
                <!-- Side-by-Side Comparison Terminal Table -->
                <div class="p-4 rounded-md border border-[#1E293B] bg-[#151B26] shadow-xl font-mono-code text-xs space-y-3" x-show="simResult">
                    <div class="flex items-center justify-between border-b border-[#1E293B] pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :class="simResult?.delta?.action === 'INCREASE' ? 'bg-[#EF4444]' : 'bg-[#10B981]'"></span>
                            <h2 class="font-bold text-sm text-white">DETERMINISTIC LANDED COST VARIANCE MATRIX</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] px-2 py-0.5 rounded font-bold"
                                  :class="simResult?.delta?.action === 'INCREASE' ? 'text-[#EF4444] bg-rose-500/10 border border-rose-500/30' : 'text-[#10B981] bg-emerald-500/10 border border-emerald-500/30'">
                                <span x-text="simResult?.delta?.action === 'INCREASE' ? 'COST INFLATION (HIKE)' : 'DUTY SAVINGS (RELIEF)'"></span>
                            </span>
                            <button @click="exportSimulationJson()" class="px-2 py-0.5 rounded bg-[#0B0F17] hover:bg-[#1E293B] text-[#00D2FF] text-[10px] border border-[#1E293B]">
                                Export JSON
                            </button>
                        </div>
                    </div>

                    <!-- Comparison Grid -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-[#1E293B] bg-[#0B0F17]/80 text-[#94A3B8] text-[11px]">
                                    <th class="py-2 px-3">Valuation Component</th>
                                    <th class="py-2 px-3 text-right">Baseline (Old Tariff)</th>
                                    <th class="py-2 px-3 text-right">Revised (Gazette Tariff)</th>
                                    <th class="py-2 px-3 text-right font-bold text-white">Delta Variance (Δ)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1E293B]/60 text-slate-300">
                                <tr>
                                    <td class="py-2 px-3 font-semibold text-white">FOB Value (Foreign & INR)</td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.baseline?.total_fob_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.revised?.total_fob_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right text-slate-500">₹0.00</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Freight & Insurance (CIF Base)</td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.baseline?.freight_inr + simResult?.baseline?.insurance_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.revised?.freight_inr + simResult?.revised?.insurance_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right text-slate-500">₹0.00</td>
                                </tr>
                                <tr class="bg-[#0B0F17]/40 font-semibold text-white">
                                    <td class="py-2 px-3">Assessable Value (CIF in INR)</td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.baseline?.cif_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.revised?.cif_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right text-slate-500">₹0.00</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Basic Customs Duty (BCD)</td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.baseline?.duties?.bcd_inr).toLocaleString('en-IN') + ' (' + simResult?.baseline?.rates?.bcd + '%)'"></td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.revised?.duties?.bcd_inr).toLocaleString('en-IN') + ' (' + simResult?.revised?.rates?.bcd + '%)'"></td>
                                    <td class="py-2 px-3 text-right font-bold"
                                        :class="simResult?.revised?.duties?.bcd_inr >= simResult?.baseline?.duties?.bcd_inr ? 'text-rose-400' : 'text-emerald-400'"
                                        x-text="(simResult?.revised?.duties?.bcd_inr >= simResult?.baseline?.duties?.bcd_inr ? '+₹' : '-₹') + Number(Math.abs(simResult?.revised?.duties?.bcd_inr - simResult?.baseline?.duties?.bcd_inr)).toLocaleString('en-IN')"></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Social Welfare Surcharge (SWS 10%)</td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.baseline?.duties?.sws_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.revised?.duties?.sws_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right"
                                        :class="simResult?.revised?.duties?.sws_inr >= simResult?.baseline?.duties?.sws_inr ? 'text-rose-400' : 'text-emerald-400'"
                                        x-text="(simResult?.revised?.duties?.sws_inr >= simResult?.baseline?.duties?.sws_inr ? '+₹' : '-₹') + Number(Math.abs(simResult?.revised?.duties?.sws_inr - simResult?.baseline?.duties?.sws_inr)).toLocaleString('en-IN')"></td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3">Integrated GST (IGST 18%)</td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.baseline?.duties?.igst_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right" x-text="'₹' + Number(simResult?.revised?.duties?.igst_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right font-bold"
                                        :class="simResult?.revised?.duties?.igst_inr >= simResult?.baseline?.duties?.igst_inr ? 'text-rose-400' : 'text-emerald-400'"
                                        x-text="(simResult?.revised?.duties?.igst_inr >= simResult?.baseline?.duties?.igst_inr ? '+₹' : '-₹') + Number(Math.abs(simResult?.revised?.duties?.igst_inr - simResult?.baseline?.duties?.igst_inr)).toLocaleString('en-IN')"></td>
                                </tr>
                                <tr class="bg-[#0B0F17] font-bold">
                                    <td class="py-2 px-3 text-[#00D2FF]">TOTAL CUSTOMS DUTIES PAYABLE</td>
                                    <td class="py-2 px-3 text-right text-slate-300" x-text="'₹' + Number(simResult?.baseline?.duties?.total_duty_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right text-white" x-text="'₹' + Number(simResult?.revised?.duties?.total_duty_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right text-sm"
                                        :class="simResult?.delta?.total_duty_delta_inr >= 0 ? 'text-[#EF4444]' : 'text-[#10B981]'"
                                        x-text="(simResult?.delta?.total_duty_delta_inr >= 0 ? '+₹' : '-₹') + Number(Math.abs(simResult?.delta?.total_duty_delta_inr)).toLocaleString('en-IN')"></td>
                                </tr>
                                <tr class="bg-[#1A2232] font-bold text-white text-sm">
                                    <td class="py-3 px-3">TOTAL LANDED COST (SHIPMENT)</td>
                                    <td class="py-3 px-3 text-right text-slate-300" x-text="'₹' + Number(simResult?.baseline?.total_landed_cost_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-3 px-3 text-right text-[#00D2FF]" x-text="'₹' + Number(simResult?.revised?.total_landed_cost_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-3 px-3 text-right"
                                        :class="simResult?.delta?.total_landed_cost_delta_inr >= 0 ? 'text-[#EF4444]' : 'text-[#10B981]'"
                                        x-text="(simResult?.delta?.total_landed_cost_delta_inr >= 0 ? '+₹' : '-₹') + Number(Math.abs(simResult?.delta?.total_landed_cost_delta_inr)).toLocaleString('en-IN')"></td>
                                </tr>
                                <tr class="font-bold">
                                    <td class="py-2 px-3 text-slate-400">Landed Cost Per Unit</td>
                                    <td class="py-2 px-3 text-right text-slate-300" x-text="'₹' + Number(simResult?.baseline?.landed_cost_per_unit_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right text-white" x-text="'₹' + Number(simResult?.revised?.landed_cost_per_unit_inr).toLocaleString('en-IN')"></td>
                                    <td class="py-2 px-3 text-right"
                                        :class="simResult?.delta?.unit_cost_delta_inr >= 0 ? 'text-[#EF4444]' : 'text-[#10B981]'"
                                        x-text="(simResult?.delta?.unit_cost_delta_inr >= 0 ? '+₹' : '-₹') + Number(Math.abs(simResult?.delta?.unit_cost_delta_inr)).toLocaleString('en-IN')"></td>
                                </tr>
                                <!-- Gross Margin Section (if price provided) -->
                                <template x-if="simResult?.baseline?.margin_analysis && simResult?.revised?.margin_analysis">
                                    <tr class="bg-[#0B0F17]/90 font-bold border-t-2 border-[#00D2FF]/30">
                                        <td class="py-2.5 px-3 text-amber-400">GROSS MARGIN % IMPACT</td>
                                        <td class="py-2.5 px-3 text-right text-slate-300" x-text="simResult?.baseline?.margin_analysis?.gross_margin_percent + '%'"></td>
                                        <td class="py-2.5 px-3 text-right text-white" x-text="simResult?.revised?.margin_analysis?.gross_margin_percent + '%'"></td>
                                        <td class="py-2.5 px-3 text-right text-sm"
                                            :class="simResult?.delta?.margin_erosion_percent >= 0 ? 'text-emerald-400' : 'text-[#EF4444]'"
                                            x-text="(simResult?.delta?.margin_erosion_percent >= 0 ? '+' : '') + simResult?.delta?.margin_erosion_percent + '% Margin ' + (simResult?.delta?.margin_erosion_percent < 0 ? 'Erosion' : 'Gain')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Chart.js Visualization Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Duty Components Chart -->
                    <div class="p-4 rounded-md border border-[#1E293B] bg-[#151B26]">
                        <div class="text-xs font-mono-code font-bold text-white mb-2 flex items-center justify-between">
                            <span>DUTY BREAKDOWN (OLD vs NEW)</span>
                            <span class="text-[10px] text-[#94A3B8]">INR</span>
                        </div>
                        <div class="h-56 relative">
                            <canvas id="dutyComparisonChart"></canvas>
                        </div>
                    </div>

                    <!-- Landed Cost & Margin Erosion Chart -->
                    <div class="p-4 rounded-md border border-[#1E293B] bg-[#151B26]">
                        <div class="text-xs font-mono-code font-bold text-white mb-2 flex items-center justify-between">
                            <span>TOTAL LANDED COST IMPACT</span>
                            <span class="text-[10px] text-[#00D2FF]" x-text="simResult?.delta?.cost_percentage_change + '% Net Shift'"></span>
                        </div>
                        <div class="h-56 relative">
                            <canvas id="landedCostChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ================= TAB 3: PORTFOLIO WATCHLIST ================= -->
    <div x-show="activeTab === 'watchlist'" class="space-y-4">
        <div class="p-4 rounded-md border border-[#1E293B] bg-[#151B26] flex flex-wrap items-center justify-between gap-3 font-mono-code text-xs">
            <div>
                <h2 class="text-sm font-bold text-white tracking-wide">ACTIVE PORTFOLIO WATCHLIST</h2>
                <div class="text-[11px] text-[#94A3B8]">
                    Tracking customs duty shifts and calculating real-time financial exposure for 
                    <span class="text-[#00D2FF] font-bold" x-text="watchlistUser.name + ' (' + watchlistUser.role + ')'"></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded bg-[#0B0F17] border border-[#1E293B] text-right">
                    <div class="text-[10px] text-[#94A3B8]">TOTAL MONTHLY EXPOSURE</div>
                    <div class="text-base font-bold" 
                         :class="watchlistExposure >= 0 ? 'text-[#EF4444]' : 'text-emerald-400'"
                         x-text="(watchlistExposure >= 0 ? '+₹' : '-₹') + Number(Math.abs(watchlistExposure)).toLocaleString('en-IN', {minimumFractionDigits: 2})"></div>
                </div>
                <button @click="showAddWatchlist = !showAddWatchlist" class="px-3 py-2 rounded bg-[#00D2FF] text-[#0B0F17] font-bold text-xs hover:bg-[#00D2FF]/90 transition">
                    + Track New HS Code
                </button>
            </div>
        </div>

        <!-- Add to Watchlist Form -->
        <div x-show="showAddWatchlist" x-transition class="p-4 rounded-md border border-[#00D2FF]/30 bg-[#0B0F17] font-mono-code text-xs space-y-3">
            <div class="font-bold text-white text-xs text-[#00D2FF]">ADD COMMODITY TO WATCHLIST</div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">HS CODE</label>
                    <select x-model="newWatchlist.hs_code" class="w-full bg-[#151B26] border border-[#1E293B] text-white p-2 rounded">
                        <option value="">-- Choose HS Code --</option>
                        @foreach($allHsCodes as $item)
                            <option value="{{ $item->code }}">{{ $item->code }} - {{ \Illuminate\Support\Str::limit($item->description, 35) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">CONTAINER BASELINE FOB (₹)</label>
                    <input type="number" step="1000" x-model.number="newWatchlist.baseline_fob_inr" placeholder="2500000" class="w-full bg-[#151B26] border border-[#1E293B] text-white p-2 rounded">
                </div>
                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">MONTHLY CONTAINERS / VOLUME</label>
                    <input type="number" min="1" x-model.number="newWatchlist.monthly_volume_units" placeholder="5" class="w-full bg-[#151B26] border border-[#1E293B] text-white p-2 rounded">
                </div>
                <div class="flex flex-col justify-end">
                    <button @click="submitAddWatchlist()" class="w-full py-2 rounded bg-[#00D2FF] text-[#0B0F17] font-bold hover:bg-[#00D2FF]/90 transition">
                        Confirm & Save to Portfolio
                    </button>
                </div>
            </div>
        </div>

        <!-- Watchlist Table -->
        <div class="rounded-md border border-[#1E293B] bg-[#151B26] overflow-x-auto shadow-xl">
            <table class="w-full text-left text-xs font-mono-code border-collapse">
                <thead>
                    <tr class="border-b border-[#1E293B] bg-[#0B0F17]/90 text-[#94A3B8] uppercase text-[11px]">
                        <th class="py-2.5 px-3">HS Code</th>
                        <th class="py-2.5 px-3">Commodity</th>
                        <th class="py-2.5 px-3 text-right">Baseline Container FOB</th>
                        <th class="py-2.5 px-3 text-center">Monthly Volume</th>
                        <th class="py-2.5 px-3 text-center">Current BCD</th>
                        <th class="py-2.5 px-3 text-center">Latest Revision</th>
                        <th class="py-2.5 px-3 text-right">Monthly Financial Impact</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1E293B]/60">
                    <template x-for="item in watchlistItems" :key="item.id">
                        <tr class="hover:bg-[#1A2232] transition">
                            <td class="py-3 px-3 font-bold text-[#00D2FF] whitespace-nowrap" x-text="item.code"></td>
                            <td class="py-3 px-3 text-slate-300 max-w-xs truncate" x-text="item.description"></td>
                            <td class="py-3 px-3 text-right font-medium text-white whitespace-nowrap" x-text="'₹' + Number(item.baseline_fob_inr).toLocaleString('en-IN')"></td>
                            <td class="py-3 px-3 text-center text-slate-300 whitespace-nowrap" x-text="item.monthly_volume_units + ' Containers'"></td>
                            <td class="py-3 px-3 text-center font-bold text-white whitespace-nowrap" x-text="item.current_bcd + '%'"></td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <template x-if="item.latest_revision">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                          :class="item.latest_revision.action === 'INCREASE' ? 'text-rose-400 bg-rose-500/10 border-rose-500/30' : 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30'"
                                          x-text="item.latest_revision.old_rate + '% &rarr; ' + item.latest_revision.new_rate + '% (' + (item.latest_revision.delta > 0 ? '+' : '') + item.latest_revision.delta + '%)'"></span>
                                </template>
                                <template x-if="!item.latest_revision">
                                    <span class="text-slate-600">No Revisions</span>
                                </template>
                            </td>
                            <td class="py-3 px-3 text-right font-bold whitespace-nowrap">
                                <template x-if="item.impact">
                                    <span :class="item.impact.monthly_inr >= 0 ? 'text-[#EF4444]' : 'text-emerald-400'"
                                          x-text="(item.impact.monthly_inr >= 0 ? '+₹' : '-₹') + Number(Math.abs(item.impact.monthly_inr)).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </template>
                                <template x-if="!item.impact">
                                    <span class="text-slate-600">₹0.00</span>
                                </template>
                            </td>
                            <td class="py-3 px-3 text-right whitespace-nowrap">
                                <button @click="removeWatchlistItem(item.id)" class="text-rose-400 hover:text-rose-300 text-[11px] p-1">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= TAB 4: GAZETTE INGESTION PIPELINE ================= -->
    <div x-show="activeTab === 'pipeline'" class="space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            <!-- Ingested Circulars List (Col 7) -->
            <div class="lg:col-span-7 p-4 rounded-md border border-[#1E293B] bg-[#151B26] space-y-3 font-mono-code text-xs">
                <div class="flex items-center justify-between border-b border-[#1E293B] pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#00D2FF]"></span>
                        <h2 class="font-bold text-sm text-white">INGESTED REGULATORY CIRCULARS</h2>
                    </div>
                    <span class="text-[#00D2FF]" x-text="circulars.length + ' Documents Indexed'"></span>
                </div>

                <div class="space-y-2.5 max-h-[600px] overflow-y-auto">
                    <template x-for="c in circulars" :key="c.id">
                        <div class="p-3 rounded border border-[#1E293B] bg-[#0B0F17] hover:border-[#00D2FF]/30 transition space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                          :class="c.source === 'CBIC_CUSTOMS' ? 'bg-[#00D2FF]/10 text-[#00D2FF] border border-[#00D2FF]/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30'"
                                          x-text="c.source"></span>
                                    <span class="font-bold text-white" x-text="c.notification_number"></span>
                                </div>
                                <span class="px-2 py-0.2 rounded text-[10px] font-bold"
                                      :class="c.parse_status === 'PARSED' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400'"
                                      x-text="c.parse_status"></span>
                            </div>

                            <div class="text-slate-300 text-xs line-clamp-2" x-text="c.title"></div>

                            <div class="flex items-center justify-between text-[10px] text-[#94A3B8] pt-1 border-t border-[#1E293B]/50">
                                <div>
                                    <span>Issued: <b class="text-white" x-text="c.issue_date"></b></span> | 
                                    <span>Effective: <b class="text-[#00D2FF]" x-text="c.effective_date"></b></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-400" x-text="c.revisions_count + ' Revisions Mapped'"></span>
                                    <button @click="reparseCircular(c.id)" class="text-[#00D2FF] hover:underline">
                                        Re-Parse
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Gazette Parser Simulation Studio (Col 5) -->
            <div class="lg:col-span-5 p-4 rounded-md border border-[#1E293B] bg-[#151B26] space-y-3 font-mono-code text-xs">
                <div class="flex items-center justify-between border-b border-[#1E293B] pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <h2 class="font-bold text-sm text-white">GAZETTE PARSER SIMULATION STUDIO</h2>
                    </div>
                    <span class="text-amber-400 text-[10px]">AI / REGEX EXTRACTION</span>
                </div>

                <div class="space-y-2">
                    <label class="text-[#94A3B8] text-[10px] block">LOAD PRESET GAZETTE TEXT</label>
                    <div class="flex flex-wrap gap-1.5">
                        <button @click="loadPresetGazette(1)" class="px-2 py-1 rounded bg-[#0B0F17] hover:bg-[#1E293B] text-slate-300 border border-[#1E293B] text-[10px]">
                            Solar & Semiconductors (Hike)
                        </button>
                        <button @click="loadPresetGazette(2)" class="px-2 py-1 rounded bg-[#0B0F17] hover:bg-[#1E293B] text-slate-300 border border-[#1E293B] text-[10px]">
                            Chemicals & APIs (Ch 29)
                        </button>
                        <button @click="loadPresetGazette(3)" class="px-2 py-1 rounded bg-[#0B0F17] hover:bg-[#1E293B] text-slate-300 border border-[#1E293B] text-[10px]">
                            Anti-Dumping Resin (ADD)
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-[#94A3B8] text-[10px] block mb-1">SOURCE</label>
                        <select x-model="simGazetteSource" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded">
                            <option value="CBIC_CUSTOMS">CBIC CUSTOMS</option>
                            <option value="DGFT">DGFT</option>
                            <option value="MINISTRY_COMMERCE">MINISTRY COMMERCE</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[#94A3B8] text-[10px] block mb-1">NOTIFICATION NUMBER</label>
                        <input type="text" x-model="simGazetteNumber" placeholder="e.g. 22/2026-Customs" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded">
                    </div>
                </div>

                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">NOTIFICATION TITLE</label>
                    <input type="text" x-model="simGazetteTitle" placeholder="Notification Title" class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded">
                </div>

                <div>
                    <label class="text-[#94A3B8] text-[10px] block mb-1">RAW GAZETTE / CIRCULAR TEXT</label>
                    <textarea x-model="simGazetteText" rows="7" placeholder="Paste official notification text here..." class="w-full bg-[#0B0F17] border border-[#1E293B] text-white p-2 rounded focus:border-[#00D2FF] focus:outline-none font-mono-code text-[11px]"></textarea>
                </div>

                <button @click="runGazetteSimulation()" 
                        :disabled="isParsingGazette"
                        class="w-full py-2.5 rounded bg-[#00D2FF] text-[#0B0F17] font-bold text-xs hover:bg-[#00D2FF]/90 transition shadow-[0_0_15px_rgba(0,210,255,0.25)] flex items-center justify-center gap-2">
                    <svg x-show="!isParsingGazette" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span x-text="isParsingGazette ? 'RUNNING PARSER & RECALCULATING IMPACT...' : 'EXECUTE INGESTION & DISPATCH ALERTS'"></span>
                </button>

                <!-- Console Output Log -->
                <div x-show="parserOutput" class="p-3 rounded bg-[#0B0F17] border border-[#1E293B] space-y-1">
                    <div class="text-[10px] text-emerald-400 font-bold">PARSER EXECUTION LOG:</div>
                    <pre class="text-[10px] text-slate-300 overflow-x-auto max-h-36 whitespace-pre-wrap" x-text="parserOutput"></pre>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function terminalApp() {
    return {
        activeTab: 'revisions',
        isPolling: false,
        lastPollMessage: '',
        revisions: @json($recentRevisions),
        selectedChapter: 'ALL',
        selectedAction: 'ALL',
        selectedSource: 'ALL',
        searchTerm: '',

        // Landed Cost Simulator State
        simHsCode: '85414300',
        simUnitFob: 180.00,
        simCurrency: 'USD',
        simContainerCount: 1,
        simUnitsPerContainer: 1000,
        simQuantity: 1000,
        simSellingPrice: 22000.00,
        simOldBcd: 25.0,
        simNewBcd: 40.0,
        simSwsRate: 10.0,
        simAidcRate: 0.0,
        simIgstRate: 12.0,
        simResult: null,

        // Charts
        dutyChart: null,
        landedChart: null,

        // Watchlist State
        watchlistItems: [],
        watchlistUser: {},
        watchlistExposure: 0,
        showAddWatchlist: false,
        newWatchlist: {
            hs_code: '',
            baseline_fob_inr: 2500000,
            monthly_volume_units: 5
        },

        // Pipeline State
        circulars: [],
        isParsingGazette: false,
        simGazetteSource: 'CBIC_CUSTOMS',
        simGazetteNumber: '19/2026-Customs',
        simGazetteTitle: 'Rationalization of Customs Tariff on Lithium Precursors and Electric Battery Assemblies',
        simGazetteText: `GOVERNMENT OF INDIA\nMINISTRY OF FINANCE (DEPARTMENT OF REVENUE)\nNotification No. 19/2026-Customs\n\nIn exercise of powers conferred by Section 25 of the Customs Act, 1962:\nIn the Table:\n(i) against entry "8507 60 00", for entry "10%", entry "15%" shall be substituted; [Lithium-ion accumulators and battery cells]\n(ii) against entry "2825 20 00", for entry "0%", entry "5%" shall be substituted; [Lithium oxide and hydroxide precursor]`,
        parserOutput: '',

        init() {
            this.runSimulation();
        },

        async filterRevisions() {
            try {
                const params = new URLSearchParams({
                    chapter: this.selectedChapter,
                    action: this.selectedAction,
                    source: this.selectedSource,
                    search: this.searchTerm
                });
                const res = await fetch(`/api/v1/revisions?${params.toString()}`);
                const data = await res.json();
                this.revisions = data.data;
            } catch (e) {
                console.error(e);
            }
        },

        async fetchHsData(code) {
            if (!code) return;
            try {
                const res = await fetch(`/api/v1/calculator/simulate/${code}`);
                const data = await res.json();
                if (data.success) {
                    const hs = data.hs_code;
                    const rev = data.revision;
                    this.simOldBcd = rev ? rev.old_rate : hs.base_bcd_rate;
                    this.simNewBcd = rev ? rev.new_rate : hs.base_bcd_rate;
                    this.simSwsRate = hs.sws_rate;
                    this.simAidcRate = hs.aidc_rate;
                    this.simIgstRate = hs.igst_rate;
                    this.runSimulation();
                }
            } catch (e) {
                console.error(e);
            }
        },

        loadHsIntoSimulator(code, oldBcd, newBcd) {
            this.activeTab = 'simulator';
            this.simHsCode = code;
            this.simOldBcd = parseFloat(oldBcd);
            this.simNewBcd = parseFloat(newBcd);
            this.fetchHsData(code);
        },

        async runSimulation() {
            try {
                const payload = {
                    unit_fob: this.simUnitFob,
                    quantity: this.simQuantity,
                    currency: this.simCurrency,
                    old_bcd: this.simOldBcd,
                    new_bcd: this.simNewBcd,
                    sws_rate: this.simSwsRate,
                    aidc_rate: this.simAidcRate,
                    igst_rate: this.simIgstRate,
                    target_selling_price: this.simSellingPrice
                };

                const res = await fetch('/api/v1/calculator/compare', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    this.simResult = json.data;
                    this.updateCharts(json.data);
                }
            } catch (e) {
                console.error(e);
            }
        },

        updateCharts(data) {
            const ctx1 = document.getElementById('dutyComparisonChart');
            const ctx2 = document.getElementById('landedCostChart');

            if (ctx1) {
                if (this.dutyChart) this.dutyChart.destroy();
                this.dutyChart = new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: ['Baseline (Old Tariff)', 'Revised (Gazette Tariff)'],
                        datasets: [
                            {
                                label: 'Basic Customs Duty (BCD)',
                                data: [data.baseline.duties.bcd_inr, data.revised.duties.bcd_inr],
                                backgroundColor: '#00D2FF',
                            },
                            {
                                label: 'Social Welfare Surcharge (SWS)',
                                data: [data.baseline.duties.sws_inr, data.revised.duties.sws_inr],
                                backgroundColor: '#F59E0B',
                            },
                            {
                                label: 'Integrated GST (IGST)',
                                data: [data.baseline.duties.igst_inr, data.revised.duties.igst_inr],
                                backgroundColor: '#818CF8',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: true, grid: { color: '#1E293B' }, ticks: { color: '#94A3B8', font: { family: 'JetBrains Mono' } } },
                            y: { stacked: true, grid: { color: '#1E293B' }, ticks: { color: '#94A3B8', font: { family: 'JetBrains Mono' } } }
                        },
                        plugins: {
                            legend: { labels: { color: '#F8FAFC', font: { family: 'JetBrains Mono', size: 10 } } }
                        }
                    }
                });
            }

            if (ctx2) {
                if (this.landedChart) this.landedChart.destroy();
                this.landedChart = new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: ['Landed Cost Per Unit (₹)', 'Total Shipment Cost (₹) / 1000'],
                        datasets: [
                            {
                                label: 'Baseline',
                                data: [data.baseline.landed_cost_per_unit_inr, data.baseline.total_landed_cost_inr / 1000],
                                backgroundColor: '#475569',
                            },
                            {
                                label: 'Revised Gazette Tariff',
                                data: [data.revised.landed_cost_per_unit_inr, data.revised.total_landed_cost_inr / 1000],
                                backgroundColor: data.delta.action === 'INCREASE' ? '#EF4444' : '#10B981',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { grid: { color: '#1E293B' }, ticks: { color: '#94A3B8', font: { family: 'JetBrains Mono' } } },
                            y: { grid: { color: '#1E293B' }, ticks: { color: '#94A3B8', font: { family: 'JetBrains Mono' } } }
                        },
                        plugins: {
                            legend: { labels: { color: '#F8FAFC', font: { family: 'JetBrains Mono', size: 10 } } }
                        }
                    }
                });
            }
        },

        exportSimulationJson() {
            if (!this.simResult) return;
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(this.simResult, null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", `vectis_simulation_hs_${this.simHsCode}.json`);
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
        },

        async addSimToWatchlist() {
            if (!this.simHsCode) return;
            try {
                const res = await fetch('/api/v1/watchlist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        hs_code: this.simHsCode,
                        baseline_fob_inr: this.simResult ? this.simResult.baseline.total_fob_inr : 2500000,
                        monthly_volume_units: this.simContainerCount || 1
                    })
                });
                const data = await res.json();
                alert(data.message || 'Added to watchlist!');
            } catch (e) {
                console.error(e);
            }
        },

        async loadWatchlist() {
            try {
                const res = await fetch('/api/v1/watchlist');
                const data = await res.json();
                if (data.success) {
                    this.watchlistItems = data.data;
                    this.watchlistUser = data.user;
                    this.watchlistExposure = data.total_monthly_exposure_inr;
                }
            } catch (e) {
                console.error(e);
            }
        },

        async submitAddWatchlist() {
            if (!this.newWatchlist.hs_code) return;
            try {
                const res = await fetch('/api/v1/watchlist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newWatchlist)
                });
                const data = await res.json();
                if (data.success) {
                    this.showAddWatchlist = false;
                    this.loadWatchlist();
                } else {
                    alert(data.message);
                }
            } catch (e) {
                console.error(e);
            }
        },

        async removeWatchlistItem(id) {
            if (!confirm('Remove this HS Code from active watchlist?')) return;
            try {
                await fetch(`/api/v1/watchlist/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                this.loadWatchlist();
            } catch (e) {
                console.error(e);
            }
        },

        async loadCirculars() {
            try {
                const res = await fetch('/api/v1/circulars');
                const data = await res.json();
                if (data.success) {
                    this.circulars = data.data;
                }
            } catch (e) {
                console.error(e);
            }
        },

        async triggerLivePoll() {
            this.isPolling = true;
            this.lastPollMessage = 'Contacting CBIC and DGFT endpoints...';
            try {
                const res = await fetch('/api/v1/circulars/poll', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await res.json();
                this.lastPollMessage = data.message;
                this.filterRevisions();
                if (this.activeTab === 'pipeline') this.loadCirculars();
            } catch (e) {
                this.lastPollMessage = 'Polling error.';
            } finally {
                this.isPolling = false;
            }
        },

        loadPresetGazette(preset) {
            if (preset === 1) {
                this.simGazetteNumber = '24/2026-Customs';
                this.simGazetteTitle = 'Solar Module BCD Hike to protect domestic manufacturing';
                this.simGazetteText = `GOVERNMENT OF INDIA\nMINISTRY OF FINANCE (DEPARTMENT OF REVENUE)\nNotification No. 24/2026-Customs\n\nIn the Table:\n(i) against entry "8541 43 00", in column (4), for entry "25%", entry "40%" shall be substituted; [Solar cells assembled into modules]\n(ii) against entry "8486 20 00", for entry "7.5%", entry "0%" shall be substituted; [Semiconductor wafer fabrication machinery]`;
            } else if (preset === 2) {
                this.simGazetteNumber = '49/2025-2026-DGFT';
                this.simGazetteTitle = 'DGFT Notification on Bulk Organic Chemicals Tariff';
                this.simGazetteText = `GOVERNMENT OF INDIA\nDIRECTORATE GENERAL OF FOREIGN TRADE\nNotification No. 49/2025-2026\n\nTariff Item 2905 11 00: Old BCD 5.0%, New BCD 7.5%.\nTariff Item 2915 21 00: Current BCD rate 7.5%, New revised BCD rate 10.0%.`;
            } else if (preset === 3) {
                this.simGazetteNumber = '28/2026-Customs (ADD)';
                this.simGazetteTitle = 'Definitive Anti-Dumping Duty on PET Resin Polymers';
                this.simGazetteText = `MINISTRY OF FINANCE\nNotification No. 28/2026-Customs (ADD)\n\nTariff item 3907 61 00: Old rate BCD 10.0%, ADD imposed bringing effective duty to 18.5%.\nEffective immediately.`;
            }
        },

        async runGazetteSimulation() {
            this.isParsingGazette = true;
            this.parserOutput = 'Submitting gazette notification to Vectis Ingestion Engine...';
            try {
                const res = await fetch('/api/v1/circulars/simulate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        source: this.simGazetteSource,
                        notification_number: this.simGazetteNumber,
                        title: this.simGazetteTitle,
                        raw_text: this.simGazetteText
                    })
                });
                const json = await res.json();
                this.parserOutput = JSON.stringify(json, null, 2);
                this.loadCirculars();
                this.filterRevisions();
                alert(json.message);
            } catch (e) {
                this.parserOutput = 'Error running parser simulation: ' + e.message;
            } finally {
                this.isParsingGazette = false;
            }
        },

        async reparseCircular(id) {
            try {
                const res = await fetch(`/api/v1/circulars/${id}/reparse`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const json = await res.json();
                alert(json.message);
                this.loadCirculars();
                this.filterRevisions();
            } catch (e) {
                console.error(e);
            }
        }
    };
}
</script>
@endpush
