-- =============================================================================
-- Migration 005: Add registrations.resume_path (Phase 5)
--
-- Add a nullable VARCHAR(512) to registrations for storing the relative
-- path of an uploaded resume file (e.g. "uploads/registrations/2026/06/abc.pdf").
-- Set by App\Services\FileStorage on upload. NULL = no resume.
--
-- Apply: import this file in cPanel > phpMyAdmin > Import, on a database
-- that was set up with the original schema.sql (Phases 1-4). Fresh installs
-- already have this column from the updated schema.sql.
-- =============================================================================

SET NAMES utf8mb4;

-- Guard: only add the column if it doesn't already exist.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'registrations'
    AND COLUMN_NAME  = 'resume_path'
);
SET @ddl := IF(
  @col_exists = 0,
  'ALTER TABLE `registrations` ADD COLUMN `resume_path` VARCHAR(512) DEFAULT NULL COMMENT ''relative to public/; set by FileStorage'' AFTER `dynamic_data`',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
