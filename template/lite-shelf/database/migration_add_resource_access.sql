-- Migration: Add Resource Access Control Tables
--
-- Adds tables for managing API key access to collections and folders.
-- Run this migration after the base schema is in place.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Table structure for table `api_key_collection_access`
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
CREATE TABLE `api_key_folder_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_id` int(11) NOT NULL,
  `folder_path` varchar(1000) NOT NULL DEFAULT '',
  `access_level` enum('read','write','full') NOT NULL DEFAULT 'read',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_folder_unique` (`key_id`,`folder_path`),
  KEY `idx_folder_path` (`folder_path`(255)),
  CONSTRAINT `api_key_folder_access_ibfk_1` FOREIGN KEY (`key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
