<?php
/**
 * Dashboard API Endpoints
 * 
 * Handles all API requests for the app management dashboard.
 * Routes via ?action=list|create|get|update|delete|stats|login|logout
 */

// Include required files
require_once __DIR__ . '/../template/lite-shelf/lib/Database.php';
require_once __DIR__ . '/../template/lite-shelf/lib/Response.php';
require_once __DIR__ . '/../lib/AppManager.php';

// Start session for auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get action from query parameter
$action = $_GET['action'] ?? '';

// Routes that don't require authentication
$publicActions = ['login', 'logout'];

// Check authentication for protected routes
if (!in_array($action, $publicActions, true) && ($_SESSION['dashboard_logged_in'] !== true)) {
    Response::unauthorized('Please login to access the dashboard API');
}

// Route the request
try {
    match ($action) {
        'list' => handleList(),
        'create' => handleCreate(),
        'get' => handleGet(),
        'update' => handleUpdate(),
        'delete' => handleDelete(),
        'stats' => handleStats(),
        'login' => handleLogin(),
        'logout' => handleLogout(),
        default => Response::badRequest('Invalid action. Expected: list, create, get, update, delete, stats, login, logout'),
    };
} catch (Throwable $e) {
    error_log("Dashboard API Error [{$action}]: " . $e->getMessage());
    Response::serverError('An error occurred while processing your request');
}

/**
 * GET ?action=list - List all apps
 */
function handleList(): void {
    $manager = new AppManager();
    $apps = $manager->listApps();
    
    // Decode config JSON for each app
    foreach ($apps as &$app) {
        if ($app['config'] !== null) {
            $app['config'] = json_decode($app['config'], true);
        }
    }
    
    Response::success($apps);
}

/**
 * POST ?action=create - Create new app
 */
function handleCreate(): void {
    $input = getInput();
    
    if (empty($input['name'])) {
        Response::badRequest('App name is required');
    }
    
    $manager = new AppManager();
    $config = $input['config'] ?? [];
    
    try {
        $id = $manager->createApp($input['name'], $config);
        $app = $manager->getApp($id);
        
        if ($app && $app['config'] !== null) {
            $app['config'] = json_decode($app['config'], true);
        }
        
        Response::success($app, ['message' => 'App created successfully'], 201);
    } catch (InvalidArgumentException $e) {
        Response::badRequest($e->getMessage());
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            Response::badRequest('An app with this name already exists');
        }
        throw $e;
    }
}

/**
 * GET ?action=get&id=X - Get single app details
 */
function handleGet(): void {
    $id = $_GET['id'] ?? null;
    
    if ($id === null || !is_numeric($id)) {
        Response::badRequest('App ID is required');
    }
    
    $manager = new AppManager();
    $app = $manager->getApp((int) $id);
    
    if ($app === null) {
        Response::notFound('App');
    }
    
    if ($app['config'] !== null) {
        $app['config'] = json_decode($app['config'], true);
    }
    
    Response::success($app);
}

/**
 * POST ?action=update&id=X - Update app configuration
 */
function handleUpdate(): void {
    $id = $_GET['id'] ?? null;
    
    if ($id === null || !is_numeric($id)) {
        Response::badRequest('App ID is required');
    }
    
    $manager = new AppManager();
    $existing = $manager->getApp((int) $id);
    
    if ($existing === null) {
        Response::notFound('App');
    }
    
    $input = getInput();
    
    if (empty($input)) {
        Response::badRequest('No data provided to update');
    }
    
    try {
        $manager->updateApp((int) $id, $input);
        $app = $manager->getApp((int) $id);
        
        if ($app && $app['config'] !== null) {
            $app['config'] = json_decode($app['config'], true);
        }
        
        Response::success($app, ['message' => 'App updated successfully']);
    } catch (InvalidArgumentException $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * POST ?action=delete&id=X - Delete app
 */
function handleDelete(): void {
    $id = $_GET['id'] ?? null;
    
    if ($id === null || !is_numeric($id)) {
        Response::badRequest('App ID is required');
    }
    
    $manager = new AppManager();
    $existing = $manager->getApp((int) $id);
    
    if ($existing === null) {
        Response::notFound('App');
    }
    
    $manager->deleteApp((int) $id);
    
    Response::success(null, ['message' => 'App deleted successfully']);
}

/**
 * GET ?action=stats - Get dashboard stats
 */
function handleStats(): void {
    $manager = new AppManager();
    $stats = $manager->getStats();
    
    Response::success($stats);
}

/**
 * POST ?action=login - Login with username/password
 */
function handleLogin(): void {
    $input = getInput();
    
    if (empty($input['username']) || empty($input['password'])) {
        Response::badRequest('Username and password are required');
    }
    
    $config = require __DIR__ . '/../config/admin.php';
    
    if ($input['username'] !== $config['username']) {
        Response::unauthorized('Invalid username or password');
    }
    
    if (!password_verify($input['password'], $config['password_hash'])) {
        Response::unauthorized('Invalid username or password');
    }
    
    $_SESSION['dashboard_logged_in'] = true;
    $_SESSION['dashboard_user'] = $config['username'];
    
    Response::success(null, ['message' => 'Login successful']);
}

/**
 * POST ?action=logout - Destroy session
 */
function handleLogout(): void {
    $_SESSION['dashboard_logged_in'] = false;
    unset($_SESSION['dashboard_user']);
    session_destroy();
    
    Response::success(null, ['message' => 'Logged out successfully']);
}

/**
 * Get input from POST body (JSON or form data)
 */
function getInput(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
    
    return $_POST;
}
