<?php
/**
 * Simple migration script to create storage_folders table
 */

require_once __DIR__ . '/lib/Database.php';

echo "=== Creating storage_folders Table ===\n\n";

try {
    $db = Database::getInstance();
    
    // Check if table already exists
    $result = $db->query("SHOW TABLES LIKE 'storage_folders'");
    if ($result->rowCount() > 0) {
        echo "✓ Table 'storage_folders' already exists\n";
        exit(0);
    }
    
    echo "Creating table 'storage_folders'...\n";
    
    // Create the table
    $sql = "CREATE TABLE `storage_folders` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(200) NOT NULL,
        `path` VARCHAR(1000) NOT NULL,
        `parent_path` VARCHAR(1000) DEFAULT '',
        `created_by_key_id` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `path` (`path`(255)),
        KEY `idx_parent_path` (`parent_path`(255)),
        KEY `created_by_key_id` (`created_by_key_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql);
    echo "✓ Table 'storage_folders' created successfully\n";
    
    // Check if folder_path column exists in storage_files
    $result = $db->query("SHOW COLUMNS FROM storage_files LIKE 'folder_path'");
    if ($result->rowCount() == 0) {
        echo "\nAdding 'folder_path' column to storage_files...\n";
        $db->query("ALTER TABLE storage_files ADD COLUMN folder_path VARCHAR(1000) NOT NULL DEFAULT '' AFTER filename_original");
        $db->query("ALTER TABLE storage_files ADD INDEX idx_folder_path (folder_path(255))");
        echo "✓ Column 'folder_path' added successfully\n";
    } else {
        echo "\n✓ Column 'folder_path' already exists in storage_files\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
