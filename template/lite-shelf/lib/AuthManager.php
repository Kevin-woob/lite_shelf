<?php
/**
 * Authentication Manager
 * 
 * Handles user authentication, session management, and password operations
 */

class AuthManager {
    private mixed $db;
    private ?array $currentUser = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new user
     */
    public function register(string $email, string $password, array $profileData = []): array {
        // Check if email already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            throw new Exception("Email already registered");
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate unique ID for the user
        $userId = uniqid('user_', true);
        
        // Insert user
        $userData = array_merge([
            'uid' => $userId,
            'email' => $email,
            'password' => $hashedPassword
        ], $profileData);
        
        $this->db->insert('users', $userData);
        
        // Return full user object
        return ['success' => true, 'user' => $userData];
    }
    
    /**
     * Authenticate user
     */
    public function login(string $email, string $password): array {
        // Find user by email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            throw new Exception("Invalid credentials");
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Invalid credentials");
        }
        
        // Update last login
        $this->db->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], "id = ?", [$user['id']]);
        
        // Set current user
        $this->currentUser = $user;
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Logout current user
     */
    public function logout(): void {
        $this->currentUser = null;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?array {
        return $this->currentUser;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool {
        return $this->currentUser !== null;
    }
    
    /**
     * Change user password
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Verify old password
        if (!password_verify($oldPassword, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users', [
            'password' => $hashedPassword
        ], "id = ?", [$userId]);
        
        return true;
    }
    
    /**
     * Reset password (for forgot password flow)
     */
    public function resetPassword(string $email, string $newPassword): bool {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users', [
            'password' => $hashedPassword
        ], "id = ?", [$user['id']]);
        
        return true;
    }
    
    /**
     * Verify a JWT token (simple version - returns user if token matches session)
     */
    public function verifyToken(string $token): ?array {
        // For simplicity, verify token against stored session
        if (isset($_SESSION['user_token']) && $_SESSION['user_token'] === $token) {
            return $this->currentUser;
        }
        
        // Try to decode as base64 token
        $decoded = json_decode(base64_decode($token), true);
        if ($decoded && isset($decoded['user_id'])) {
            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$decoded['user_id']]);
            if ($user) {
                return $user;
            }
        }
        
        return null;
    }
    
    /**
     * Create user (admin function)
     */
    public function createUser(string $email, string $password, array $profileData = []): array {
        // Check if email already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            throw new Exception("Email already registered");
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate unique ID for the user
        $userId = uniqid('user_', true);
        
        // Insert user
        $userData = [
            'uid' => $userId,
            'email' => $email,
            'password' => $hashedPassword,
            'display_name' => $profileData['display_name'] ?? null,
            'is_active' => 1
        ];
        
        $insertId = $this->db->insert('users', $userData);
        $userData['id'] = $insertId;
        unset($userData['password']);
        
        return $userData;
    }
    
    /**
     * Delete user
     */
    public function deleteUser(int $userId): bool {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return false;
        }
        
        return (bool) $this->db->delete('users', 'id = ?', [$userId]);
    }
    
    /**
     * Helper method for tests - register user using test helper names
     */
    public function registerUser(string $email, string $password): array {
        return $this->register($email, $password);
    }
    
    /**
     * Helper method for tests - login user using test helper names
     */
    public function loginUser(string $email, string $password): array {
        return $this->login($email, $password);
    }
    
    /**
     * Helper method for tests - verify password
     */
    public function verifyPassword(string $password, string $email): bool {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        if (!$user) {
            return false;
        }
        return password_verify($password, $user['password']);
    }
}
