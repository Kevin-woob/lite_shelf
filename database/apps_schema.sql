-- Lite_Shelf Database Schema
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `lite_shelf` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lite_shelf`;

-- Table structure for table `apps`
CREATE TABLE `apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `folder_path` varchar(255) NOT NULL COMMENT 'Relative path to app folder',
  `api_key` varchar(255) DEFAULT NULL COMMENT 'Admin API key for this app',
  `database_name` varchar(100) DEFAULT NULL COMMENT 'MySQL database name for this app',
  `status` enum('active','inactive','error') DEFAULT 'active',
  `config` text DEFAULT NULL COMMENT 'JSON configuration for the app',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial admin user for the dashboard
INSERT INTO `apps` (`name`, `folder_path`, `status`, `config`) VALUES
('dashboard', 'dashboard', 'active', '{"role": "admin", "description": "Dashboard application"}');

COMMIT;
