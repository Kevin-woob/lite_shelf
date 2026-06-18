<?php
/**
 * Create missing storage_files table
 */

require_once __DIR__ . '/lib/Database.php';

echo "=== Creating storage_files Table ===\n\n";

try {
    $db = Database::getInstance();
    
    // Check if storage_files exists
    $result = $db->query("SHOW TABLES LIKE 'storage_files'");
    if ($result->rowCount() > 0) {
        echo "✓ Table 'storage_files' already exists\n";
        exit(0);
    }
    
    echo "Creating table 'storage_files'...\n";
    
    $sql = "CREATE TABLE `storage_files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `filename_original` varchar(255) NOT NULL,
        `filename_stored` varchar(255) NOT NULL,
        `folder_path` varchar(1000) NOT NULL DEFAULT '',
        `mime_type` varchar(100) DEFAULT NULL,
        `size_bytes` int(11) NOT NULL,
        `uploaded_by_key_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `filename_stored` (`filename_stored`),
        KEY `idx_folder_path` (`folder_path`(255)),
        KEY `uploaded_by_key_id` (`uploaded_by_key_id`),
        KEY `mime_type` (`mime_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql);
    echo "✓ Table 'storage_files' created successfully\n";
    
} catch (Exception $e) {
    echo "\n✗ Failed: " . $e->getMessage() . "\n";
    exit(1);
}
