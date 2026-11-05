-- Sample macro events + reports to demonstrate the core product: global/
-- national events -> who gains, who loses, by industry and by named
-- company -> market direction read. Run once against a fresh database,
-- after schema.sql and seed.sql.
--
-- Three examples, deliberately covering both modes the product supports:
--   1. Venezuela / Maduro        -> reactive, status=published
--   2. Russia-Ukraine peace talks -> reactive, status=in_review (shows the
--      review queue with something actually waiting in it)
--   3. UP 2027 election          -> predictive, status=ai_drafted (no
--      companies named yet -- this is the AI-draft stage, nothing to
--      review until an analyst adds company-level detail)

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. Venezuela: Maduro detained
-- ============================================================
INSERT INTO macro_events (title, category, timing, event_date, countries_json, summary, sources_json) VALUES
(
    'Nicolas Maduro detained following US operation',
    'leadership_change',
    'reactive',
    '2026-10-15',
    JSON_ARRAY('Venezuela', 'United States'),
    'Venezuelan president Nicolas Maduro was detained following a US-led operation, ending over a decade in power. Interim governance arrangements are forming under US and regional backing, with immediate implications for Venezuela''s oil sector, sanctions regime, and the companies that had been operating under or around the prior sanctions framework.',
    JSON_ARRAY(
        JSON_OBJECT('title', 'Maduro detained, interim government forms', 'url', 'https://example.com/venezuela-maduro-detained', 'publisher', 'Reuters', 'published_at', '2026-10-15')
    )
);
SET @venezuela_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note, reviewed_by, reviewed_at, review_notes, published_at) VALUES
(
    @venezuela_event_id,
    'Maduro''s removal reopens Venezuela''s oil sector to Western majors',
    'published',
    'sector_rotation',
    'This is a sector-specific rotation, not a broad risk-on move. Energy majors with prior Venezuela exposure are being repriced upward on the prospect of sanctions relief and renewed PDVSA joint-venture activity, while Russian and Chinese state-linked entities that filled the vacuum under sanctions face the opposite move.',
    'Money is flowing toward Western integrated oil majors with pre-existing Venezuela infrastructure (fastest to re-mobilize) and away from the Russian/Chinese entities that benefited from the prior sanctions regime. Watch OFAC licensing announcements over the next 4-6 weeks -- the speed of sanctions relief, not the detention itself, is what will actually move capital.',
    1, NOW(), 'Checked sourcing on all three company claims, numbers are consistent with public reporting. Approved.', NOW()
);
SET @venezuela_report_id = LAST_INSERT_ID();

INSERT INTO report_industry_impacts (report_id, industry, direction, severity, rationale, sort_order) VALUES
(@venezuela_report_id, 'Oil & Gas Exploration', 'gainer', 5, 'Direct beneficiary of any sanctions relief -- Venezuela holds the world''s largest proven crude reserves, largely stranded under the prior regime.', 1),
(@venezuela_report_id, 'Oilfield Services', 'gainer', 3, 'Renewed drilling and infrastructure rehabilitation work follows any major re-entry by international operators.', 2),
(@venezuela_report_id, 'Shipping & Tankers', 'mixed', 2, 'Legitimate crude flows should normalize and grow, but the shadow-fleet operators that profited from sanctions-evasion routes lose that premium trade.', 3),
(@venezuela_report_id, 'Defense & Private Security', 'watch', 2, 'Transition security and infrastructure-protection contracts are plausible but not yet confirmed.', 4);

INSERT INTO report_company_impacts (report_id, company_name, ticker, country, direction, rationale, recent_activity_json, sources_json, sort_order) VALUES
(
    @venezuela_report_id, 'Chevron Corporation', 'CVX', 'United States', 'gainer',
    'Chevron held the broadest surviving US license to operate in Venezuela''s oil sector through the sanctions period and is best positioned to scale output quickly if licensing expands further.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2025 Q4', 'note', 'Operated under a limited OFAC license, producing under output caps.', 'source_url', 'https://example.com/chevron-venezuela-license'),
        JSON_OBJECT('period', '2026 Q3', 'note', 'Signaled readiness to expand investment pending license terms.', 'source_url', 'https://example.com/chevron-q3-earnings-call')
    ),
    JSON_ARRAY(JSON_OBJECT('title', 'Chevron Venezuela operations overview', 'url', 'https://example.com/chevron-venezuela', 'publisher', 'Company filings')),
    1
),
(
    @venezuela_report_id, 'Petroleos de Venezuela SA (PDVSA)', NULL, 'Venezuela', 'mixed',
    'As the state oil company, PDVSA gains from renewed foreign investment and technical capacity, but any interim government restructuring or debt-restructuring process creates near-term uncertainty over management and asset control.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2026 Q3', 'note', 'Output remained well below 2010s peak capacity due to years of underinvestment.', 'source_url', 'https://example.com/pdvsa-output-2026')
    ),
    JSON_ARRAY(JSON_OBJECT('title', 'PDVSA production data', 'url', 'https://example.com/pdvsa-data', 'publisher', 'OPEC secondary sources')),
    2
),
(
    @venezuela_report_id, 'Rosneft Oil Company', 'ROSN', 'Russia', 'loser',
    'Rosneft expanded its Venezuela footprint specifically because Western majors were sanctions-constrained; a Western re-entry directly displaces the preferential access and discounted-crude arrangements it built under the prior regime.',
    JSON_ARRAY(
        JSON_OBJECT('period', '2024-2026', 'note', 'Increased crude-for-debt and joint-venture activity with PDVSA while Western competitors were restricted.', 'source_url', 'https://example.com/rosneft-venezuela')
    ),
    JSON_ARRAY(JSON_OBJECT('title', 'Rosneft Venezuela footprint', 'url', 'https://example.com/rosneft-footprint', 'publisher', 'Industry analysis')),
    3
);

-- ============================================================
-- 2. Russia-Ukraine peace deal talks -- sitting in the review queue
-- ============================================================
INSERT INTO macro_events (title, category, timing, event_date, countries_json, summary, sources_json) VALUES
(
    'US-brokered Russia-Ukraine peace framework talks advance',
    'sanctions_peace_deal',
    'reactive',
    '2026-10-29',
    JSON_ARRAY('Russia', 'Ukraine', 'United States', 'European Union'),
    'US-brokered talks toward a Russia-Ukraine peace framework have advanced to a stage where sanctions-relief and reconstruction financing terms are being actively discussed, though no signed agreement exists yet.',
    JSON_ARRAY(
        JSON_OBJECT('title', 'Peace framework talks advance', 'url', 'https://example.com/russia-ukraine-talks', 'publisher', 'AP', 'published_at', '2026-10-29')
    )
);
SET @ru_ua_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note) VALUES
(
    @ru_ua_event_id,
    'A credible peace framework would reprice European defense, energy, and reconstruction plays',
    'in_review',
    'risk_on',
    'DRAFT -- pending review. If a framework is actually signed, this reads as a broad European risk-on event: European equities and the euro firm, European natural gas prices ease further as a risk premium unwinds, and a reconstruction-exposed basket (construction materials, engineering, rail/infrastructure) gets a direct re-rating. Defense names see a near-term pullback on reduced immediate procurement urgency, offset partly by longer-term European rearmament commitments that predate this deal.',
    'DRAFT -- pending review. Early positioning is in reconstruction and European industrials ahead of confirmation; a confirmed signature would likely accelerate the move. Needs a human check on timing assumptions before this goes out -- framework talks advancing is not the same as a signed deal, and the report should be explicit that this is a probability-weighted read, not a done deal.'
);

-- No company impacts yet for this one -- deliberately left for the
-- reviewing analyst to add/confirm, since this is the state a report is
-- actually in while sitting in the queue (market read exists, company-level
-- detail is still being finalized).

-- ============================================================
-- 3. UP 2027 election -- predictive, still ai_drafted (earliest stage)
-- ============================================================
INSERT INTO macro_events (title, category, timing, event_date, countries_json, summary, sources_json) VALUES
(
    'Uttar Pradesh 2027 state election',
    'election',
    'predictive',
    '2027-02-01',
    JSON_ARRAY('India'),
    'Uttar Pradesh, India''s most populous state and a major electoral and industrial base, goes to the polls in early 2027. Outcome has historically correlated with shifts in state-level infrastructure spending, land/industrial policy, and law-and-order perception that affects investment decisions in the state.',
    JSON_ARRAY(
        JSON_OBJECT('title', 'UP assembly election scheduling', 'url', 'https://example.com/up-election-2027', 'publisher', 'Election Commission of India', 'published_at', '2026-09-01')
    )
);
SET @up_event_id = LAST_INSERT_ID();

INSERT INTO macro_reports (event_id, headline, status, market_stance, market_summary, market_flow_note) VALUES
(
    @up_event_id,
    'UP 2027: early read on industry exposure ahead of the election cycle',
    'ai_drafted',
    'neutral_uncertain',
    'AI DRAFT -- not yet reviewed. Too early for a confident market-direction call fifteen months out. Historically, continuity outcomes favor already-committed state infrastructure and industrial-corridor projects (UP''s expressway and industrial-corridor buildout), while a change in government has in the past triggered review/renegotiation of some state-level contracts and land allocations. This needs a named-company pass and an analyst sanity check before it is review-ready.',
    'AI DRAFT -- not yet reviewed. No actionable money-flow read yet at this distance from the vote; flagging the state infrastructure, cement, and real-estate-development names with the largest UP-specific exposure as the ones to build out first when this report is revisited closer to the election.'
);

-- No industry or company impacts yet -- this is intentionally the
-- earliest-stage example (AI drafted, nothing added by a human yet),
-- showing the review queue has work genuinely still to do on it.

SET FOREIGN_KEY_CHECKS = 1;
