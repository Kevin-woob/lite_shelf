<?php
/**
 * Collection Reference
 * 
 * Provides CRUD operations for Lite_Shelf collection access
 * Uses data_collections + data_items schema
 */

require_once __DIR__ . '/Query.php';

class CollectionReference {
    private mixed $db;
    private string $collectionName;
    private int $collectionId;
    
    /**
     * Constructor
     */
    public function __construct(string $collectionName) {
        $this->db = Database::getInstance();
        $this->collectionName = Database::validateCollectionName($collectionName);
        $this->collectionId = $this->getCollectionId();
    }
    
    /**
     * Get or create the collection ID from data_collections table
     */
    private function getCollectionId(): int {
        $collection = $this->db->fetchOne(
            "SELECT id FROM data_collections WHERE name = ?",
            [$this->collectionName]
        );
        
        if (!$collection) {
            // Auto-create collection (Lite_Shelf behavior)
            $this->db->insert('data_collections', [
                'name' => $this->collectionName,
                'description' => null,
                'schema_config' => null
            ]);
            $collection = $this->db->fetchOne(
                "SELECT id FROM data_collections WHERE name = ?",
                [$this->collectionName]
            );
        }
        
        return (int) $collection['id'];
    }
    
    /**
     * Get a reference to a specific document
     */
    public function document(string $documentId): DocumentReference {
        return new DocumentReference($this->collectionName, $this->collectionId, $documentId);
    }
    
    /**
     * Add a new document with auto-generated ID
     */
    public function add(array $data): array {
        $id = $this->generateId();
        $docRef = $this->document($id);
        $insertedId = $docRef->set($data);
        return ['id' => (string) $insertedId];
    }
    
    /**
     * Create or overwrite a document
     */
    public function set(string $documentId, array $data): void {
        $docRef = $this->document($documentId);
        $docRef->set($data);
    }
    
    /**
     * Update an existing document
     */
    public function update(string $documentId, array $data): void {
        $docRef = $this->document($documentId);
        $docRef->update($data);
    }
    
    /**
     * Delete a document
     */
    public function delete(string $documentId): bool {
        $docRef = $this->document($documentId);
        return $docRef->delete();
    }
    
    /**
     * Query documents in the collection
     */
    public function where(string $field, string $operator, $value): Query {
        return new Query($this->collectionName, $this->collectionId, $field, $operator, $value);
    }
    
    /**
     * Order by field (starts a query chain)
     */
    public function orderBy(string $field, string $direction = 'ASC'): Query {
        return (new Query($this->collectionName, $this->collectionId))->orderBy($field, $direction);
    }
    
    /**
     * Limit result count (starts a query chain)
     */
    public function limit(int $count): Query {
        return (new Query($this->collectionName, $this->collectionId))->limit($count);
    }
    
    /**
     * Get all documents in collection
     */
    public function get(): array {
        $results = $this->db->fetchAll(
            "SELECT id, data, created_at, updated_at FROM data_items WHERE collection_id = ?",
            [$this->collectionId]
        );
        
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
     * Count documents in collection
     */
    public function count(): int {
        return $this->db->count('data_items', "collection_id = ?", [$this->collectionId]);
    }
    
    /**
     * Get first document
     */
    public function first(): ?array {
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Get the collection name
     */
    public function getName(): string {
        return $this->collectionName;
    }
    
    /**
     * Generate a unique document ID
     */
    private function generateId(): string {
        return uniqid('doc_', true);
    }
}
