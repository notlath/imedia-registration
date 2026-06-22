-- IMedia Registration — Phase 8 login throttling.
--
-- Adds admins.locked_until so a locked admin is rejected with 429 even
-- when the per-IP limiter is bypassed (e.g. attacker rotates IPs), and
-- login_attempts for the rolling-window counters used by LoginController.
--
-- Both are additive; existing rows are untouched.

ALTER TABLE admins
    ADD COLUMN locked_until DATETIME NULL DEFAULT NULL AFTER last_login_at;

CREATE TABLE IF NOT EXISTS login_attempts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip         VARBINARY(16)   NOT NULL,                     -- inet_pton() / inet_ntop()
    email      VARCHAR(190)    NOT NULL,
    success    TINYINT(1)      NOT NULL,                     -- 1 = success (clears prior failures), 0 = failure
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_ip_time   (ip, created_at),
    KEY idx_login_attempts_email_time (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
