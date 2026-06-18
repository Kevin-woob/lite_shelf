<?php
/**
 * Complete database setup for testing
 */

require_once __DIR__ . '/lib/Database.php';

echo "=== Setting Up Database for Testing ===\n\n";

try {
    $db = Database::getInstance();
    
    // Read the full schema
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Schema file not found: {$schemaFile}\n");
    }
    
    $sql = file_get_contents($schemaFile);
    
    echo "Executing schema.sql...\n";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--') && !str_starts_with($s, 'SET') && !str_starts_with($s, 'COMMIT')
    );
    
    $created = 0;
    $skipped = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        // Extract table name from CREATE TABLE statement
        if (preg_match('/CREATE TABLE `?(\w+)`?/i', $statement, $matches)) {
            $tableName = $matches[1];
            
            // Check if table already exists
            $result = $db->query("SHOW TABLES LIKE ?", [$tableName]);
            if ($result->rowCount() > 0) {
                echo "  ⚠ Table '{$tableName}' already exists (skipped)\n";
                $skipped++;
                continue;
            }
            
            echo "  Creating table '{$tableName}'...\n";
            $db->query($statement);
            $created++;
        }
    }
    
    echo "\n✓ Migration completed successfully!\n";
    echo "  Created: {$created} tables\n";
    echo "  Skipped: {$skipped} tables (already exist)\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
