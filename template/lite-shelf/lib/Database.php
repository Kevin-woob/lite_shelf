<?php
/**
 * Database Class
 * 
 * Singleton PDO wrapper for database operations
 */

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private array $config;
    private string $tablePrefix = '';
    
    /**
     * Reserved table names that cannot be used as collection names
     */
    private const RESERVED_TABLES = [
        'admin_users', 'api_keys', 'data_collections', 'data_items',
        'storage_files', 'notifications', 'users', 'api_requests'
    ];
    
    /**
     * Get singleton database instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            if (defined('TESTING') && TESTING) {
                require_once __DIR__ . '/../tests/SQLiteTestDatabase.php';
                self::$instance = new SQLiteTestDatabase();
            } else {
                self::$instance = new self();
            }
        }
        return self::$instance;
    }

    /**
     * Reset the singleton instance (useful for test isolation)
     */
    public static function resetInstance(): void {
        if (self::$instance instanceof SQLiteTestDatabase) {
            SQLiteTestDatabase::reset();
        }
        self::$instance = null;
    }
    
    private function __construct() {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->tablePrefix = $this->config['table_prefix'] ?? '';
        $this->pdo = $this->connect();
    }
    
    private function connect(): PDO {
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            $this->config['host'],
            $this->config['port'] ?? 3306,
            $this->config['database']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            return new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate a collection name to prevent SQL injection
     */
    public static function validateCollectionName(string $name): string {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid collection name: {$name}");
        }
        if (in_array(strtolower($name), self::RESERVED_TABLES, true)) {
            throw new InvalidArgumentException("Collection name '{$name}' is reserved");
        }
        return $name;
    }
    
    /**
     * Validate a SQL identifier (table/column name) to prevent injection
     */
    protected static function validateIdentifier(string $name): string {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid identifier: {$name}");
        }
        return $name;
    }
    
    /**
     * Resolve a table name by prepending the configured table prefix
     */
    public function resolveTable(string $table): string {
        return $this->tablePrefix . self::validateIdentifier($table);
    }
    
    /**
     * Get the current table prefix
     */
    public function getTablePrefix(): string {
        return $this->tablePrefix;
    }
    
    /**
     * Prefix all known table names in a raw SQL string
     * Only applies prefix if one is configured
     */
    public function prefixSql(string $sql): string {
        if ($this->tablePrefix === '') {
            return $sql;
        }

        // List of all known table names from the schema, sorted longest first
        // to avoid partial replacements (e.g., 'users' matching inside 'admin_users')
        $tables = [
            'admin_users', 'data_collections', 'storage_files', 'storage_folders', 'api_requests',
            'api_keys', 'data_items', 'notifications', 'users',
        ];

        foreach ($tables as $table) {
            $pattern = '/(?<![a-zA-Z0-9_])' . preg_quote($table, '/') . '(?![a-zA-Z0-9_])/';
            $sql = preg_replace($pattern, $this->tablePrefix . $table, $sql);
        }

        return $sql;
    }
    
    /**
     * Execute a query with parameters
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch a single row
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $sql = $this->prefixSql($sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array {
        $sql = $this->prefixSql($sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert a record and return the last insert ID
     */
    public function insert(string $table, array $data): int {
        $table = $this->resolveTable($table);
        $columns = [];
        foreach (array_keys($data) as $col) {
            $columns[] = self::validateIdentifier($col);
        }
        $columnsStr = implode(', ', $columns);
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columnsStr}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Update records matching condition
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $table = $this->resolveTable($table);
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = self::validateIdentifier($column) . " = :{$column}";
        }

        // Convert positional WHERE params to named to avoid mixing parameter styles
        $allParams = $data;
        $whereClause = $where;
        if (!empty($whereParams)) {
            $paramIndex = 0;
            $whereClause = preg_replace_callback('/\?/', function () use (&$paramIndex, $whereParams, &$allParams) {
                $name = "_w{$paramIndex}";
                $allParams[$name] = $whereParams[$paramIndex];
                $paramIndex++;
                return ":{$name}";
            }, $where);
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($allParams);
        return $stmt->rowCount();
    }
    
    /**
     * Delete records matching condition
     */
    public function delete(string $table, string $where, array $params = []): int {
        $table = $this->resolveTable($table);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Count records matching condition
     */
    public function count(string $table, string $where = '1=1', array $params = []): int {
        $table = $this->resolveTable($table);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollBack(): bool {
        return $this->pdo->rollBack();
    }
}
