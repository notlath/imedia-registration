-- =============================================================================
-- IMedia Registration — Database Schema
-- Target: MySQL 5.7+ / MariaDB 10.3+
-- Engine: InnoDB (transactions, FKs, row-level locking)
-- Charset: utf8mb4 / utf8mb4_unicode_ci (full Unicode incl. emoji)
--
-- Install: import this file in cPanel > phpMyAdmin > Import.
-- The client creates a fresh database (e.g. <cpaneluser>_imreg) first.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1. Admins — application users (separate from WordPress wp_users)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(255)    NOT NULL,
  `email`        VARCHAR(255)    NOT NULL,
  `password`     VARCHAR(255)    NOT NULL COMMENT 'bcrypt',
  `role`         ENUM('admin','super') NOT NULL DEFAULT 'admin',
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_until` DATETIME        DEFAULT NULL COMMENT 'login throttling; NULL = not locked',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 1b. Login attempts — rolling-window throttle log
-- =============================================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARBINARY(16)   NOT NULL,                     -- inet_pton() / inet_ntop()
  `email`      VARCHAR(190)    NOT NULL,
  `success`    TINYINT(1)      NOT NULL,                     -- 1 = success (clears prior failures), 0 = failure
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip_time`   (`ip`, `created_at`),
  KEY `idx_login_attempts_email_time` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. Registrations — the core inquiry table
--    Indexes:
--      idx_course_start — composite for the threshold (course, year, month) query
--      idx_status       — status filter on list views
--      idx_payment      — payment_status filter
--      idx_email        — search by email
--      idx_deleted      — alumni filter
-- =============================================================================
CREATE TABLE IF NOT EXISTS `registrations` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(255)    NOT NULL,
  `mobile`         VARCHAR(100)    DEFAULT NULL,
  `email`          VARCHAR(255)    NOT NULL,
  `address`        TEXT            DEFAULT NULL,
  `course`         VARCHAR(255)    NOT NULL,
  `start_date`     DATE            NOT NULL,
  `end_date`       DATE            NOT NULL,
  `status`         ENUM('pending','tentative','confirm','forfeit','reschedule')
                                  NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('pending','deposit','fully_paid')
                                  NOT NULL DEFAULT 'pending',
  `paid_amount`    DECIMAL(10,2)   DEFAULT NULL COMMENT 'required when payment_status != pending',
  `paid_at`        DATE            DEFAULT NULL COMMENT 'required when payment_status != pending',
  `remark`         TEXT            DEFAULT NULL COMMENT 'required when payment_status != pending',
  `dynamic_data`   JSON            DEFAULT NULL,
  `resume_path`    VARCHAR(512)    DEFAULT NULL COMMENT 'relative to public/; set by FileStorage',
  `deleted_at`     DATETIME        DEFAULT NULL COMMENT 'soft delete',
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course_start` (`course`, `start_date`),                      -- threshold query
  KEY `idx_status`       (`status`),
  KEY `idx_payment`      (`payment_status`),
  KEY `idx_email`        (`email`),
  KEY `idx_deleted`      (`deleted_at`),
  KEY `idx_created`      (`created_at`)                                -- 30-day series, list sort
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. Contacts — separate inquiry channel
-- =============================================================================
CREATE TABLE IF NOT EXISTS `contacts` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)    DEFAULT NULL,
  `mobile`     VARCHAR(100)    DEFAULT NULL,
  `email`      VARCHAR(255)    DEFAULT NULL,
  `subject`    VARCHAR(255)    DEFAULT NULL,
  `message`    TEXT            DEFAULT NULL,
  `status`     ENUM('pending','contacted','resolved') NOT NULL DEFAULT 'pending',
  `remarks`    TEXT            DEFAULT NULL,
  `deleted_at` DATETIME        DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`  (`status`),
  KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. Applications — OJT + Trainer (one table, typed)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `applications` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`            ENUM('ojt','trainer') NOT NULL,
  `name`            VARCHAR(255)    DEFAULT NULL,
  `mobile`          VARCHAR(100)    DEFAULT NULL,
  `email`           VARCHAR(255)    DEFAULT NULL,
  `position`        VARCHAR(255)    DEFAULT NULL,
  `message`         TEXT            DEFAULT NULL,
  `resume_path`     VARCHAR(500)    DEFAULT NULL,
  `resume_filename` VARCHAR(255)    DEFAULT NULL,
  `status`          ENUM('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
  `remarks`         TEXT            DEFAULT NULL,
  `deleted_at`      DATETIME        DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type`    (`type`),
  KEY `idx_status`  (`status`),
  KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. Custom endpoints — admin-defined dynamic schemas
-- =============================================================================
CREATE TABLE IF NOT EXISTS `custom_endpoints` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)    NOT NULL,
  `slug`       VARCHAR(100)    NOT NULL,
  `icon`       VARCHAR(100)    DEFAULT NULL,
  `fields`     JSON            DEFAULT NULL,
  `statuses`   JSON            DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_custom_endpoints_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. Custom submissions — rows for custom_endpoints (FK CASCADE)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `custom_submissions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `endpoint_id` BIGINT UNSIGNED NOT NULL,
  `data`        JSON            DEFAULT NULL,
  `status`      VARCHAR(50)     NOT NULL DEFAULT 'pending',
  `remarks`     TEXT            DEFAULT NULL,
  `deleted_at`  DATETIME        DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_submissions_endpoint` (`endpoint_id`),
  KEY `idx_submissions_deleted`  (`deleted_at`),
  CONSTRAINT `fk_submissions_endpoint`
    FOREIGN KEY (`endpoint_id`) REFERENCES `custom_endpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. Status history — every status / payment_status change is logged
-- =============================================================================
CREATE TABLE IF NOT EXISTS `status_history` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` ENUM('registration','contact','application','custom_submission') NOT NULL,
  `entity_id`   BIGINT UNSIGNED NOT NULL,
  `field`       ENUM('status','payment_status') NOT NULL,
  `old_value`   VARCHAR(50)     DEFAULT NULL,
  `new_value`   VARCHAR(50)     NOT NULL,
  `changed_by`  BIGINT UNSIGNED DEFAULT NULL COMMENT 'admin id; NULL for public WP submit',
  `changed_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note`        TEXT            DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_history_entity`    (`entity_type`, `entity_id`),
  KEY `idx_history_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. Form routes — maps WP form_id -> target table
--    Admin sets this in /admin/settings so the new app knows where to write
-- =============================================================================
CREATE TABLE IF NOT EXISTS `form_routes` (
  `form_id`     BIGINT UNSIGNED NOT NULL COMMENT 'the WP IMedia Forms CPT id',
  `target_type` ENUM('registration','contact','ojt','trainer','custom') NOT NULL,
  `target_slug` VARCHAR(100)    DEFAULT NULL COMMENT 'for target_type=custom, the custom_endpoints.slug',
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. Threshold alerts sent — one row per (course, year, month) we've alerted on
--    UNIQUE KEY blocks duplicate emails on the 10th, 11th, ... confirm
-- =============================================================================
CREATE TABLE IF NOT EXISTS `threshold_alerts_sent` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course`       VARCHAR(255)    NOT NULL,
  `course_year`  INT             NOT NULL,
  `course_month` INT             NOT NULL,
  `sent_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_threshold_slot` (`course`, `course_year`, `course_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 10. Settings — singleton row (id=1) holding all runtime config
-- =============================================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id`                            TINYINT         NOT NULL DEFAULT 1,
  `site_name`                     VARCHAR(255)    DEFAULT 'Inventive Media Registration',
  `alert_threshold`               INT UNSIGNED    NOT NULL DEFAULT 9,

  -- Email templates (registration confirmation)
  `email_template_subject`        VARCHAR(255)    DEFAULT NULL,
  `email_template_body`           MEDIUMTEXT      DEFAULT NULL,
  `email_template_enabled`        TINYINT(1)      NOT NULL DEFAULT 1,

  -- Admin notification (per new registration)
  `admin_notification_enabled`    TINYINT(1)      NOT NULL DEFAULT 0,
  `admin_notification_to`         VARCHAR(255)    DEFAULT NULL,
  `admin_notification_subject`    VARCHAR(255)    DEFAULT NULL,
  `admin_notification_body`       MEDIUMTEXT      DEFAULT NULL,

  -- Threshold alert (when (course, year, month) hits the configured threshold)
  `threshold_alert_enabled`       TINYINT(1)      NOT NULL DEFAULT 1,
  `threshold_alert_to`            VARCHAR(255)    DEFAULT NULL,
  `threshold_alert_subject`       VARCHAR(255)    DEFAULT 'Course Capacity Reached: {{course}} ({{monthName}} {{year}})',
  `threshold_alert_body`          MEDIUMTEXT      DEFAULT NULL,

  -- SMTP (used by PHPMailer)
  `smtp_host`                     VARCHAR(255)    DEFAULT NULL,
  `smtp_port`                     INT             NOT NULL DEFAULT 587,
  `smtp_user`                     VARCHAR(255)    DEFAULT NULL,
  `smtp_pass`                     VARCHAR(255)    DEFAULT NULL,
  `smtp_from_name`                VARCHAR(255)    DEFAULT NULL,
  `smtp_from_email`               VARCHAR(255)    DEFAULT NULL,
  `smtp_secure`                   TINYINT(1)      NOT NULL DEFAULT 0,

  -- HMAC shared secret — must match the WordPress plugin's `imf_shared_secret` option
  `hmac_shared_secret`            VARCHAR(255)    DEFAULT NULL,

  `updated_at`                    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 11. Outbox emails — Phase 3 threshold-alert queue.
--     Phase 5 wires PHPMailer and a cron to actually send these.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `outbox_emails` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email`   VARCHAR(255)    NOT NULL,
  `subject`    VARCHAR(255)    NOT NULL,
  `body_html`  MEDIUMTEXT      NOT NULL,
  `status`     ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `attempts`   INT UNSIGNED    NOT NULL DEFAULT 0,
  `last_error` TEXT            DEFAULT NULL,
  `context`    JSON            DEFAULT NULL COMMENT 'structured payload (course, count, threshold, ...)',
  `queued_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at`    DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_outbox_status` (`status`, `queued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 12. Seed: settings singleton + default admin
--     (default password is "admin123" — client must change on first login)
-- =============================================================================
INSERT INTO `settings` (`id`) VALUES (1) ON DUPLICATE KEY UPDATE `id` = `id`;

-- bcrypt hash of "admin123" generated with PASSWORD_BCRYPT (cost 10).
-- Verified locally: password_verify('admin123', '<this hash>') === true.
-- The client MUST change this password on first login (Users > Edit).
INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES (
  'Admin',
  'admin@example.com',
  '$2y$10$/xvkSpvRvbg1/KyjXq/2pOStF6BQvIwiopZZQaXv8ADSmPS8oix.e',
  'super'
) ON DUPLICATE KEY UPDATE `id` = `id`;

SET FOREIGN_KEY_CHECKS = 1;
