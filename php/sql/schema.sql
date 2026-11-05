-- GeoTrade Terminal — plain MySQL schema (no migration framework).
-- Import via phpMyAdmin or `mysql -u USER -p DBNAME < schema.sql` on Hostinger.
-- InnoDB throughout for FK + transaction support. utf8mb4 throughout.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- STAGE 1/3: Duty / Gazette engine
-- ============================================================

CREATE TABLE IF NOT EXISTS admin_users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)    NOT NULL,
    email           VARCHAR(190)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at   DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)    NOT NULL,
    email           VARCHAR(190)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hs_codes (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(12)     NOT NULL UNIQUE,
    code_normalized  VARCHAR(10)     NOT NULL,
    chapter          TINYINT UNSIGNED NOT NULL,
    description      VARCHAR(255)    NOT NULL,
    is_active        TINYINT(1)      NOT NULL DEFAULT 1,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hs_chapter_active (chapter, is_active),
    INDEX idx_hs_code_normalized (code_normalized)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gazette_notifications (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    authority          VARCHAR(20)     NOT NULL COMMENT 'CBIC, DGFT, DGCI&S',
    reference_no       VARCHAR(60)     NOT NULL,
    title              VARCHAR(255)    NOT NULL,
    notification_type  ENUM('bcd_change','cess_change','anti_dumping','incentive_scheme','other') NOT NULL,
    gazette_date       DATE            NOT NULL,
    effective_date     DATE            NOT NULL,
    source_pdf_path    VARCHAR(255)    NULL,
    raw_extraction     JSON            NULL,
    parse_status       ENUM('pending','parsed','failed') NOT NULL DEFAULT 'pending',
    parsed_at          DATETIME        NULL,
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_authority_reference (authority, reference_no),
    INDEX idx_gazette_effective_date (effective_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS duty_rates (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hs_code_id                INT UNSIGNED NOT NULL,
    gazette_notification_id   INT UNSIGNED NULL,
    bcd_rate                  DECIMAL(6,3) NOT NULL,
    sws_rate                  DECIMAL(6,3) NOT NULL DEFAULT 10.000,
    igst_rate                 DECIMAL(6,3) NOT NULL,
    aidc_rate                 DECIMAL(6,3) NOT NULL DEFAULT 0.000,
    status                    ENUM('active','under_review','superseded') NOT NULL DEFAULT 'active',
    effective_from            DATE NOT NULL,
    effective_to              DATE NULL,
    created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_duty_hs_code FOREIGN KEY (hs_code_id) REFERENCES hs_codes(id) ON DELETE CASCADE,
    CONSTRAINT fk_duty_notification FOREIGN KEY (gazette_notification_id) REFERENCES gazette_notifications(id) ON DELETE SET NULL,
    INDEX idx_duty_hs_window (hs_code_id, effective_from, effective_to)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quarantined_tariff_revisions (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gazette_notification_id   INT UNSIGNED NOT NULL,
    hs_code                   VARCHAR(12) NOT NULL,
    duty_type                 VARCHAR(10) NOT NULL,
    old_rate                  DECIMAL(6,3) NULL,
    new_rate                  DECIMAL(6,3) NOT NULL,
    conditions                VARCHAR(500) NULL,
    status                    ENUM('pending_review','approved','rejected') NOT NULL DEFAULT 'pending_review',
    reason                    TEXT NOT NULL,
    reviewed_by                INT UNSIGNED NULL,
    reviewed_at                DATETIME NULL,
    created_at                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quarantine_notification FOREIGN KEY (gazette_notification_id) REFERENCES gazette_notifications(id) ON DELETE CASCADE,
    CONSTRAINT fk_quarantine_reviewer FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_quarantine_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_watchlists (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                   INT UNSIGNED NOT NULL,
    hs_code_id                INT UNSIGNED NOT NULL,
    notify_on_increase_only   TINYINT(1) NOT NULL DEFAULT 1,
    created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_watchlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_hs_code FOREIGN KEY (hs_code_id) REFERENCES hs_codes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_hs_code (user_id, hs_code_id)
) ENGINE=InnoDB;

-- Pushed to users on page load / AJAX poll instead of a websocket event.
-- "delivered" lets the polling endpoint return only what's new since last check.
CREATE TABLE IF NOT EXISTS tariff_alerts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    hs_code_id      INT UNSIGNED NOT NULL,
    duty_rate_id    INT UNSIGNED NOT NULL,
    duty_type       VARCHAR(10) NOT NULL,
    old_rate        DECIMAL(6,3) NULL,
    new_rate        DECIMAL(6,3) NOT NULL,
    delta_pts       DECIMAL(6,3) NOT NULL,
    is_increase     TINYINT(1) NOT NULL,
    status          ENUM('unread','read','dismissed') NOT NULL DEFAULT 'unread',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_hs_code FOREIGN KEY (hs_code_id) REFERENCES hs_codes(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_duty_rate FOREIGN KEY (duty_rate_id) REFERENCES duty_rates(id) ON DELETE CASCADE,
    INDEX idx_alert_user_status (user_id, status)
) ENGINE=InnoDB;

-- Polled by the ticker page (ajax/ticker.php?since_id=N) instead of a
-- websocket broadcast — one row per notification, batched across all its
-- HS codes, exactly like the Laravel TariffNotificationBroadcastEvent did.
CREATE TABLE IF NOT EXISTS ticker_events (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gazette_notification_id INT UNSIGNED NOT NULL,
    hs_codes_json      JSON NOT NULL COMMENT 'array of affected HS code strings',
    max_delta_pts       DECIMAL(6,3) NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticker_notification FOREIGN KEY (gazette_notification_id) REFERENCES gazette_notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- STAGE 5: Macro / geopolitical event & report engine
-- ============================================================

CREATE TABLE IF NOT EXISTS macro_events (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    category        ENUM('war_conflict','sanctions_peace_deal','election','leadership_change',
                         'regulation_law','civil_unrest','market_shock','resource_discovery','trade_policy') NOT NULL,
    timing          ENUM('reactive','predictive') NOT NULL,
    event_date      DATE NULL COMMENT 'NULL for reactive events that already happened without a single clean date',
    countries_json  JSON NOT NULL COMMENT 'array of country names/codes involved',
    summary         TEXT NOT NULL,
    sources_json    JSON NULL COMMENT 'array of {title,url,publisher,published_at}',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_category (category),
    INDEX idx_event_timing (timing)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS macro_reports (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id          INT UNSIGNED NOT NULL,
    headline          VARCHAR(255) NOT NULL,
    status            ENUM('ai_drafted','in_review','changes_requested','approved','published','rejected') NOT NULL DEFAULT 'ai_drafted',
    market_stance     ENUM('risk_on','risk_off','sector_rotation','neutral_uncertain') NOT NULL,
    market_summary    TEXT NOT NULL,
    market_flow_note  TEXT NOT NULL,
    ai_drafted_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by       INT UNSIGNED NULL,
    reviewed_at       DATETIME NULL,
    review_notes      TEXT NULL,
    published_at      DATETIME NULL,
    CONSTRAINT fk_report_event FOREIGN KEY (event_id) REFERENCES macro_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_reviewer FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_report_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_industry_impacts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id       INT UNSIGNED NOT NULL,
    industry        VARCHAR(150) NOT NULL,
    direction       ENUM('gainer','loser','mixed','watch') NOT NULL,
    severity        TINYINT UNSIGNED NOT NULL COMMENT '1-5',
    rationale       TEXT NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_industry_report FOREIGN KEY (report_id) REFERENCES macro_reports(id) ON DELETE CASCADE,
    CONSTRAINT chk_severity CHECK (severity BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_company_impacts (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id         INT UNSIGNED NOT NULL,
    company_name      VARCHAR(150) NOT NULL,
    ticker            VARCHAR(20) NULL,
    country           VARCHAR(100) NOT NULL,
    direction         ENUM('gainer','loser','mixed','watch') NOT NULL,
    rationale         TEXT NOT NULL,
    recent_activity_json JSON NULL COMMENT 'array of {period, note, source_url}',
    sources_json      JSON NULL COMMENT 'array of {title,url,publisher}',
    sort_order        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_company_report FOREIGN KEY (report_id) REFERENCES macro_reports(id) ON DELETE CASCADE,
    INDEX idx_company_name (company_name)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
