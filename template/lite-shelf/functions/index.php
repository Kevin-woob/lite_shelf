<?php
/**
 * Cloud Functions Emulation
 * 
 * Provides Lite_Shelf serverless function execution
 * Secured with API key validation and path traversal protection
 */

require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Response.php';
require_once __DIR__ . '/../lib/ApiKeyManager.php';

$method = $_SERVER['REQUEST_METHOD'];

// API key validation required
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
if (!$apiKey) {
    Response::unauthorized('API key required');
}

$apiKeyManager = new ApiKeyManager();
$keyData = $apiKeyManager->validateKey($apiKey);
if (!$keyData) {
    Response::unauthorized('Invalid API key');
}

// Get function name from path
$path = $_SERVER['PATH_INFO'] ?? $_GET['function'] ?? '';
$path = trim($path, '/');

if (empty($path)) {
    Response::json([
        'success' => true,
        'message' => 'Cloud Functions endpoint',
        'triggers' => ['https', 'auth', 'db', 'storage']
    ]);
    return;
}

// Validate function name - prevent directory traversal and injection
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $path)) {
    Response::badRequest('Invalid function name');
}

$functionPath = realpath(__DIR__ . '/' . $path . '.php');
$functionsDir = realpath(__DIR__);

// Ensure the resolved path is within the functions directory
if ($functionPath === false || strpos($functionPath, $functionsDir) !== 0) {
    Response::notFound('Function not found');
}

if (!file_exists($functionPath)) {
    Response::notFound('Function not found');
}

// Set up payload for the function
$payload = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    // Execute the function file
    $result = require $functionPath;
    
    if (is_array($result)) {
        Response::json(['success' => true, 'data' => $result]);
    } elseif (is_string($result)) {
        $decoded = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            Response::json(['success' => true, 'data' => $decoded]);
        } else {
            Response::json(['success' => true, 'data' => ['result' => $result]]);
        }
    } else {
        Response::json(['success' => true, 'data' => ['result' => $result]]);
    }
} catch (Exception $e) {
    error_log("Function Error: " . $e->getMessage());
    Response::serverError($e->getMessage());
}
