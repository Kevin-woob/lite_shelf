<?php
/**
 * API Key Manager
 * 
 * Handles generation, validation, and management of API keys
 */

class ApiKeyManager {
    private mixed $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate a new API key
     */
    public function generateKey(string $name = null, string $allowedEndpoints = null, int $rateLimit = 1000, bool $isAdmin = false): array {
        // Generate secure random key (32 bytes = 64 hex chars)
        $key = bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $key);
        
        // Store in database
        $data = [
            'key_hash' => $keyHash,
            'name' => $name ?: 'API Key ' . date('Y-m-d H:i:s'),
            'allowed_endpoints' => $allowedEndpoints ? json_encode($allowedEndpoints) : null,
            'rate_limit' => $rateLimit,
            'expires_at' => null,
            'is_admin' => $isAdmin ? 1 : 0
        ];
        
        $id = $this->db->insert('api_keys', $data);
        
        return [
            'id' => $id,
            'key' => $key, // Return plain text key only once!
            'key_hash' => $keyHash,
            'is_admin' => $isAdmin,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Validate an API key
     */
    public function validateKey(string $key): ?array {
        $keyHash = hash('sha256', $key);
        
        $apiKey = $this->db->fetchOne(
            "SELECT * FROM api_keys WHERE key_hash = ? AND is_active = 1",
            [$keyHash]
        );
        
        if (!$apiKey) {
            return null;
        }
        
        // Check expiration
        if ($apiKey['expires_at'] && strtotime($apiKey['expires_at']) < time()) {
            return null;
        }
        
        return $apiKey;
    }
    
    /**
     * Get allowed endpoints for a key
     */
    public function getAllowedEndpoints(array $apiKey): ?array {
        if (!$apiKey['allowed_endpoints']) {
            return null; // No restrictions
        }
        
        return json_decode($apiKey['allowed_endpoints'], true);
    }
    
    /**
     * Check if endpoint is allowed for this key
     */
    public function isEndpointAllowed(array $apiKey, string $endpoint): bool {
        $allowed = $this->getAllowedEndpoints($apiKey);
        
        // No restrictions means all endpoints allowed
        if ($allowed === null) {
            return true;
        }
        
        return in_array($endpoint, $allowed);
    }
    
    /**
     * Check rate limit
     */
    public function checkRateLimit(int $keyId): bool {
        // Placeholder - implement with Redis or request logging table
        return true;
    }
    
    /**
     * Revoke an API key
     */
    public function revokeKey(int $keyId): bool {
        return (bool) $this->db->update('api_keys', ['is_active' => 0], 'id = ?', [$keyId]);
    }
    
    /**
     * Delete an API key permanently
     */
    public function deleteKey(int $keyId): bool {
        return (bool) $this->db->delete('api_keys', 'id = ?', [$keyId]);
    }
    
    /**
     * List all API keys
     */
    public function listKeys(): array {
        return $this->db->fetchAll("SELECT id, name, created_at, expires_at, is_active, is_admin, is_initial FROM api_keys ORDER BY created_at DESC");
    }
}
