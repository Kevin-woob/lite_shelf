<?php
/**
 * Document Reference
 * 
 * Provides operations for individual Lite_Shelf documents
 * Uses data_collections + data_items schema
 */

class DocumentReference {
    private mixed $db;
    private string $collectionName;
    private int $collectionId;
    private string $documentId;
    
    /**
     * Constructor
     */
    public function __construct(string $collectionName, int $collectionId, string $documentId) {
        $this->db = Database::getInstance();
        $this->collectionName = $collectionName;
        $this->collectionId = $collectionId;
        $this->documentId = $documentId;
    }
    
    /**
     * Get document data
     */
    public function get(): ?array {
        $doc = $this->db->fetchOne(
            "SELECT id, data, created_at, updated_at FROM data_items WHERE collection_id = ? AND id = ?",
            [$this->collectionId, $this->documentId]
        );
        
        if (!$doc) {
            return null;
        }
        
        $decodedData = is_string($doc['data']) ? json_decode($doc['data'], true) : $doc['data'];
        
        // Return document with id and decoded data
        return array_merge(['id' => $doc['id']], $decodedData);
    }
    
    /**
     * Set/overwrite document data
     */
    public function set(array $data): ?int {
        // Check if document exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM data_items WHERE collection_id = ? AND id = ?",
            [$this->collectionId, $this->documentId]
        );
        
        if ($existing) {
            // Update existing document
            $this->db->update('data_items', [
                'data' => json_encode($data)
            ], "collection_id = ? AND id = ?", [$this->collectionId, $this->documentId]);
            return (int) $existing['id'];
        } else {
            // Insert new document - let MySQL auto-increment the ID
            $lastId = $this->db->insert('data_items', [
                'collection_id' => $this->collectionId,
                'data' => json_encode($data)
            ]);
            $this->documentId = (string) $lastId;
            return (int) $lastId;
        }
    }
    
    /**
     * Update specific fields in document
     */
    public function update(array $data): void {
        // Get existing document first
        $existing = $this->get();
        
        if ($existing) {
            // Merge with existing data
            unset($existing['id']);
            $mergedData = array_merge($existing, $data);
            
            $this->db->update('data_items', [
                'data' => json_encode($mergedData)
            ], "collection_id = ? AND id = ?", [$this->collectionId, $this->documentId]);
        }
    }
    
    /**
     * Delete document
     */
    public function delete(): bool {
        $result = $this->db->delete('data_items', "collection_id = ? AND id = ?", [$this->collectionId, $this->documentId]);
        return $result > 0;
    }
    
    /**
     * Check if document exists
     */
    public function exists(): bool {
        $doc = $this->get();
        return $doc !== null && isset($doc['id']);
    }
    
    /**
     * Get reference to parent collection
     */
    public function collection(): CollectionReference {
        return new CollectionReference($this->collectionName);
    }
    
    /**
     * Get the document ID
     */
    public function getId(): string {
        return $this->documentId;
    }
}
