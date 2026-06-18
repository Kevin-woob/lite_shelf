<?php
/**
 * Response Helper Class
 * 
 * Provides standardized HTTP response methods
 */

class Response {
    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success(mixed $data = null, array $meta = null, int $statusCode = 200): void {
        $response = ['success' => true];
        if ($data !== null) $response['data'] = $data;
        if ($meta !== null) $response['meta'] = $meta;
        self::json($response, $statusCode);
    }
    
    /**
     * Send error response
     */
    public static function error(string $code, string $message, int $statusCode = 400, mixed $details = null): void {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        if ($details !== null) $response['error']['details'] = $details;
        self::json($response, $statusCode);
    }
    
    /**
     * Send not found response
     */
    public static function notFound(string $resource = 'Resource'): void {
        self::error('NOT_FOUND', "{$resource} not found", 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::error('UNAUTHORIZED', $message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): void {
        self::error('FORBIDDEN', $message, 403);
    }
    
    /**
     * Send bad request response
     */
    public static function badRequest(string $message = 'Bad Request'): void {
        self::error('BAD_REQUEST', $message, 400);
    }
    
    /**
     * Send server error response
     */
    public static function serverError(string $message = 'Internal Server Error'): void {
        self::error('SERVER_ERROR', $message, 500);
    }
}
