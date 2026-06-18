<?php
/**
 * Query
 * 
 * Provides query building and execution for Lite_Shelf queries
 * Uses data_items table with JSON_EXTRACT for server-side filtering
 */

class Query {
    private mixed $db;
    private string $collectionName;
    private int $collectionId;
    private array $conditions = [];
    private ?string $orderByField = null;
    private string $orderDirection = 'ASC';
    private ?int $limitCount = null;
    private int $offsetValue = 0;
    
    /**
     * Constructor
     */
    public function __construct(string $collectionName, int $collectionId, ?string $field = null, ?string $operator = null, $value = null) {
        $this->db = Database::getInstance();
        $this->collectionName = $collectionName;
        $this->collectionId = $collectionId;
        
        // If initial filter provided
        if ($field && $operator && $value !== null) {
            $this->where($field, $operator, $value);
        }
    }
    
    /**
     * Add a WHERE clause
     */
    public function where(string $field, string $operator, $value): self {
        $this->conditions[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }
    
    /**
     * Order by field
     */
    public function orderBy(string $field, string $direction = 'ASC'): self {
        $this->orderByField = $field;
        $this->orderDirection = strtoupper($direction);
        return $this;
    }
    
    /**
     * Limit result count
     */
    public function limit(int $count): self {
        $this->limitCount = $count;
        return $this;
    }
    
    /**
     * Set offset
     */
    public function offset(int $value): self {
        $this->offsetValue = $value;
        return $this;
    }
    
    /**
     * Get first document matching query
     */
    public function first(): ?array {
        $result = $this->get();
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Get JSON extraction expression (compatible with MySQL and SQLite)
     */
    private function jsonExtractExpr(string $field): string {
        // SQLite's json_extract returns unquoted scalars natively
        // MySQL needs JSON_UNQUOTE(JSON_EXTRACT(...))
        if (defined('TESTING') && TESTING) {
            return "json_extract(data, '$.{$field}')";
        }
        return "JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$field}'))";
    }

    /**
     * Execute query and get all results (database-side filtering)
     */
    public function get(): array {
        $sql = "SELECT id, data, created_at, updated_at FROM data_items WHERE collection_id = ?";
        $params = [$this->collectionId];
        
        // Build WHERE conditions using JSON_EXTRACT
        foreach ($this->conditions as $condition) {
            $jsonExpr = $this->jsonExtractExpr($condition['field']);
            $sql .= " AND {$jsonExpr} {$condition['operator']} ?";
            $params[] = is_bool($condition['value']) ? (int) $condition['value'] : $condition['value'];
        }
        
        // Build ORDER BY using JSON_EXTRACT
        if ($this->orderByField) {
            $jsonExpr = $this->jsonExtractExpr($this->orderByField);
            $sql .= " ORDER BY {$jsonExpr} {$this->orderDirection}";
        }
        
        // Build LIMIT/OFFSET
        if ($this->limitCount !== null) {
            $sql .= " LIMIT ?";
            $params[] = $this->limitCount;
        }
        if ($this->offsetValue > 0) {
            $sql .= " OFFSET ?";
            $params[] = $this->offsetValue;
        }
        
        $results = $this->db->fetchAll($sql, $params);
        
        return array_map(function($doc) {
            $decodedData = is_string($doc['data']) ? json_decode($doc['data'], true) : $doc['data'];
            return [
                'id' => $doc['id'],
                'data' => $decodedData,
                'created_at' => $doc['created_at'] ?? null,
                'updated_at' => $doc['updated_at'] ?? null
            ];
        }, $results);
    }
    
    /**
     * Count matching documents
     */
    public function count(): int {
        $sql = "SELECT COUNT(*) as cnt FROM data_items WHERE collection_id = ?";
        $params = [$this->collectionId];
        
        foreach ($this->conditions as $condition) {
            $jsonExpr = $this->jsonExtractExpr($condition['field']);
            $sql .= " AND {$jsonExpr} {$condition['operator']} ?";
            $params[] = is_bool($condition['value']) ? (int) $condition['value'] : $condition['value'];
        }
        
        $stmt = $this->db->query($sql, $params);
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
