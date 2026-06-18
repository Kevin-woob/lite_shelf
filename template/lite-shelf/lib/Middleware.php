<?php
/**
 * Authentication Middleware
 * 
 * Validates authentication for protected routes
 */

class AuthMiddleware {
    private AuthManager $authManager;
    
    public function __construct() {
        $this->authManager = new AuthManager();
    }
    
    /**
     * Create middleware callable
     */
    public function __invoke(array $request, callable $next): mixed {
        // Extract Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            Response::unauthorized('Missing or invalid authorization header');
        }
        
        // Verify token
        $user = $this->authManager->verifyToken($token);
        
        if (!$user) {
            Response::unauthorized('Invalid or expired token');
        }
        
        // Add user to request
        $request['user'] = $user;
        
        return $next($request);
    }
}

/**
 * API Key Middleware
 * 
 * Validates API keys for API endpoints
 */
class ApiKeyMiddleware {
    private ApiKeyManager $apiKeyManager;
    
    public function __construct() {
        $this->apiKeyManager = new ApiKeyManager();
    }
    
    /**
     * Create middleware callable
     */
    public function __invoke(array $request, callable $next): mixed {
        // Extract API key from header only (not query params - prevents logging leakage)
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        if (!$apiKey) {
            Response::unauthorized('API key required');
        }
        
        // Validate key
        $apiKeyData = $this->apiKeyManager->validateKey($apiKey);
        
        if (!$apiKeyData) {
            Response::unauthorized('Invalid API key');
        }
        
        // Check rate limit
        if (!$this->apiKeyManager->checkRateLimit($apiKeyData['id'])) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Rate limit exceeded'
                ]
            ], 429);
        }
        
        // Add API key data to request
        $request['apiKey'] = $apiKeyData;
        
        return $next($request);
    }
}

/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing headers
 */
class CorsMiddleware {
    private array $config;
    
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
            'max_age' => 86400 // 24 hours
        ], $config);
    }
    
    /**
     * Create middleware callable
     */
    public function __invoke(array $request, callable $next): mixed {
        // Set CORS headers
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $this->config['allowed_origins'];
        
        if (in_array('*', $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: *");
            // Do NOT send Allow-Credentials with wildcard origin (browsers reject it)
        } elseif (!empty($origin) && in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header("Access-Control-Allow-Credentials: true");
        }
        
        header("Access-Control-Allow-Methods: " . implode(', ', $this->config['allowed_methods']));
        header("Access-Control-Allow-Headers: " . implode(', ', $this->config['allowed_headers']));
        header("Access-Control-Max-Age: {$this->config['max_age']}");
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        return $next($request);
    }
}

/**
 * Logging Middleware
 * 
 * Logs all incoming requests
 */
class LoggingMiddleware {
    /**
     * Create middleware callable
     */
    public function __invoke(array $request, callable $next): mixed {
        // Log request
        $logEntry = sprintf(
            "[%s] %s %s - IP: %s",
            date('Y-m-d H:i:s'),
            $request['method'] ?? $_SERVER['REQUEST_METHOD'],
            $request['path'] ?? $_SERVER['REQUEST_URI'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        error_log($logEntry);
        
        return $next($request);
    }
}

/**
 * Rate Limiting Middleware
 * 
 * Simple rate limiting by IP address
 */
class RateLimitingMiddleware {
    private mixed $db;
    private int $maxRequests;
    private int $windowSeconds;
    
    public function __construct(int $maxRequests = 100, int $windowSeconds = 3600) {
        $this->db = Database::getInstance();
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }
    
    /**
     * Create middleware callable
     */
    public function __invoke(array $request, callable $next): mixed {
        $ip = $_SERVER['REMOTE_ADDR'];
        $windowStart = date('Y-m-d H:i:s', strtotime('-' . $this->windowSeconds . ' seconds'));
        
        // Count requests in window
        $count = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM api_requests WHERE ip = ? AND created_at > ?",
            [$ip, $windowStart]
        );
        
        if ($count && $count['count'] >= $this->maxRequests) {
            Response::json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.'
                ]
            ], 429);
        }
        
        // Log request
        $this->db->insert('api_requests', [
            'ip' => $ip,
            'method' => $request['method'] ?? $_SERVER['REQUEST_METHOD'],
            'path' => $request['path'] ?? $_SERVER['REQUEST_URI']
        ]);
        
        return $next($request);
    }
}
