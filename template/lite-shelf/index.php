<?php
// Clear PHP OPcache to ensure latest code is used
if (function_exists('opcache_reset')) {
    static $cacheCleared = false;
    if (!$cacheCleared) {
        opcache_reset();
        $cacheCleared = true;
    }
}

/**
 * Lite_Shelf - Main Entry Point
 * 
 * This is the REST API endpoint that wraps all Lite_Shelf operations
 */

require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/lib/Response.php';
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/StorageManager.php';
require_once __DIR__ . '/lib/ApiKeyManager.php';
require_once __DIR__ . '/lib/AuthManager.php';
require_once __DIR__ . '/lib/Middleware.php';
require_once __DIR__ . '/lib/CollectionReference.php';
require_once __DIR__ . '/lib/DocumentReference.php';
require_once __DIR__ . '/lib/Query.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip the script's own directory from the path to get the relative API path
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptDir = dirname($scriptName);
$path = $requestUri;
if ($scriptDir !== '/' && $scriptDir !== '\\') {
    $path = preg_replace('#^' . preg_quote($scriptDir, '#') . '#', '', $path);
}

// Remove index.php from the path (handles /index.php, /index.php/route, etc.)
$path = preg_replace('#^/index\.php#', '', $path);

// Remove leading slash for matching
$path = ltrim($path, '/');

// Route requests
try {
    // Health and status routes
    if ($path === '' || $path === '/') {
        $response = Response::json([
            'status' => 'ok',
            'message' => 'Lite_Shelf API',
            'version' => '1.0.0'
        ]);
    } elseif ($path === 'health') {
        $response = Response::json(['status' => 'healthy']);

    // Notification routes
    } elseif ($path === 'notifications' && $method === 'GET') {
        handleListNotifications();
    } elseif ($path === 'notifications' && $method === 'POST') {
        handleCreateNotification();
    } elseif (preg_match('#^notifications/(\d+)$#', $path, $matches) && $method === 'DELETE') {
        handleDeleteNotification((int)$matches[1]);
    } elseif (preg_match('#^notifications/(\d+)/read$#', $path, $matches) && $method === 'POST') {
        handleMarkNotificationRead((int)$matches[1]);

    // Data collection routes (require API key)
    } elseif (preg_match('#^collections/([^/]+)/documents$#', $path, $matches)) {
        $apiKeyData = validateApiKey();
        $collectionName = Database::validateCollectionName($matches[1]);
        $collection = new CollectionReference($collectionName);
        
        if ($method === 'GET') {
            // Check for query parameters
            $hasFilters = isset($_GET['field']) || isset($_GET['orderBy']) || isset($_GET['limit']);
            
            if ($hasFilters) {
                // Build query with filters
                $query = new Query($collectionName, $collection->getId());
                
                // Apply where filter
                if (isset($_GET['field']) && isset($_GET['operator']) && isset($_GET['value'])) {
                    $query = $query->where($_GET['field'], $_GET['operator'], $_GET['value']);
                }
                
                // Apply orderBy
                if (isset($_GET['orderBy'])) {
                    $query = $query->orderBy($_GET['orderBy'], $_GET['order'] ?? 'ASC');
                }
                
                // Apply limit
                if (isset($_GET['limit'])) {
                    $query = $query->limit((int)$_GET['limit']);
                }
                
                Response::json(['success' => true, 'documents' => $query->get()]);
            } else {
                Response::json(['success' => true, 'documents' => $collection->get()]);
            }
        } elseif ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $collection->add($data ?? []);
            Response::json(['success' => true, 'id' => $result['id']], 201);
        }
    } elseif (preg_match('#^collections/([^/]+)/documents/([^/]+)$#', $path, $matches)) {
        $apiKeyData = validateApiKey();
        $collectionName = Database::validateCollectionName($matches[1]);
        $docId = $matches[2];
        $collection = new CollectionReference($collectionName);
        $docRef = $collection->document($docId);
        
        if ($method === 'GET') {
            $doc = $docRef->get();
            if ($doc) {
                Response::json(['success' => true, 'document' => $doc]);
            } else {
                Response::notFound('Document not found');
            }
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $docRef->set($data ?? []);
            Response::json(['success' => true]);
        } elseif ($method === 'PATCH') {
            $data = json_decode(file_get_contents('php://input'), true);
            $docRef->update($data ?? []);
            Response::json(['success' => true]);
        } elseif ($method === 'DELETE') {
            if ($docRef->delete()) {
                Response::json(['success' => true]);
            } else {
                Response::notFound('Document not found');
            }
        }
    } elseif (preg_match('#^collections/([^/]+)$#', $path, $matches)) {
        $apiKeyData = validateApiKey();
        $collectionName = Database::validateCollectionName($matches[1]);
        $collection = new CollectionReference($collectionName);
        if ($method === 'GET') {
            Response::json(['success' => true, 'documents' => $collection->get()]);
        } elseif ($method === 'DELETE') {
            Response::json(['success' => true, 'message' => 'Collection deleted']);
        }

    // Storage routes
    } elseif ($path === 'storage/upload' && $method === 'POST') {
        handleUpload();
    } elseif (preg_match('#^storage/files/([^/]+)$#', $path, $matches) && $method === 'GET') {
        handleGetFile($matches[1]);
    } elseif (preg_match('#^storage/files/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
        handleDeleteFile($matches[1]);
    } elseif (preg_match('#^storage/serve/(.+)$#', $path, $matches) && $method === 'GET') {
        handleServeFile($matches[1]);

    } else {
        Response::notFound('Endpoint not found: ' . $path);
    }

} catch (InvalidArgumentException $e) {
    Response::json(['success' => false, 'error' => $e->getMessage()], 400);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    Response::serverError($e->getMessage());
}

/**
 * Validate API key from X-API-Key header
 */
function validateApiKey(): array {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
    if (!$apiKey) {
        Response::unauthorized('API key required');
    }

    $apiKeyManager = new ApiKeyManager();
    $apiKeyData = $apiKeyManager->validateKey($apiKey);

    if (!$apiKeyData) {
        Response::unauthorized('Invalid API key');
    }

    return $apiKeyData;
}

/**
 * List all notifications
 */
function handleListNotifications(): void {
    $db = Database::getInstance();
    $limit = $_GET['limit'] ?? 100;
    $offset = $_GET['offset'] ?? 0;
    $unreadOnly = isset($_GET['unread']);
    
    $where = $unreadOnly ? 'is_read = 0' : '1=1';
    $notifications = $db->fetchAll(
        "SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [(int)$limit, (int)$offset]
    );
    
    Response::json(['success' => true, 'notifications' => $notifications]);
}

/**
 * Create a new notification
 */
function handleCreateNotification(): void {
    $apiKeyData = validateApiKey();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['message'])) {
        Response::badRequest('Message is required');
    }

    $db = Database::getInstance();
    $id = $db->insert('notifications', [
        'target_user_id' => $data['target_user_id'] ?? null,
        'sender_key_id' => $apiKeyData['id'],
        'message' => $data['message'],
        'data' => isset($data['data']) ? json_encode($data['data']) : null,
        'is_read' => 0
    ]);

    Response::json(['success' => true, 'id' => $id], 201);
}

/**
 * Delete a notification
 */
function handleDeleteNotification(int $notificationId): void {
    $db = Database::getInstance();
    $result = $db->delete('notifications', 'id = ?', [$notificationId]);
    
    if ($result > 0) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('Notification not found');
    }
}

/**
 * Mark a notification as read
 */
function handleMarkNotificationRead(int $notificationId): void {
    $db = Database::getInstance();
    $result = $db->update('notifications', ['is_read' => 1], 'id = ?', [$notificationId]);
    
    if ($result > 0) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('Notification not found');
    }
}

/**
 * Handle file upload
 */
function handleUpload(): void {
    $apiKeyData = validateApiKey();

    if (!isset($_FILES['file'])) {
        Response::badRequest('No file uploaded');
    }

    $storage = new StorageManager();
    $uploadedFile = $_FILES['file'];
    $folderPath = $_POST['folder_path'] ?? '';

    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        Response::badRequest('Upload error: ' . $uploadedFile['error']);
    }

    $result = $storage->upload($uploadedFile, $folderPath, (int)$apiKeyData['id']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json([
        'success' => true, 
        'id' => $result['id'],
        'filename' => $result['filename'], 
        'folder_path' => $result['folder_path'] ?? '',
        'url' => $result['url'], 
        'size' => $result['size'], 
        'mime_type' => $result['mime_type']
    ], 201);
}

/**
 * Get a file
 */
function handleGetFile(string $filename): void {
    $storage = new StorageManager();
    $content = $storage->getFile($filename);

    if ($content !== null && $content !== false) {
        header('Content-Type: application/octet-stream');
        echo $content;
    } else {
        Response::notFound('File not found');
    }
}

/**
 * Serve a file inline with its real MIME type (for images, CSS, JS, HTML, etc.)
 */
function handleServeFile(string $storedName): void {
    $db = Database::getInstance();
    $file = $db->fetchOne(
        "SELECT filename_stored, filename_original, mime_type FROM storage_files WHERE filename_stored = ?",
        [$storedName]
    );

    if (!$file) {
        Response::notFound('File not found');
        return;
    }

    $config = require __DIR__ . '/config/settings.php';
    $filePath = $config['uploads_base_path'] . $file['filename_stored'];

    if (!file_exists($filePath) || !is_readable($filePath)) {
        Response::notFound('File not found on disk');
        return;
    }

    $mime = $file['mime_type'];
    if (!$mime) {
        $mime = function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream';
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($file['filename_original']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
}

/**
 * Delete a file
 */
function handleDeleteFile(string $filename): void {
    $storage = new StorageManager();
    
    if ($storage->deleteFile($filename)) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('File not found');
    }
}