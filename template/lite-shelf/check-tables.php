<?php
/**
 * Check database tables
 */

require_once __DIR__ . '/lib/Database.php';

echo "=== Checking Database Tables ===\n\n";

try {
    $db = Database::getInstance();
    
    // Check if storage_folders exists
    $result = $db->query("SHOW TABLES LIKE 'storage_folders'");
    if ($result->rowCount() > 0) {
        echo "✓ Table 'storage_folders' exists\n";
    } else {
        echo "✗ Table 'storage_folders' does NOT exist\n";
    }
    
    // Check if storage_files exists
    $result = $db->query("SHOW TABLES LIKE 'storage_files'");
    if ($result->rowCount() > 0) {
        echo "✓ Table 'storage_files' exists\n";
    } else {
        echo "✗ Table 'storage_files' does NOT exist\n";
    }
    
    // List all tables
    echo "\nAll tables in database:\n";
    $result = $db->query("SHOW TABLES");
    $tables = $result->fetchAll();
    foreach ($tables as $table) {
        echo "  - " . array_values($table)[0] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
