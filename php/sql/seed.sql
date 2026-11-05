-- Minimal seed data to get Stage 1 testable end to end.
-- Change the admin password immediately after import — this hash is for
-- the literal string "ChangeMe123!" and must never reach production as-is.

INSERT INTO admin_users (name, email, password_hash, is_active) VALUES
('Admin', 'admin@geotrade.local', '$2y$10$NzqN8X0v3qf5Qd1z8y6p7eBq0q9b1n1a8U3h2kYm7cQxGx8oQJr9e', 1)
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO hs_codes (code, code_normalized, chapter, description, is_active) VALUES
('2933.99', '293399', 29, 'Organic compounds (APIs) n.e.s.', 1),
('2917.11', '291711', 29, 'Oxalic acid and its salts and esters', 1),
('8504.40', '850440', 85, 'Static converters', 1),
('8542.31', '854231', 85, 'Processors and controllers (electronic integrated circuits)', 1)
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO gazette_notifications (authority, reference_no, title, notification_type, gazette_date, effective_date, parse_status, parsed_at) VALUES
('CBIC', '42/2026-Cus', 'Seeks to increase BCD on certain organic chemicals', 'bcd_change', '2026-10-24', '2026-10-28', 'parsed', NOW())
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- NOTE: duty_rates has no unique constraint to dedupe against (by design —
-- effective-dated history is meant to accumulate rows), so the two INSERTs
-- below are NOT idempotent. Run this whole seed file exactly once against a
-- fresh database, not repeatedly.
INSERT INTO duty_rates (hs_code_id, gazette_notification_id, bcd_rate, sws_rate, igst_rate, aidc_rate, status, effective_from, effective_to)
SELECT id, NULL, 12.500, 10.000, 18.000, 0.000, 'superseded', '2025-01-01', '2026-10-28' FROM hs_codes WHERE code = '2933.99';

INSERT INTO duty_rates (hs_code_id, gazette_notification_id, bcd_rate, sws_rate, igst_rate, aidc_rate, status, effective_from, effective_to)
SELECT hc.id, gn.id, 17.500, 10.000, 18.000, 0.000, 'active', '2026-10-28', NULL
FROM hs_codes hc, gazette_notifications gn
WHERE hc.code = '2933.99' AND gn.reference_no = '42/2026-Cus';
