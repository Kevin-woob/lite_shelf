-- Migration: Add folder support to storage system
-- Adds folder_path column to storage_files and creates storage_folders table
-- Run this on an existing installation to upgrade to folder support

-- 1. Add folder_path column to storage_files (if not exists)
-- Note: If the column already exists, this will error - safe to skip
ALTER TABLE `storage_files`
ADD COLUMN `folder_path` VARCHAR(1000) NOT NULL DEFAULT '' AFTER `filename_original`,
ADD INDEX `idx_folder_path` (`folder_path`(255));

-- 2. Create storage_folders table (if not exists)
CREATE TABLE `storage_folders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `path` VARCHAR(1000) NOT NULL,
  `parent_path` VARCHAR(1000) DEFAULT '',
  `created_by_key_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`),
  KEY `idx_parent_path` (`parent_path`),
  KEY `created_by_key_id` (`created_by_key_id`),
  CONSTRAINT `storage_folders_ibfk_1` FOREIGN KEY (`created_by_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
