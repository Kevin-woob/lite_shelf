<?php
/**
 * App Manager
 * 
 * Handles CRUD operations for applications in the dashboard.
 * Manages app provisioning by copying the template to new folders.
 */

class AppManager {
    private Database $db;
    private string $templatePath;
    private string $appsPath;
    public string $lastProvisionError = '';
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->templatePath = dirname(__DIR__) . '/template/lite-shelf/';
        $this->appsPath = dirname(__DIR__) . '/apps/';
    }
    
    /**
     * List all applications
     */
    public function listApps(): array {
        return $this->db->fetchAll(
            'SELECT id, name, folder_path, api_key, database_name, status, config, created_at, updated_at FROM apps ORDER BY created_at DESC'
        );
    }
    
    /**
     * Get a single application by ID
     */
    public function getApp(int $id): ?array {
        return $this->db->fetchOne(
            'SELECT id, name, folder_path, api_key, database_name, status, config, created_at, updated_at FROM apps WHERE id = ?',
            [$id]
        );
    }
    
    /**
     * Validate app name (alphanumeric, dashes, underscores, 3-50 chars)
     */
    public function validateAppName(string $name): bool {
        if (strlen($name) < 3 || strlen($name) > 50) {
            return false;
        }
        return preg_match('/^[a-zA-Z0-9_-]+$/', $name) === 1;
    }
    
    /**
     * Create a new application with full template provisioning
     */
    public function createApp(string $name, array $config = []): int {
        // Validate name
        if (!$this->validateAppName($name)) {
            throw new InvalidArgumentException('App name must be 3-50 characters, containing only letters, numbers, dashes, and underscores');
        }
        
        $folderName = strtolower($name);
        $folderPath = $this->appsPath . $folderName;
        
        // Check for duplicate folder
        if (file_exists($folderPath)) {
            throw new InvalidArgumentException('An app with this name already exists');
        }
        
        // Check for duplicate name in database
        $existing = $this->db->fetchOne('SELECT id FROM apps WHERE name = ?', [$name]);
        if ($existing) {
            throw new InvalidArgumentException('An app with this name already exists');
        }
        
        // Copy template to new folder
        if (!$this->copyDirectory($this->templatePath, $folderPath)) {
            throw new RuntimeException('Failed to create app folder from template');
        }
        
        // Generate unique values
        $apiKey = $this->generateApiKey();
        // All apps share the same database; isolation is via table prefix
        // Read the actual database name from the dashboard's config (written by setup.php)
        $tplConfig = require $this->templatePath . 'config/database.php';
        $sharedDatabase = $tplConfig['database'];
        $tablePrefix = $folderName . '_';

        // Update config files in the new app
        $this->updateAppConfig($folderPath, $name, $sharedDatabase, $apiKey, $tablePrefix);
        
        // Create database tables and seed admin API key
        $appStatus = 'active';
        $provisionError = '';
        try {
            $appDbConfigPath = $folderPath . '/config/database.php';
            if (file_exists($appDbConfigPath)) {
                $appDbConfig = require $appDbConfigPath;
                $appPdo = new PDO(
                    "mysql:host={$appDbConfig['host']};port={$appDbConfig['port']};dbname={$appDbConfig['database']};charset=utf8mb4",
                    $appDbConfig['username'],
                    $appDbConfig['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Execute schema.sql with table prefix
                $this->executeSchema($appPdo, $tablePrefix);
                
                // Seed the generated API key into the provisioned app's api_keys table
                $keyHash = hash('sha256', $apiKey);
                $tableName = $tablePrefix . 'api_keys';
                
                $stmt = $appPdo->prepare(
                    "INSERT INTO `{$tableName}` (key_hash, name, is_admin, is_active, rate_limit, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([$keyHash, 'Initial Admin Key', 1, 1, 1000]);
            }
        } catch (\Throwable $e) {
            error_log('Failed to provision app "' . $name . '": ' . $e->getMessage());
            $appStatus = 'error';
            $provisionError = $e->getMessage();
        }
        
        // Insert into database (database_name stores the table prefix for reference)
        $data = [
            'name' => $name,
            'folder_path' => $folderName,
            'api_key' => $apiKey,
            'database_name' => $tablePrefix,
            'status' => $appStatus,
            'config' => !empty($config) ? json_encode($config) : null,
        ];
        
        $id = $this->db->insert('apps', $data);
        
        // Store the provision error so the caller can surface it
        if ($provisionError !== '') {
            $this->lastProvisionError = $provisionError;
        }
        
        return $id;
    }
    
    /**
     * Update an application
     */
    public function updateApp(int $id, array $data): int {
        $allowed = ['name', 'folder_path', 'api_key', 'database_name', 'status', 'config'];
        $updateData = [];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                if ($field === 'config' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }
        
        if (empty($updateData)) {
            throw new InvalidArgumentException('No valid fields to update');
        }
        
        return $this->db->update('apps', $updateData, 'id = ?', [$id]);
    }
    
    /**
     * Delete an application, drop its prefixed tables, and remove its folder
     */
    public function deleteApp(int $id): int {
        $app = $this->getApp($id);
        if (!$app) {
            throw new InvalidArgumentException('App not found');
        }

        // Drop all tables with this app's prefix
        $tablePrefix = $app['database_name'];
        if (!empty($tablePrefix)) {
            $this->dropAppTables($tablePrefix);
        }
        
        // Remove folder if it exists
        $folderPath = $this->appsPath . $app['folder_path'];
        if (file_exists($folderPath)) {
            $this->deleteDirectory($folderPath);
        }
        
        // Delete from database
        return $this->db->delete('apps', 'id = ?', [$id]);
    }
    
    /**
     * Drop all tables matching the given prefix in the shared database
     */
    private function dropAppTables(string $tablePrefix): void {
        // Try dashboard config first, then template config as fallback
        $dashboardConfig = __DIR__ . '/../config/database.php';
        $templateConfig = $this->templatePath . '/config/database.php';
        
        if (file_exists($dashboardConfig)) {
            $config = require $dashboardConfig;
        } elseif (file_exists($templateConfig)) {
            $config = require $templateConfig;
        } else {
            throw new RuntimeException('No database config found');
        }
        
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Get all tables matching the prefix
        $stmt = $pdo->query(
            "SHOW TABLES LIKE '{$tablePrefix}%'"
        );
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Disable foreign key checks temporarily
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats(): array {
        $total = $this->db->count('apps');
        $active = $this->db->count('apps', "status = 'active'");
        $inactive = $this->db->count('apps', "status = 'inactive'");
        $error = $this->db->count('apps', "status = 'error'");
        
        return [
            'total_apps' => $total,
            'active_apps' => $active,
            'inactive_apps' => $inactive,
            'error_apps' => $error,
        ];
    }
    
    /**
     * Generate a random API key
     */
    public function generateApiKey(): string {
        return 'app_' . bin2hex(random_bytes(32));
    }
    
    /**
     * Execute the app's schema.sql with the given table prefix
     */
    private function executeSchema(\PDO $pdo, string $tablePrefix): void {
        $schemaPath = $this->templatePath . '/database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new \RuntimeException('Schema file not found: ' . $schemaPath);
        }
        
        $sql = file_get_contents($schemaPath);
        
        // Prepend table prefix to all table names in backticks
        // This covers CREATE TABLE, REFERENCES, and any other backticked references
        $sql = preg_replace('/`(admin_users|users|api_keys|data_collections|data_items|storage_files|storage_folders|notifications|api_key_collection_access|api_key_folder_access)`/', "`{$tablePrefix}\$1`", $sql);
        
        // Update CONSTRAINT names to avoid conflicts
        $sql = preg_replace('/CONSTRAINT `([^`]+)_ibfk_(\d+)`/', "CONSTRAINT `{$tablePrefix}\$1_ibfk_\$2`", $sql);
        
        // Update KEY and UNIQUE KEY names
        $sql = preg_replace('/UNIQUE KEY `([^`]+)`/', "UNIQUE KEY `{$tablePrefix}\$1`", $sql);
        // Only prefix non-standard KEY names (skip idx_* which are column indexes)
        $sql = preg_replace('/(?<!UNIQUE )KEY `((?!idx_)[^`]+)`/', "KEY `{$tablePrefix}\$1`", $sql);
        
        // Disable foreign key checks during schema creation
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        // Split by semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            $pdo->exec($statement);
        }
        
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    /**
     * Copy a directory recursively
     */
    private function copyDirectory(string $source, string $destination): bool {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        if ($dir === false) {
            return false;
        }
        
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
        closedir($dir);
        return true;
    }
    
    /**
     * Delete a directory recursively
     */
    private function deleteDirectory(string $path): bool {
        if (!is_dir($path)) {
            return false;
        }
        
        $dir = opendir($path);
        if ($dir === false) {
            return false;
        }
        
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        closedir($dir);
        return rmdir($path);
    }
    
    /**
     * Update config files in the new app folder
     */
    private function updateAppConfig(string $folderPath, string $appName, string $databaseName, string $apiKey, string $tablePrefix): void {
        // Update database.php
        $dbConfigPath = $folderPath . '/config/database.php';
        if (file_exists($dbConfigPath)) {
            $content = file_get_contents($dbConfigPath);
            // Replace database name with unique one for this app
            $content = preg_replace(
                "/'database' => getenv\('DB_NAME'\) \?: '.*?'/",
                "'database' => getenv('DB_NAME') ?: '{$databaseName}'",
                $content
            );
            // Replace table_prefix with unique one for this app
            $content = preg_replace(
                "/'table_prefix' => getenv\('DB_PREFIX'\) \?: '.*?'/",
                "'table_prefix' => getenv('DB_PREFIX') ?: '{$tablePrefix}'",
                $content
            );
            file_put_contents($dbConfigPath, $content);
        }
        
        // Update settings.php
        $settingsPath = $folderPath . '/config/settings.php';
        if (file_exists($settingsPath)) {
            $content = file_get_contents($settingsPath);
            // Replace app name
            $content = preg_replace(
                "/'app_name' => '.*?'/",
                "'app_name' => '{$appName}'",
                $content
            );
            file_put_contents($settingsPath, $content);
        }
    }
    
    /**
     * Get stats for an app folder
     */
    public function getAppStats(string $folderName): array {
        $folderPath = $this->appsPath . $folderName;
        
        if (!is_dir($folderPath)) {
            return ['exists' => false];
        }
        
        $size = 0;
        $fileCount = 0;
        $dirCount = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileCount++;
                $size += $file->getSize();
            } elseif ($file->isDir() && $file->getFilename() !== '.') {
                $dirCount++;
            }
        }
        
        return [
            'exists' => true,
            'size_bytes' => $size,
            'size_human' => $this->humanFileSize($size),
            'file_count' => $fileCount,
            'folder_count' => $dirCount,
        ];
    }
    
    /**
     * Format file size to human-readable format
     */
    private function humanFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
