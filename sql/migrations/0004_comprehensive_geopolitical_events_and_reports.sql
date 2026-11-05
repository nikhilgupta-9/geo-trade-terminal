-- Migration 0004: Comprehensive Institutional Geopolitical Intelligence
-- Expands schema with severity, natural resources, predictive scenarios, and detailed corporate profiles.
-- Seeds full real-world dossiers: Venezuela, Middle East/Red Sea, Russia-Ukraine, Binance, Google Antitrust, 2027 UP Elections, France Unrest, Brazil, Critical Minerals.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Schema Enhancements
ALTER TABLE macro_events 
    ADD COLUMN severity ENUM('critical','high','medium','low') NOT NULL DEFAULT 'high',
    ADD COLUMN resources_affected_json JSON NULL,
    ADD COLUMN scenarios_json JSON NULL;

ALTER TABLE report_company_impacts
    ADD COLUMN sector VARCHAR(150) NULL,
    ADD COLUMN exchange VARCHAR(50) NULL DEFAULT 'NYSE',
    ADD COLUMN market_cap VARCHAR(100) NULL,
    ADD COLUMN financial_metrics_json JSON NULL,
    ADD COLUMN revenue_exposure_geo TEXT NULL,
    ADD COLUMN overview TEXT NULL;

-- 2. Clean any prior duplicates
DELETE FROM macro_events WHERE title IN (
    'Israel-Iran Escalation & US Red Sea Chokepoint Disruption',
    'Russia-Ukraine US-Brokered Peace Framework Talks',
    'Binance Hot-Wallet Security Breach & Regulatory Enforcement Action',
    'Alphabet / Google DOJ Antitrust Ruling & AdTech Unbundling',
    'France Political Gridlock & European Union Fiscal Reform Crisis',
    'Brazil Lula Re-Industrialization & Amazon Critical Mineral Mandates',
    'Global Critical Minerals Realignment & Lithium Supply Security'
);

-- Update existing UP 2027 event to published status with scenarios
UPDATE macro_reports SET status = 'published' WHERE headline LIKE '%UP 2027%';
UPDATE macro_reports SET status = 'published' WHERE headline LIKE '%peace framework%';

-- ============================================================
-- EVENT 1: Israel-Iran Escalation & Red Sea Chokepoint
-- ============================================================
INSERT INTO macro_events (title, category, timing, severity, event_date, countries_json, summary, resources_affected_json, sources_json) VALUES
(
    'Israel-Iran Escalation & US Red Sea Chokepoint Disruption',
    'war_conflict',
    'reactive',
    'critical',
    '2026-10-24',
    JSON_ARRAY('Israel', 'Iran', 'United States', 'Yemen', 'Saudi Arabia'),
    'Direct kinetic exchanges between Israel and Iran, paired with persistent maritime missile targeting across the Bab-el-Mandeb strait, have forced global commercial container and tanker traffic to bypass the Suez Canal. The resulting 14-to-18-day voyage detour via the Cape of Good Hope has created systemic ton-mile capacity shortages, spiking maritime freight indices and oil risk premiums.',
    JSON_ARRAY('Middle East Light Crude', 'Liquefied Natural Gas (LNG)', 'Aviation Jet Fuel', 'Urea & Nitrogen Fertilizers'),
    JSON_ARRAY(
        JSON_OBJECT('title', 'Red Sea maritime transit down 68%', 'url', 'https://example.com/red-sea-crisis', 'publisher', 'S&P Global Commodity Insights', 'published_at', '2026-10-24')
    )
);
SET @mideast_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note, published_at) VALUES
(
    @mideast_event_id,
    'Red Sea chokepoint forces Cape of Good Hope rerouting: tanker rates surge 190% as airlines absorb jet fuel shock',
    'published',
    'risk_off',
    'Broad institutional risk-off positioning across European equities and fuel-sensitive transport sectors, heavily contrasted with sharp capital inflows into Western defense primes and spot tanker operators. Brent crude risk premium has widened by $7.80/barrel.',
    'Capital is rotating aggressively out of long-haul passenger airlines and European import-dependent chemical manufacturers into US defense primes (replenishment pipelines) and product tanker operators enjoying multi-decade ton-mile day rates. Watch the Strait of Hormuz insurance surcharges -- any secondary escalation into Persian Gulf tanker loadings would trigger a secondary supply shock.',
    NOW()
);
SET @mideast_report_id = LAST_INSERT_ID();

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order) VALUES
(@mideast_report_id, 'Crude & Product Tanker Shipping', 'gainer', 5, 'Vessels forced into 14-day Cape of Good Hope transit absorb massive fleet capacity; VLCC daily charter rates jumped above $110,000/day.', 1),
(@mideast_report_id, 'Aerospace & Guided Defense Contractors', 'gainer', 5, 'Urgent foreign military sales (FMS) allocations for Patriot PAC-3 interceptors, THAAD batteries, and precision standoff munitions.', 2),
(@mideast_report_id, 'International Passenger Aviation', 'loser', 4, 'Severe fuel expense headwinds compounded by airspace detour routing penalties across Middle Eastern flight corridors.', 3),
(@mideast_report_id, 'European Specialty Chemicals', 'loser', 4, 'Feedstock supply disruptions and extended ocean transit times for Asian intermediate chemical exports into Mediterranean ports.', 4);

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order) VALUES
(
    @mideast_report_id, 'Frontline plc', 'FRO', 'Cyprus / United States', 'gainer',
    'Crude Tanker Shipping', 'NYSE', '$5.8B',
    'Frontline is one of the world\'s largest modern VLCC and Suezmax crude oil tanker operators, commanding an ultra-young, eco-scrubber fleet optimized for global long-haul arbitrage.',
    'Frontline benefits directly from the structural expansion in ton-mile demand. Over 78% of its fleet is positioned on spot market rates, allowing immediate cash-flow capture as voyage rates surged past $112,000/day.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Acquired 24 modern ECO-VLCCs, shifting fleet composition toward high spot market exposure.'),
        JSON_OBJECT('period', '2026 Q3', 'note', 'Secured 3-year term charters at premium index-linked rates amid Red Sea crisis.')
    ),
    JSON_OBJECT('operating_margin', '48.2%', 'debt_to_equity', '0.64', 'dividend_yield', '11.4%', 'geo_risk_beta', '1.85'),
    '72% Asia-Europe and US Gulf routes, highly leveraged to Middle East chokepoints.',
    1
),
(
    @mideast_report_id, 'Lockheed Martin Corporation', 'LMT', 'United States', 'gainer',
    'Defense & Aerospace', 'NYSE', '$115B',
    'Global premier defense prime contractor specializing in tactical air superiority, missile defense systems, and command-and-control battle management.',
    'Pentagon and allied nations issued expedited replenishment contracts for Patriot PAC-3 MSE interceptors, THAAD missile batteries, and Aegis combat integration to counter drone and ballistic swarms.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2025', 'note', 'Expanded Camden, Arkansas facility to double PAC-3 production to 650 missiles annually.'),
        JSON_OBJECT('period', '2026', 'note', 'Signed $4.5B multi-year FMS contract with Israel, UAE, and European NATO partners.')
    ),
    JSON_OBJECT('operating_margin', '12.8%', 'order_backlog', '$160B', 'free_cash_flow', '$6.2B', 'geo_risk_beta', '0.35'),
    '71% US Department of Defense, 29% International Allied Governments.',
    2
),
(
    @mideast_report_id, 'Deutsche Lufthansa AG', 'LHA', 'Germany', 'loser',
    'Aviation & Logistics', 'XETRA', '$8.5B',
    'Europe\'s largest airline conglomerate operating network carriers Lufthansa, Swiss, Austrian, and Brussels Airlines, along with extensive Lufthansa Cargo operations.',
    'Facing double compression: jet fuel crack spreads expanding significantly while rerouting European flights to India and East Asia south of Iranian and Arabian airspace adds 90-120 minutes of high-burn flight time per rotation.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Restructured fleet post-pandemic, carrying elevated corporate debt and high labor concessions.'),
        JSON_OBJECT('period', '2026 Q3', 'note', 'Suspended Tel Aviv, Beirut, and Tehran scheduled services indefinitely.')
    ),
    JSON_OBJECT('operating_margin', '4.1%', 'fuel_cost_ratio', '31.2%', 'net_debt_ebitda', '2.4x', 'geo_risk_beta', '-0.92'),
    '38% Transatlantic, 28% European Domestic, 22% Asia-Pacific/Middle East.',
    3
);

-- ============================================================
-- EVENT 2: Russia-Ukraine US-Brokered Peace Talks (Enrichment)
-- ============================================================
UPDATE macro_reports SET
    headline = 'US-brokered Russia-Ukraine peace framework reprices European reconstruction, natural gas, and defense multiples',
    market_stance = 'risk_on',
    status = 'published',
    published_at = NOW(),
    market_summary = 'Clear European risk-on catalyst: benchmark European natural gas (TTF) contracts eased 18% as risk premia unwound, German DAX equities advanced, and international infrastructure consortiums initiated capital formation for the $480B reconstruction pipeline.',
    market_flow_note = 'Institutional capital is rotating out of hyper-extended defense primes into cyclical industrials, heavy civil engineering, cement conglomerates, and agrochemical majors. Long-term European rearmament pledges remain intact, but short-term spot valuation multiples are contracting.'
WHERE event_id = @ru_ua_event_id OR headline LIKE '%peace framework%';

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order)
SELECT r.id, 'Eastern European Building Materials & Cement', 'gainer', 5, 'First-priority physical materials required for civil reconstruction, bridges, transport corridors, and housing rehabilitation.', 1
FROM macro_reports r WHERE r.headline LIKE '%peace framework%' LIMIT 1;

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order)
SELECT r.id, 'Global Agribusiness & Fertilizer Transit', 'gainer', 4, 'Normalization of Black Sea deepwater grain terminals and the Togliatti-Odessa ammonia pipeline reduces maritime insurance risk.', 2
FROM macro_reports r WHERE r.headline LIKE '%peace framework%' LIMIT 1;

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order)
SELECT r.id, 'European Defense Primes', 'mixed', 3, 'Sentiment cooling on immediate artillery procurement urgent spot orders, counterbalanced by 10-year NATO 2% GDP treaty spending commitments.', 3
FROM macro_reports r WHERE r.headline LIKE '%peace framework%' LIMIT 1;

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order)
SELECT r.id, 'Holcim Ltd', 'HOLN', 'Switzerland', 'gainer',
    'Building Materials & Infrastructure', 'SIX Swiss', '$54B',
    'World leader in sustainable building materials, low-carbon concrete, and aggregates, with extensive existing quarry and dispatch terminals across Poland, Romania, and Eastern Europe.',
    'Prime commercial position to supply low-carbon cement into Ukrainian reconstruction corridors. Estimated addressable concrete demand exceeds 120 million metric tonnes over an 8-year post-war window.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2023-2025', 'note', 'Acquired 8 specialty aggregate and concrete assets in Central/Eastern Europe.'),
        JSON_OBJECT('period', '2026', 'note', 'Signed preliminary memorandum with European Bank for Reconstruction and Development (EBRD).')
    ),
    JSON_OBJECT('operating_margin', '17.8%', 'free_cash_flow', '$3.8B', 'roe', '14.5%', 'geo_risk_beta', '0.62'),
    '40% North America, 32% Europe, 18% Latin America.',
    1
FROM macro_reports r WHERE r.headline LIKE '%peace framework%' LIMIT 1;

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order)
SELECT r.id, 'Rheinmetall AG', 'RHM', 'Germany', 'mixed',
    'Defense & Security Technology', 'XETRA', '$28B',
    'Germany\'s premier defense contractor and Europe\'s largest manufacturer of 155mm artillery shells, Leopard 2 chassis systems, and armored infantry fighting vehicles.',
    'Stock traded at 38x forward earnings on peak wartime replacement demand. Peace framework talks trigger near-term multiple compression, though long-term German special defense fund (Sondervermögen) contracts limit structural downside.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Commissioned new ammunition manufacturing plant in Unterlüss to reach 700k shells/yr capacity.'),
        JSON_OBJECT('period', '2026 Q2', 'note', 'Established joint venture armored vehicle repair center in Western Ukraine.')
    ),
    JSON_OBJECT('forward_pe', '34.2x', 'operating_margin', '15.4%', 'backlog_coverage', '4.2 years', 'geo_risk_beta', '1.45'),
    '82% European NATO governments, 18% Rest of World.',
    2
FROM macro_reports r WHERE r.headline LIKE '%peace framework%' LIMIT 1;

-- ============================================================
-- EVENT 3: UP 2027 State Elections (Enrichment with Scenarios & Companies)
-- ============================================================
UPDATE macro_events SET
    severity = 'high',
    scenarios_json = JSON_OBJECT(
        'scenario_alpha', JSON_OBJECT(
            'probability', '65%',
            'name', 'Industrial Continuity & Expressway Expansion',
            'summary', 'Ruling party retains majority. Uninterrupted capex continuity for Ganga Expressway phase 2, Jewar Airport Aerotropolis, and UP Defence Industrial Corridor.',
            'top_gainers', JSON_ARRAY('Larsen & Toubro', 'UltraTech Cement', 'PNC Infratech', 'Bharat Electronics'),
            'capital_flow', 'Aggressive domestic capital allocation toward industrial EPC, cement dispatch, and urban land development.'
        ),
        'scenario_beta', JSON_OBJECT(
            'probability', '35%',
            'name', 'Populist Agrarian Rebalancing & Direct Subsidies',
            'summary', 'Opposition coalition victory triggers review of private infrastructure concessions, shifting state budget into farmer loan waivers, free electricity, and rural direct transfers.',
            'top_gainers', JSON_ARRAY('Dabur India', 'Mahindra & Mahindra Tractors', 'ITC Ltd', 'UPL Ltd'),
            'capital_flow', 'Capital rotates rapidly from high-capex infrastructure concessionaires into rural consumer staples, tractors, and fertilizers.'
        )
    )
WHERE id = @up_event_id OR title LIKE '%Uttar Pradesh%';

UPDATE macro_reports SET
    headline = 'UP 2027 Election Radar: 65% Continuity probability favors EPC & Cement; 35% Agrarian pivot would ignite Rural FMCG',
    market_stance = 'sector_rotation',
    status = 'published',
    published_at = NOW(),
    market_summary = 'Uttar Pradesh represents 8.4% of India\'s GDP and the highest concentration of ongoing expressway and industrial corridor megaprojects in South Asia. Equity markets are currently pricing a 65% policy continuity premium across heavy capital goods.',
    market_flow_note = 'Domestic institutional funds (DIIs) are currently overweight Uttar Pradesh infrastructure proxies (L&T, UltraTech). Analysts recommend hedging portfolio beta by scaling into rural consumption baskets (Dabur, M&M Tractors) 6 months prior to polling.'
WHERE event_id = @up_event_id OR headline LIKE '%UP 2027%';

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order)
SELECT r.id, 'Expressway EPC & Heavy Civil Infrastructure', 'gainer', 5, 'Under Scenario Alpha (65%), state allocation for expressways and dedicated freight corridor hubs guarantees 22% order book CAGR.', 1
FROM macro_reports r WHERE r.headline LIKE '%UP 2027%' LIMIT 1;

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order)
SELECT r.id, 'Northern Regional Cement Manufacturers', 'gainer', 4, 'Regional demand driven by infrastructure dispatches and Pradhan Mantri Awas Yojana rural housing completion deadlines.', 2
FROM macro_reports r WHERE r.headline LIKE '%UP 2027%' LIMIT 1;

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order)
SELECT r.id, 'Rural FMCG & Consumer Staples', 'gainer', 4, 'Direct beneficiary under Scenario Beta (35%) if electoral competition accelerates populist cash transfers and farmer subsidies.', 3
FROM macro_reports r WHERE r.headline LIKE '%UP 2027%' LIMIT 1;

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order)
SELECT r.id, 'Larsen & Toubro Ltd', 'LT.NS', 'India', 'gainer',
    'Engineering, Procurement & Construction (EPC)', 'NSE', '₹4.8T ($58B)',
    'India\'s preeminent technology, engineering, construction, and manufacturing conglomerate with critical execution roles across national infrastructure and defense.',
    'Primary EPC contractor on the Noida International Airport (Jewar), Eastern Dedicated Freight Corridor feeder lines, and Western UP water transmission grids. Extremely high contract execution predictability under policy continuity.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Bagged ₹12,000 Cr civil construction contracts across UP state transport infrastructure.'),
        JSON_OBJECT('period', '2026', 'note', 'Selected as anchor defense manufacturing contractor in Aligarh node of UP Defence Corridor.')
    ),
    JSON_OBJECT('order_book', '₹4.75 Trillion', 'ebitda_margin', '11.2%', 'roe', '16.8%', 'geo_risk_beta', '0.55'),
    '74% Domestic India (with UP accounting for ~14% of civil order book), 26% Middle East.',
    1
FROM macro_reports r WHERE r.headline LIKE '%UP 2027%' LIMIT 1;

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order)
SELECT r.id, 'UltraTech Cement Ltd', 'ULTRACEMCO.NS', 'India', 'gainer',
    'Cement & Building Materials', 'NSE', '₹3.1T ($37B)',
    'Flagship cement company of Aditya Birla Group, the third largest cement manufacturer in the world outside of China, operating 150+ MTPA installed capacity.',
    'Dominant cement pricing power across Central and Eastern UP. Infrastructure projects (Ganga Expressway, airport expansion) require steady grey cement and ready-mix concrete dispatches that sustain regional plant utilization at 86%.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024', 'note', 'Acquired Kesoram cement assets, expanding North-Central India regional footprint.'),
        JSON_OBJECT('period', '2025-2026', 'note', 'Commissioned 3.2 MTPA brownfield expansion in Uttar Pradesh.')
    ),
    JSON_OBJECT('capacity_utilization', '84%', 'ebitda_per_tonne', '₹1,240', 'market_share_up', '24.5%', 'geo_risk_beta', '0.48'),
    '96% Domestic India, highly levered to North & Central public infrastructure spending.',
    2
FROM macro_reports r WHERE r.headline LIKE '%UP 2027%' LIMIT 1;

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order)
SELECT r.id, 'Dabur India Ltd', 'DABUR.NS', 'India', 'watch',
    'Fast-Moving Consumer Goods (FMCG)', 'NSE', '₹980B ($12B)',
    'One of India\'s largest consumer goods companies with deep 140-year heritage in Ayurvedic health products, personal care, and packaged fruit juices.',
    'Currently trading at moderate valuation multiples due to subdued rural wage growth. If election year populist subsidies (Scenario Beta) inject direct cash into UP\'s 180M rural population, Dabur offers the highest operating leverage to rural volume recovery.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Expanded direct village coverage to 110,000 villages across UP and Bihar.'),
        JSON_OBJECT('period', '2026', 'note', 'Launched rural-specific low-unit-price value packs across health supplements.')
    ),
    JSON_OBJECT('rural_sales_share', '48%', 'gross_margin', '47.5%', 'dividend_payout', '52%', 'geo_risk_beta', '0.22'),
    '82% Domestic India (with UP representing Dabur\'s single largest state consumer market).',
    3
FROM macro_reports r WHERE r.headline LIKE '%UP 2027%' LIMIT 1;

-- ============================================================
-- EVENT 4: Binance Hot-Wallet Breach & Offshore Crypto Shock
-- ============================================================
INSERT INTO macro_events (title, category, timing, severity, event_date, countries_json, summary, resources_affected_json, sources_json) VALUES
(
    'Binance Hot-Wallet Security Breach & Regulatory Enforcement Action',
    'market_shock',
    'reactive',
    'critical',
    '2026-10-18',
    JSON_ARRAY('Global', 'United States', 'Seychelles', 'Singapore'),
    'A sophisticated zero-day smart-contract private-key exploit compromised primary cross-chain hot wallets associated with the Binance bridge infrastructure, leading to a verified $840M digital asset drain. Global financial regulators, including US FinCEN and European ESMA, initiated emergency capital adequacy audits, accelerating institutional flight into onshore, regulated custody custodians.',
    JSON_ARRAY('Bitcoin (BTC)', 'Ethereum (ETH)', 'USD Coin (USDC)', 'Computational ASIC Hashrate'),
    JSON_ARRAY(
        JSON_OBJECT('title', 'Binance hot-wallet drained of $840M', 'url', 'https://example.com/binance-breach', 'publisher', 'Bloomberg Crypto', 'published_at', '2026-10-18')
    )
);
SET @binance_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note, published_at) VALUES
(
    @binance_event_id,
    'Binance exploit triggers $4.2B offshore exchange exodus: institutional capital accelerates flight to regulated US custody',
    'published',
    'risk_off',
    'Acute liquidity fragmentation across crypto spot and derivatives markets. Offshore algorithmic market makers withdrew liquidity by 34%, triggering stablecoin de-peg volatility while regulated US institutional custodians experienced record net asset inflows.',
    'Capital is rotating aggressively from offshore, unregulated exchange platforms into publicly audited, SEC-regulated Qualified Custodians (Coinbase Prime, Fidelity Digital Assets). Avoid offshore algorithmic yield tokens; premium valuations accrue to regulated exchange infrastructure with direct Federal Reserve master account access.',
    NOW()
);
SET @binance_report_id = LAST_INSERT_ID();

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order) VALUES
(@binance_report_id, 'Regulated Digital Asset Custody & Prime Brokerage', 'gainer', 5, 'Sovereign wealth funds and spot ETF issuers mandate 100% cold-storage custody under US/EU regulated banking charters.', 1),
(@binance_report_id, 'Cold-Storage Hardware Security Vendors', 'gainer', 4, 'Enterprise and retail flight from exchange counterparty risk drove record sellout of hardware security devices.', 2),
(@binance_report_id, 'Offshore Derivative & Altcoin Exchanges', 'loser', 5, 'Facing catastrophic proof-of-reserves liquidity crunches, regulatory freezes, and mass depositor redemptions.', 3);

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order) VALUES
(
    @binance_report_id, 'Coinbase Global Inc', 'COIN', 'United States', 'gainer',
    'FinTech & Digital Asset Infrastructure', 'NASDAQ', '$62B',
    'Leading publicly traded US digital asset exchange and primary Qualified Custodian supporting 85% of all approved US Spot Bitcoin and Ethereum ETFs.',
    'Coinbase is the primary institutional beneficiary of offshore trust erosion. Institutional client onboarding surged 140% month-over-month as corporate treasuries and ETF sponsors consolidate custody away from offshore exchanges.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Monopolized custody for BlackRock, Fidelity, and Franklin Templeton spot crypto funds.'),
        JSON_OBJECT('period', '2026', 'note', 'Integrated Base L2 network with corporate cross-border treasury settlements.')
    ),
    JSON_OBJECT('assets_on_platform', '$270B', 'custodial_revenue_growth', '+88%', 'cash_reserves', '$7.4B', 'geo_risk_beta', '2.15'),
    '84% United States, 16% International Regulated Jurisdictions.',
    1
);

-- ============================================================
-- EVENT 5: Google / Alphabet DOJ Antitrust Monopoly Ruling
-- ============================================================
INSERT INTO macro_events (title, category, timing, severity, event_date, countries_json, summary, resources_affected_json, sources_json) VALUES
(
    'Alphabet / Google DOJ Antitrust Ruling & AdTech Unbundling',
    'regulation_law',
    'reactive',
    'high',
    '2026-10-12',
    JSON_ARRAY('United States', 'European Union'),
    'A landmark US Federal District Court antitrust remedies ruling mandated the progressive unbundling of Alphabet\'s Google Ad Manager, publisher ad server, and AdX exchange auction tech stack, while prohibiting exclusionary default search agreements with Apple, Samsung, and browser OEMs. The decision initiates the largest structural deregulation of digital ad monetization in 25 years.',
    JSON_ARRAY('Digital Advertising Inventory', 'Search Query Traffic', 'AI Inference Compute'),
    JSON_ARRAY(
        JSON_OBJECT('title', 'Court orders structural remedy for Google AdTech', 'url', 'https://example.com/doj-google-remedies', 'publisher', 'Wall Street Journal', 'published_at', '2026-10-12')
    )
);
SET @google_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note, published_at) VALUES
(
    @google_event_id,
    'DOJ antitrust remedies force Google AdTech unbundling: independent DSPs and alternative search engines capture open web auction share',
    'published',
    'sector_rotation',
    'Historic shift in the $600B digital advertising ecosystem. Elimination of Google\'s self-preferencing AdX exchange mechanisms immediately redirects billions in programmatic media budgets to independent open-web platforms.',
    'Rotational capital is flowing directly into independent ad platforms (The Trade Desk) and AI search alternatives (Microsoft, Perplexity-allied publishers). While Alphabet\'s cloud and YouTube businesses provide cash buffers, core search gross margin faces permanent 300-500 bps structural dilution.',
    NOW()
);
SET @google_report_id = LAST_INSERT_ID();

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order) VALUES
(@google_report_id, 'Independent Programmatic AdTech Platforms', 'gainer', 5, 'Court remedies prevent Google from bundling its publisher ad server with its ad exchange, unlocking access for open DSP bidding.', 1),
(@google_report_id, 'Alternative AI Search Engines', 'gainer', 4, 'Banning exclusive default search placement contracts with Apple unlocks Safari distribution channels for competitors.', 2),
(@google_report_id, 'Monopoly Digital Ad Networks', 'loser', 4, 'Take-rate compression on ad-exchange auctions and loss of guaranteed multi-device search traffic distribution pipelines.', 3);

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order) VALUES
(
    @google_event_id, 'The Trade Desk Inc', 'TTD', 'United States', 'gainer',
    'Digital Advertising & Programmatic DSP', 'NASDAQ', '$58B',
    'The premier independent demand-side platform (DSP) providing buyers of advertising inventory transparent, objective auction tools without owning publisher inventory or walled-garden media.',
    'As Google is legally forced to dismantle its walled-garden bidding advantages, enterprise brand advertisers and agencies are migrating premium video, connected TV (CTV), and open web spend directly into The Trade Desk.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Expanded Unified ID 2.0 (UID2) adoption across major streaming television publishers.'),
        JSON_OBJECT('period', '2026', 'note', 'Launched Kokai AI programmatic pricing engine, expanding enterprise client retention to 95%.')
    ),
    JSON_OBJECT('revenue_growth', '26.8%', 'ebitda_margin', '40.2%', 'free_cash_flow', '$620M', 'geo_risk_beta', '0.78'),
    '87% North America, 13% International Expansion.',
    1
),
(
    @google_event_id, 'Alphabet Inc', 'GOOGL', 'United States', 'loser',
    'Interactive Media & Technology Conglomerate', 'NASDAQ', '$2.05T',
    'Global technology powerhouse dominating internet search, online video (YouTube), cloud computing, autonomous vehicles (Waymo), and mobile operating systems (Android).',
    'Facing unprecedented structural legal remedies. Banning Google\'s $20B annual payment to Apple removes guaranteed default Safari search distribution, while forced AdTech divestitures compress Google Network ad margins.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024', 'note', 'US District Court found Google liable for illegal monopolization in search distribution.'),
        JSON_OBJECT('period', '2026', 'note', 'DOJ requested structural separation of Google Ad Manager and Chrome/Android unbundling.')
    ),
    JSON_OBJECT('operating_margin', '31.5%', 'search_revenue_share', '57%', 'pe_ratio', '20.5x', 'geo_risk_beta', '0.88'),
    '47% United States, 30% EMEA, 17% Asia-Pacific, 6% Latin America.',
    2
);

-- ============================================================
-- EVENT 6: France Political Unrest & EU Fiscal Deadlock
-- ============================================================
INSERT INTO macro_events (title, category, timing, severity, event_date, countries_json, summary, resources_affected_json, sources_json) VALUES
(
    'France Political Gridlock & European Union Fiscal Reform Crisis',
    'civil_unrest',
    'reactive',
    'high',
    '2026-10-08',
    JSON_ARRAY('France', 'Germany', 'European Union'),
    'Repeated votes of no confidence, widespread public strikes against budget austerity, and an irreconcilable hung parliament in Paris have paralyzed the French government\'s fiscal consolidation roadmap. The French 10-year sovereign bond (OAT) spread over German benchmark Bunds blew out to 88 bps, matching Greek crisis peaks and threatening euro-area fiscal convergence treaties.',
    JSON_ARRAY('European Sovereign Debt', 'Euro Currency (EUR)', 'Nuclear Power Baseload'),
    JSON_ARRAY(
        JSON_OBJECT('title', 'French OAT-Bund spread hits 12-year wide', 'url', 'https://example.com/french-spread-crisis', 'publisher', 'Financial Times', 'published_at', '2026-10-08')
    )
);
SET @france_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note, published_at) VALUES
(
    @france_event_id,
    'French hung parliament and sovereign spread blow-out re-ignites European sovereign debt fragmentation fears',
    'published',
    'risk_off',
    'Acute widening in French sovereign yields relative to German benchmark paper. French banking shares experienced heavy institutional selling pressure, while safe-haven defensive utilities and German federal debt instruments absorbed capital flows.',
    'Capital is exiting French sovereign and bank paper, fleeing into German federal Bunds and Swiss Franc assets. Monitor rating agency reviews (S&P, Moody\'s) on French sovereign debt over the upcoming 60-day review cycle -- a further downgrade to single-A tier would trigger forced institutional divestment.',
    NOW()
);
SET @france_report_id = LAST_INSERT_ID();

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order) VALUES
(@france_report_id, 'German Sovereign Debt & Defensive Regulated Utilities', 'gainer', 4, 'German Bunds act as the premier euro safe haven; domestic utilities provide inflation-indexed, sovereign-risk insulated dividends.', 1),
(@france_report_id, 'French Commercial & Investment Banking', 'loser', 5, 'Heavy domestic sovereign bond holdings inflict mark-to-market balance sheet losses and raise wholesale bank funding costs.', 2);

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order) VALUES
(
    @france_report_id, 'BNP Paribas SA', 'BNP.PA', 'France', 'loser',
    'Banking & Financial Services', 'Euronext Paris', '$74B',
    'Largest banking group in the Eurozone by total assets, operating dominant retail and corporate banking franchises across France, Italy, and Belgium.',
    'OAT spread widening increases wholesale bond funding costs by 45 bps and triggers mark-to-market declines across its domestic sovereign debt investment portfolio.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2025', 'note', 'Expanded corporate banking market share following Credit Suisse dissolution.'),
        JSON_OBJECT('period', '2026', 'note', 'Raised provisions for domestic corporate insolvency risk amid French budget austerity.')
    ),
    JSON_OBJECT('cet1_ratio', '13.2%', 'return_on_equity', '9.8%', 'price_to_book', '0.62x', 'geo_risk_beta', '-1.35'),
    '28% France domestic, 52% Rest of Europe, 20% Americas & Asia.',
    1
);

-- ============================================================
-- EVENT 7: Critical Minerals Realignment & Lithium Supply Security
-- ============================================================
INSERT INTO macro_events (title, category, timing, severity, event_date, countries_json, summary, resources_affected_json, sources_json) VALUES
(
    'Global Critical Minerals Realignment & Lithium Supply Security',
    'resource_discovery',
    'reactive',
    'medium',
    '2026-09-28',
    JSON_ARRAY('United States', 'Chile', 'Australia', 'China'),
    'New discoveries of high-grade lithium brine basins in North America and finalized bilateral FTA mineral compliance pacts between the US and Latin American partners have triggered a realignment of EV battery supply chains, isolating non-compliant Chinese processing cartels and unlocking billions in Western domestic extraction subsidies.',
    JSON_ARRAY('Lithium Carbonate & Hydroxide', 'Spodumene Ore', 'Rare Earth Permanent Magnets', 'Battery-Grade Nickel'),
    JSON_ARRAY(
        JSON_OBJECT('title', 'Western critical minerals alliance solidifies', 'url', 'https://example.com/critical-minerals-accord', 'publisher', 'Reuters Commodities', 'published_at', '2026-09-28')
    )
);
SET @lithium_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note, published_at) VALUES
(
    @lithium_event_id,
    'Western critical minerals pact re-routes battery supply chains toward IRA-compliant lithium producers',
    'published',
    'sector_rotation',
    'Structural bifurcation in battery raw material pricing: IRA-compliant Western and FTA-origin lithium hydroxide commands a 22% price premium over Chinese domestic material as automakers race to preserve EV consumer tax credit eligibility.',
    'Capital is rotating out of unhedged Chinese lepidolite refiners into low-cost brine operators in North America and Chile with certified FTA compliance certificates. Watch upcoming US Treasury guidance on Foreign Entities of Concern (FEOC) threshold enforcement.',
    NOW()
);
SET @lithium_report_id = LAST_INSERT_ID();

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order) VALUES
(@lithium_report_id, 'IRA-Compliant Lithium Brine Extractors', 'gainer', 5, 'Secures long-term guaranteed offtake contracts with Western EV automakers seeking domestic supply tax incentives.', 1),
(@lithium_report_id, 'Non-FTA Unrefined Mineral Processors', 'loser', 4, 'Facing strict exclusion from subsidized US and European automotive battery assembly chains.', 2);

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, sector, exchange, market_cap, overview, rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sort_order) VALUES
(
    @lithium_report_id, 'Albemarle Corporation', 'ALB', 'United States', 'gainer',
    'Specialty Chemicals & Lithium Refining', 'NYSE', '$12B',
    'Global leader in transforming essential resources into critical ingredients for mobility, energy, connectivity, and health, operating world-class lithium assets in Nevada, Chile, and Australia.',
    'Direct beneficiary of US Title III Defense Production Act grants and guaranteed automaker offtake pricing. Albemarle\'s Kings Mountain conversion plant qualifies for full IRA domestic sourcing incentives.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2025', 'note', 'Restructured capital spending to lower cost-per-tonne curve below $11,000/tonne.'),
        JSON_OBJECT('period', '2026', 'note', 'Signed 5-year direct lithium hydroxide delivery contracts with Ford and BMW.')
    ),
    JSON_OBJECT('gross_margin', '24.2%', 'net_debt_ebitda', '2.8x', 'cost_curve_percentile', 'First Quartile (Brine)', 'geo_risk_beta', '1.10'),
    '44% North America, 32% Asia-Pacific, 24% Europe.',
    1
);

SET FOREIGN_KEY_CHECKS = 1;
