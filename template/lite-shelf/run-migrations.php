<?php
/**
 * Run database migrations for testing
 */

require_once __DIR__ . '/lib/Database.php';

echo "=== Running Database Migrations ===\n\n";

$db = Database::getInstance();

// Read migration file
$migrationFile = __DIR__ . '/database/migration_add_storage_folders.sql';
if (!file_exists($migrationFile)) {
    die("Migration file not found: {$migrationFile}\n");
}

$sql = file_get_contents($migrationFile);

echo "Executing migration: migration_add_storage_folders.sql\n";

try {
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--')
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        echo "Executing: " . substr($statement, 0, 80) . "...\n";
        
        try {
            $db->query($statement);
            echo "✓ Success\n";
        } catch (Exception $e) {
            // Check if error is about duplicate column/table (already exists)
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Already exists (skipped)\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
