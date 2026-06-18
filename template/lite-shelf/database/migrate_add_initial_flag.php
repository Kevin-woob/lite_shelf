<?php
/**
 * Migration: Add is_initial column to api_keys table
 * This prevents revoking the initial admin key
 * 
 * Run this script once to apply the migration
 * Usage: php database/migrate_add_initial_flag.php
 */

require_once __DIR__ . '/../lib/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Starting migration: Add is_initial column to api_keys table...\n";
    
    $prefix = $db->getTablePrefix();
    
    // Step 1: Add the is_initial column (ignore error if it already exists)
    try {
        $db->query("ALTER TABLE {$prefix}api_keys ADD COLUMN is_initial TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin");
        echo "✓ Added 'is_initial' column to {$prefix}api_keys table.\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ Column 'is_initial' already exists. Skipping.\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Mark the oldest active admin key as initial
    $row = $db->fetchOne(
        "SELECT MIN(id) as min_id FROM {$prefix}api_keys WHERE is_admin = 1 AND is_active = 1"
    );
    
    if ($row && $row['min_id']) {
        $db->update('api_keys', ['is_initial' => 1], 'id = ?', [(int) $row['min_id']]);
        echo "✓ Marked API key ID {$row['min_id']} as initial admin key.\n";
    } else {
        echo "ℹ No active admin keys found. No key marked as initial.\n";
    }
    
    echo "\nMigration complete!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
