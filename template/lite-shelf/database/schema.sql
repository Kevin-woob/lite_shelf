-- Lite_Shelf Database Schema
--
-- NOTE: All tables use a configurable table_prefix for multi-app isolation.
-- The default prefix is empty (''). When provisioned by the dashboard,
-- each app receives its own prefix (e.g., 'myapp_') in config/database.php.
--
-- To use this schema manually, ensure the target database already exists.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Table structure for table `admin_users`
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('admin','super_admin') DEFAULT 'admin',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTE: Create your own admin user after deployment:
-- INSERT INTO admin_users (username, password_hash, email, role) VALUES
-- ('admin', '$2y$10$YOUR_BCRYPT_HASH_HERE', 'admin@example.com', 'super_admin');

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uid` (`uid`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `api_keys`
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_hash` varchar(64) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `allowed_endpoints` text DEFAULT NULL COMMENT 'JSON array of allowed endpoints',
  `rate_limit` int(11) DEFAULT 1000 COMMENT 'requests per hour',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_initial` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`),
  KEY `idx_active` (`is_active`),
  KEY `idx_admin` (`is_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `data_collections`
CREATE TABLE `data_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `schema_config` text DEFAULT NULL COMMENT 'JSON validation rules',
  `created_by_key_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by_key_id` (`created_by_key_id`),
  CONSTRAINT `data_collections_ibfk_1` FOREIGN KEY (`created_by_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `data_items`
CREATE TABLE `data_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `data` json NOT NULL,
  `created_by_key_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `collection_id` (`collection_id`),
  KEY `created_by_key_id` (`created_by_key_id`),
  CONSTRAINT `data_items_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `data_collections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `data_items_ibfk_2` FOREIGN KEY (`created_by_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `storage_files`
CREATE TABLE `storage_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename_original` varchar(255) NOT NULL,
  `folder_path` varchar(1000) NOT NULL DEFAULT '',
  `filename_stored` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size_bytes` int(11) NOT NULL,
  `uploaded_by_key_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename_stored` (`filename_stored`),
  KEY `idx_folder_path` (`folder_path`(255)),
  KEY `uploaded_by_key_id` (`uploaded_by_key_id`),
  KEY `mime_type` (`mime_type`),
  CONSTRAINT `storage_files_ibfk_1` FOREIGN KEY (`uploaded_by_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `storage_folders`
CREATE TABLE `storage_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `path` varchar(1000) NOT NULL,
  `parent_path` varchar(1000) DEFAULT '',
  `created_by_key_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_path` (`path`(191)),
  KEY `idx_parent_path` (`parent_path`),
  KEY `created_by_key_id` (`created_by_key_id`),
  CONSTRAINT `storage_folders_ibfk_1` FOREIGN KEY (`created_by_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_user_id` int(11) DEFAULT NULL,
  `sender_key_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `target_user_id` (`target_user_id`),
  KEY `created_at` (`created_at`),
  KEY `idx_unread` (`is_read`),
  KEY `sender_key_id` (`sender_key_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`sender_key_id`) REFERENCES `api_keys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `api_key_collection_access`
-- Junction table for API key → collection permissions
CREATE TABLE `api_key_collection_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `access_level` enum('read','write','full') NOT NULL DEFAULT 'read',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_collection_unique` (`key_id`,`collection_id`),
  KEY `idx_collection` (`collection_id`),
  CONSTRAINT `api_key_collection_access_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `api_key_collection_access_ibfk_2` FOREIGN KEY (`collection_id`) REFERENCES `data_collections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `api_key_folder_access`
-- Junction table for API key → folder permissions
CREATE TABLE `api_key_folder_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_id` int(11) NOT NULL,
  `folder_path` varchar(1000) NOT NULL DEFAULT '',
  `access_level` enum('read','write','full') NOT NULL DEFAULT 'read',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_folder_unique` (`key_id`,`folder_path`(191)),
  KEY `idx_folder_path` (`folder_path`(255)),
  CONSTRAINT `api_key_folder_access_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;