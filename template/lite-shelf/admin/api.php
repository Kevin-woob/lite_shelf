<?php
/**
 * Admin API Routes
 * 
 * Handles all administrative API endpoints
 * Routes via query parameter: ?route=/users
 * Protected by API key authentication (requires admin key)
 */
error_reporting(0);
ini_set('display_errors', 0);

session_start();

require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Response.php';
require_once __DIR__ . '/../lib/AuthManager.php';
require_once __DIR__ . '/../lib/ApiKeyManager.php';
require_once __DIR__ . '/../lib/StorageManager.php';
require_once __DIR__ . '/../lib/CollectionReference.php';
require_once __DIR__ . '/../lib/DocumentReference.php';
require_once __DIR__ . '/../lib/AccessControl.php';

// Parse route - may contain embedded query string like /storage/folders?parent_path=xxx
$rawRoute = $_GET['route'] ?? '';
$routeParts = explode('?', $rawRoute, 2);
$route = $routeParts[0];

// If route contains query params, merge them into $_GET
if (isset($routeParts[1])) {
    parse_str($routeParts[1], $routeParams);
    $_GET = array_merge($_GET, $routeParams);
}

$method = $_SERVER['REQUEST_METHOD'];

// Routes that don't require admin API key auth
$publicRoutes = ['/login', '/validate-admin-key', '/session-key'];

// Routes that require admin access (management operations)
$adminOnlyRoutes = [
    '/users', '/stats',
    '/api-keys', '/api-keys/revoke', '/api-keys/set-admin',
    '/api-keys/grant-collection', '/api-keys/revoke-collection',
    '/api-keys/grant-folder', '/api-keys/revoke-folder', '/api-keys/permissions',
    '/storage/upload',
];

if (!in_array($route, $publicRoutes, true)) {
    // Check session first (for logged-in admin users)
    if (isset($_SESSION['admin_api_key']) && !empty($_SESSION['admin_api_key'])) {
        $apiKeyManager = new ApiKeyManager();
        $adminKeyData = $apiKeyManager->validateKey($_SESSION['admin_api_key']);
        if (!$adminKeyData || empty($adminKeyData['is_admin'])) {
            // Session key is invalid, clear it
            unset($_SESSION['admin_api_key']);
            $_SESSION = [];
            session_destroy();
            Response::json(['success' => false, 'error' => 'Session expired'], 401);
        }
    } else {
        // Check API key from header
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if (!$apiKey) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'API key required']
            ], 401);
        }
        
        $apiKeyManager = new ApiKeyManager();
        $adminKeyData = $apiKeyManager->validateKey($apiKey);
        if (!$adminKeyData) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Invalid API key']
            ], 401);
        }
        
        // Check if this route requires admin access
        $requiresAdmin = false;
        foreach ($adminOnlyRoutes as $adminRoute) {
            if ($route === $adminRoute || str_starts_with($route, $adminRoute . '/')) {
                $requiresAdmin = true;
                break;
            }
        }
        
        if ($requiresAdmin && empty($adminKeyData['is_admin'])) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Admin access required']
            ], 403);
        }
    }
}

try {
    // Auth routes (no API key check)
    if ($route === '/login' && $method === 'POST') {
        handleAdminLogin();
    } elseif ($route === '/validate-admin-key' && $method === 'POST') {
        handleValidateAdminKey();
    } elseif ($route === '/session-key' && $method === 'GET') {
        handleSessionKey();
    } elseif ($route === '/logout' && $method === 'POST') {
        handleAdminLogout();
    // Stats and data routes
    } elseif ($route === '/stats' && $method === 'GET') {
        handleStats($adminKeyData);
    } elseif ($route === '/users' && $method === 'GET') {
        handleListUsers();
    } elseif ($route === '/users' && $method === 'POST') {
        handleCreateUser();
    } elseif (preg_match('#^/users/(\d+)$#', $route, $matches) && $method === 'DELETE') {
        handleDeleteUser((int)$matches[1]);
    } elseif ($route === '/api-keys' && $method === 'GET') {
        handleListApiKeys();
    } elseif ($route === '/api-keys' && $method === 'POST') {
        handleCreateApiKey();
    } elseif (preg_match('#^/api-keys/(\d+)/revoke$#', $route, $matches) && $method === 'POST') {
        handleRevokeApiKey((int)$matches[1]);
    } elseif (preg_match('#^/api-keys/(\d+)/set-admin$#', $route, $matches) && $method === 'POST') {
        handleSetAdminKey((int)$matches[1]);
    // Permission management routes
    } elseif (preg_match('#^/api-keys/(\d+)/grant-collection$#', $route, $matches) && $method === 'POST') {
        handleGrantCollectionAccess((int)$matches[1]);
    } elseif (preg_match('#^/api-keys/(\d+)/revoke-collection$#', $route, $matches) && $method === 'POST') {
        handleRevokeCollectionAccess((int)$matches[1]);
    } elseif (preg_match('#^/api-keys/(\d+)/grant-folder$#', $route, $matches) && $method === 'POST') {
        handleGrantFolderAccess((int)$matches[1]);
    } elseif (preg_match('#^/api-keys/(\d+)/revoke-folder$#', $route, $matches) && $method === 'POST') {
        handleRevokeFolderAccess((int)$matches[1]);
    } elseif (preg_match('#^/api-keys/(\d+)/permissions$#', $route, $matches) && $method === 'GET') {
        handleGetKeyPermissions((int)$matches[1]);
    // Folder CRUD routes (before file routes to avoid conflicts)
    } elseif ($route === '/storage/folders/all-paths' && $method === 'GET') {
        handleListAllFolderPaths();
    } elseif ($route === '/storage/folders' && $method === 'GET') {
        handleListFolders($adminKeyData);
    } elseif ($route === '/storage/folders' && $method === 'POST') {
        handleCreateFolder($adminKeyData);
    } elseif (preg_match('#^/storage/folders/(.+)/move$#', $route, $matches) && $method === 'POST') {
        handleMoveFolder(urldecode($matches[1]), $adminKeyData);
    } elseif (preg_match('#^/storage/folders/(.+)/copy$#', $route, $matches) && $method === 'POST') {
        handleCopyFolder(urldecode($matches[1]), $adminKeyData);
    } elseif (preg_match('#^/storage/folders/(.+)$#', $route, $matches) && $method === 'PATCH') {
        handleRenameFolder(urldecode($matches[1]), $adminKeyData);
    } elseif (preg_match('#^/storage/folders/(.+)$#', $route, $matches) && $method === 'DELETE') {
        handleDeleteFolder(urldecode($matches[1]), $adminKeyData);
    // File routes
    } elseif ($route === '/storage/files' && $method === 'GET') {
        handleListStorageFiles($adminKeyData);
    } elseif (preg_match('#^/storage/files/(\d+)/move$#', $route, $matches) && $method === 'POST') {
        handleMoveFile((int)$matches[1], $adminKeyData);
    } elseif (preg_match('#^/storage/files/(\d+)/rename$#', $route, $matches) && $method === 'POST') {
        handleRenameFile((int)$matches[1], $adminKeyData);
    } elseif (preg_match('#^/storage/files/(\d+)/copy$#', $route, $matches) && $method === 'POST') {
        handleCopyFile((int)$matches[1], $adminKeyData);
    } elseif (preg_match('#^/storage/files/(\d+)$#', $route, $matches) && $method === 'DELETE') {
        handleDeleteFile((int)$matches[1], $adminKeyData);
    } elseif ($route === '/collections' && $method === 'GET') {
        handleListCollections($adminKeyData);
    } elseif ($route === '/collections' && $method === 'POST') {
        handleCreateCollection($adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)$#', $route, $matches) && $method === 'GET') {
        handleGetCollectionItems($matches[1], $adminKeyData);
    // New storage and collection CRUD routes
    } elseif ($route === '/storage/upload' && $method === 'POST') {
        handleFileUpload($adminKeyData);
    } elseif (preg_match('#^/storage/files/(\d+)/download$#', $route, $matches) && $method === 'GET') {
        handleDownloadFile((int)$matches[1], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)/documents$#', $route, $matches) && $method === 'POST') {
        handleCreateDocument($matches[1], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)/documents/([^/]+)$#', $route, $matches) && $method === 'GET') {
        handleGetDocument($matches[1], $matches[2], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)/documents/([^/]+)$#', $route, $matches) && $method === 'PATCH') {
        handleUpdateDocument($matches[1], $matches[2], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)/documents/([^/]+)$#', $route, $matches) && $method === 'DELETE') {
        handleDeleteDocument($matches[1], $matches[2], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)$#', $route, $matches) && $method === 'PATCH') {
        handleRenameCollection($matches[1], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)/copy$#', $route, $matches) && $method === 'POST') {
        handleCopyCollection($matches[1], $adminKeyData);
    } elseif (preg_match('#^/collections/([^/]+)$#', $route, $matches) && $method === 'DELETE') {
        handleDeleteCollection($matches[1], $adminKeyData);
    } else {
        Response::notFound('Endpoint not found: ' . $route);
    }
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    Response::serverError($e->getMessage());
}

/**
 * Admin login - accepts API key and creates session
 */
function handleAdminLogin(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['api_key'])) {
        Response::badRequest('API key is required');
    }
    
    $apiKeyManager = new ApiKeyManager();
    $keyData = $apiKeyManager->validateKey($data['api_key']);
    
    if (!$keyData || empty($keyData['is_admin'])) {
        Response::json(['success' => false, 'message' => 'Invalid admin key'], 401);
    }
    
    $_SESSION['admin_api_key'] = $data['api_key'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = $keyData['name'];
    
    Response::json(['success' => true, 'name' => $keyData['name']]);
}

/**
 * Validate an admin key (without creating session)
 */
function handleValidateAdminKey(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['api_key'])) {
        Response::json(['success' => false, 'valid' => false]);
    }
    
    $apiKeyManager = new ApiKeyManager();
    $keyData = $apiKeyManager->validateKey($data['api_key']);
    
    if ($keyData && !empty($keyData['is_admin'])) {
        Response::json(['success' => true, 'valid' => true, 'is_admin' => true, 'name' => $keyData['name']]);
    } else {
        Response::json(['success' => true, 'valid' => false, 'is_admin' => false]);
    }
}

/**
 * Return stored admin key for session (used by JS)
 */
function handleSessionKey(): void {
    if (isset($_SESSION['admin_api_key'])) {
        Response::json(['success' => true, 'has_key' => true]);
    } else {
        Response::json(['success' => true, 'has_key' => false]);
    }
}

/**
 * Admin logout
 */
function handleAdminLogout(): void {
    $_SESSION = [];
    session_destroy();
    Response::json(['success' => true]);
}

/**
 * Get dashboard statistics
 */
function handleStats(?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $isAdmin = $apiKeyData && !empty($apiKeyData['is_admin']);

    if ($isAdmin) {
        $stats = [
            'total_users' => (int) $db->count('users'),
            'active_api_keys' => (int) $db->count('api_keys', 'is_active = 1'),
            'total_files' => (int) $db->count('storage_files'),
            'total_collections' => (int) $db->count('data_collections'),
            'total_documents' => (int) $db->count('data_items')
        ];
    } else {
        $keyId = $apiKeyData['id'] ?? 0;
        $accessibleCollections = AccessControl::getAccessibleCollections($keyId);
        $collectionIds = array_map(fn($c) => $c['id'], $accessibleCollections);

        $stats = [
            'total_users' => (int) $db->count('users'),
            'active_api_keys' => (int) $db->count('api_keys', 'is_active = 1'),
            'total_files' => 0, // Would need complex folder-based filtering
            'total_collections' => count($accessibleCollections),
            'total_documents' => !empty($collectionIds) ? (int) $db->count('data_items', 'collection_id IN (' . implode(',', array_fill(0, count($collectionIds), '?')) . ')', $collectionIds) : 0
        ];
    }

    Response::json(['success' => true, 'stats' => $stats]);
}

/**
 * List all users
 */
function handleListUsers(): void {
    $db = Database::getInstance();
    $users = $db->fetchAll(
        "SELECT id, uid, email, email_verified, display_name, created_at, is_active FROM users ORDER BY created_at DESC"
    );
    
    Response::json(['success' => true, 'users' => $users]);
}

/**
 * Create a new user
 */
function handleCreateUser(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        Response::badRequest('Email and password are required');
    }
    
    try {
        $auth = new AuthManager();
        $user = $auth->createUser(
            $data['email'],
            $data['password'],
            ['display_name' => $data['display_name'] ?? null]
        );
        
        Response::json(['success' => true, 'user' => $user], 201);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Delete a user
 */
function handleDeleteUser(int $userId): void {
    $auth = new AuthManager();
    
    if ($auth->deleteUser($userId)) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('User not found');
    }
}

/**
 * List all API keys
 */
function handleListApiKeys(): void {
    $apiKeyManager = new ApiKeyManager();
    $keys = $apiKeyManager->listKeys();
    
    Response::json(['success' => true, 'api_keys' => $keys]);
}

/**
 * Create a new API key
 */
function handleCreateApiKey(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['name'])) {
        Response::badRequest('Name is required');
    }
    
    try {
        $apiKeyManager = new ApiKeyManager();
        $key = $apiKeyManager->generateKey(
            $data['name'],
            null,
            $data['rate_limit'] ?? 1000,
            $data['is_admin'] ?? false
        );
        
        Response::json(['success' => true, 'key' => $key], 201);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Revoke an API key
 */
function handleRevokeApiKey(int $keyId): void {
    $db = Database::getInstance();
    $apiKeyManager = new ApiKeyManager();

    // Prevent revoking the initial admin key
    $keyData = $db->fetchOne("SELECT is_active, is_initial FROM api_keys WHERE id = ?", [$keyId]);
    if (!$keyData) {
        Response::notFound('API key not found');
        return;
    }

    if ($keyData['is_initial']) {
        Response::json(['success' => false, 'message' => 'Cannot revoke the initial admin key. Create another admin key first if you need to change admin access.'], 403);
        return;
    }

    if ($apiKeyManager->revokeKey($keyId)) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('API key not found');
    }
}

/**
 * Set an API key as admin
 */
function handleSetAdminKey(int $keyId): void {
    $db = Database::getInstance();
    $result = $db->update('api_keys', ['is_admin' => 1], 'id = ?', [$keyId]);
    
    if ($result > 0) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('API key not found');
    }
}

/**
 * List uploaded files (from database)
 */
function handleListStorageFiles(?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $isAdmin = $apiKeyData && !empty($apiKeyData['is_admin']);
    $folderPath = $_GET['folder_path'] ?? '';
    $folderPath = rtrim($folderPath, '/') . ($folderPath ? '/' : '');

    if ($isAdmin) {
        if ($folderPath === '') {
            $files = $db->fetchAll(
                "SELECT id, filename_original, filename_stored, folder_path, mime_type, size_bytes, created_at FROM storage_files WHERE folder_path = '' ORDER BY created_at DESC"
            );
        } else {
            $files = $db->fetchAll(
                "SELECT id, filename_original, filename_stored, folder_path, mime_type, size_bytes, created_at FROM storage_files WHERE folder_path = ? ORDER BY created_at DESC",
                [$folderPath]
            );
        }
    } else {
        $keyId = $apiKeyData['id'] ?? 0;
        // Check folder access
        if ($folderPath !== '' && !AccessControl::hasFolderAccess($keyId, $folderPath, 'read')) {
            Response::json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to this folder']
            ], 403);
            return;
        }

        if ($folderPath === '') {
            $files = $db->fetchAll(
                "SELECT id, filename_original, filename_stored, folder_path, mime_type, size_bytes, created_at FROM storage_files WHERE folder_path = '' ORDER BY created_at DESC"
            );
        } else {
            $files = $db->fetchAll(
                "SELECT id, filename_original, filename_stored, folder_path, mime_type, size_bytes, created_at FROM storage_files WHERE folder_path = ? ORDER BY created_at DESC",
                [$folderPath]
            );
        }
    }

    Response::json(['success' => true, 'files' => $files]);
}

/**
 * Delete a file
 */
function handleDeleteFile(int $fileId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $isAdmin = $apiKeyData && !empty($apiKeyData['is_admin']);

    $file = $db->fetchOne("SELECT filename_stored, folder_path FROM storage_files WHERE id = ?", [$fileId]);
    if (!$file) {
        Response::notFound('File not found');
        return;
    }

    if (!$isAdmin && !AccessControl::hasFolderAccess($apiKeyData['id'], $file['folder_path'], 'full')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to delete this file']
        ], 403);
        return;
    }

    // Delete from filesystem
    $config = require __DIR__ . '/../config/settings.php';
    $filePath = $config['uploads_base_path'] . $file['filename_stored'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete from database
    $db->delete('storage_files', 'id = ?', [$fileId]);

    Response::json(['success' => true]);
}

/**
 * List all data collections
 */
function handleListCollections(?array $apiKeyData = null): void {
    $db = Database::getInstance();

    $collections = AccessControl::getAccessibleCollections($apiKeyData['id'] ?? 0);

    // Add document counts
    foreach ($collections as &$collection) {
        $collection['document_count'] = (int) $db->count('data_items', 'collection_id = ?', [$collection['id']]);
    }

    Response::json(['success' => true, 'collections' => $collections]);
}

/**
 * Create a new collection
 */
function handleCreateCollection(?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['name'])) {
        Response::badRequest('Collection name is required');
    }

    $name = trim($data['name']);

    // Validate collection name
    if (!preg_match('/^[a-z0-9_]+$/', $name)) {
        Response::badRequest('Collection name must contain only lowercase letters, numbers, and underscores');
    }

    // Check if collection already exists
    $existing = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$name]);
    if ($existing) {
        Response::badRequest('Collection already exists');
    }

    $description = $data['description'] ?? null;

    $db->insert('data_collections', [
        'name' => $name,
        'description' => $description,
        'schema_config' => null
    ]);

    Response::json(['success' => true, 'collection' => ['name' => $name]]);
}

/**
 * Get items in a collection
 */
function handleGetCollectionItems(string $collectionName, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $collection = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$collectionName]);

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'read')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to this collection']
        ], 403);
        return;
    }

    $items = $db->fetchAll(
        "SELECT id, data, created_at, updated_at FROM data_items WHERE collection_id = ? ORDER BY created_at DESC LIMIT 100",
        [$collection['id']]
    );

    // Decode JSON data
    foreach ($items as &$item) {
        $item['data'] = is_string($item['data']) ? json_decode($item['data'], true) : $item['data'];
    }

    Response::json(['success' => true, 'items' => $items]);
}

/**
 * Handle file upload via multipart/form-data
 */
function handleFileUpload(?array $apiKeyData = null): void {
    if (!isset($_FILES['file'])) {
        Response::badRequest('No file provided');
    }

    $file = $_FILES['file'];
    $storageManager = new StorageManager();

    // Get optional folder path
    $folderPath = $_POST['folder_path'] ?? '';

    // Check folder access
    if ($folderPath !== '' && !AccessControl::hasFolderAccess($apiKeyData['id'], $folderPath, 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to upload to this folder']
        ], 403);
        return;
    }

    $uploadedByKeyId = $apiKeyData['id'] ?? null;

    $result = $storageManager->upload($file, $folderPath, $uploadedByKeyId);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json([
        'success' => true,
        'id' => $result['id'],
        'filename' => $result['filename'],
        'folder_path' => $result['folder_path'] ?? '',
        'url' => $result['url']
    ], 200);
}

/**
 * Download a file by ID
 */
function handleDownloadFile(int $fileId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();

    $file = $db->fetchOne(
        "SELECT id, filename_original, filename_stored, mime_type, folder_path FROM storage_files WHERE id = ?",
        [$fileId]
    );

    if (!$file) {
        Response::notFound('File not found');
        return;
    }

    // Check folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $file['folder_path'], 'read')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to download this file']
        ], 403);
        return;
    }

    $config = require __DIR__ . '/../config/settings.php';
    $filePath = $config['uploads_base_path'] . $file['filename_stored'];

    if (!file_exists($filePath)) {
        Response::notFound('File not found on disk');
        return;
    }

    // Send file with correct headers
    header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . basename($file['filename_original']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache');

    readfile($filePath);
    exit;
}

/**
 * Create a new document in a collection
 */
function handleCreateDocument(string $collectionName, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $collection = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$collectionName]);

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to create documents in this collection']
        ], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        Response::badRequest('Invalid JSON body');
    }

    try {
        $collection = new CollectionReference($collectionName);
        $result = $collection->add($data);

        Response::json(['success' => true, 'id' => $result['id']], 201);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Get a single document from a collection
 */
function handleGetDocument(string $collectionName, string $documentId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $collection = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$collectionName]);

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'read')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to this collection']
        ], 403);
        return;
    }

    try {
        $collection = new CollectionReference($collectionName);
        $docRef = $collection->document($documentId);
        $doc = $docRef->get();

        if ($doc) {
            Response::json(['success' => true, 'document' => $doc]);
        } else {
            Response::notFound('Document not found');
        }
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Update an existing document in a collection
 */
function handleUpdateDocument(string $collectionName, string $documentId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $collection = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$collectionName]);

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to update documents in this collection']
        ], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        Response::badRequest('Invalid JSON body');
    }

    try {
        $collection = new CollectionReference($collectionName);
        $collection->update($documentId, $data);

        Response::json(['success' => true]);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Delete a document from a collection
 */
function handleDeleteDocument(string $collectionName, string $documentId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $collection = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$collectionName]);

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'full')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to delete documents in this collection']
        ], 403);
        return;
    }

    try {
        $collection = new CollectionReference($collectionName);
        $deleted = $collection->delete($documentId);

        if (!$deleted) {
            Response::notFound('Document not found');
            return;
        }

        Response::json(['success' => true]);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Delete a collection and all its documents
 */
function handleDeleteCollection(string $collectionName, ?array $apiKeyData = null): void {
    $db = Database::getInstance();

    // Get collection ID
    $collection = $db->fetchOne(
        "SELECT id FROM data_collections WHERE name = ?",
        [$collectionName]
    );

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'full')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to delete this collection']
        ], 403);
        return;
    }

    try {
        // Delete all documents in the collection
        $db->delete('data_items', 'collection_id = ?', [$collection['id']]);

        // Delete the collection itself
        $db->delete('data_collections', 'id = ?', [$collection['id']]);

        Response::json(['success' => true]);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

// ========== FOLDER & FILE MOVE/COPY HANDLERS ==========

/**
 * Rename a collection
 */
function handleRenameCollection(string $collectionName, ?array $apiKeyData = null): void {
    $db = Database::getInstance();

    // Get collection ID
    $collection = $db->fetchOne(
        "SELECT id FROM data_collections WHERE name = ?",
        [$collectionName]
    );

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'full')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to rename this collection']
        ], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['name'])) {
        Response::badRequest('New collection name is required');
    }

    $newName = trim($data['name']);

    // Validate new name
    if (!preg_match('/^[a-z0-9_]+$/', $newName)) {
        Response::badRequest('Collection name must contain only lowercase letters, numbers, and underscores');
    }

    if ($newName === $collectionName) {
        Response::json(['success' => true, 'message' => 'Name unchanged']);
        return;
    }

    // Check if new name already exists
    $existing = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$newName]);
    if ($existing) {
        Response::badRequest('Collection already exists');
    }

    try {
        $db->query(
            $db->prefixSql("UPDATE data_collections SET name = ? WHERE id = ?"),
            [$newName, $collection['id']]
        );

        Response::json(['success' => true, 'oldName' => $collectionName, 'newName' => $newName]);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * Copy a collection (duplicate with all documents)
 */
function handleCopyCollection(string $collectionName, ?array $apiKeyData = null): void {
    $db = Database::getInstance();

    // Get source collection ID
    $collection = $db->fetchOne(
        "SELECT id, name, description, schema_config FROM data_collections WHERE name = ?",
        [$collectionName]
    );

    if (!$collection) {
        Response::notFound('Collection not found');
        return;
    }

    if (!AccessControl::hasCollectionAccess($apiKeyData['id'], $collection['id'], 'full')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to copy this collection']
        ], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['new_name'])) {
        Response::badRequest('New collection name is required');
    }

    $newName = trim($data['new_name']);

    // Validate new name
    if (!preg_match('/^[a-z0-9_]+$/', $newName)) {
        Response::badRequest('Collection name must contain only lowercase letters, numbers, and underscores');
    }

    if ($newName === $collectionName) {
        Response::badRequest('New collection name must be different from source name');
    }

    // Check if new name already exists
    $existing = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$newName]);
    if ($existing) {
        Response::badRequest('Collection already exists');
    }

    try {
        // Create the new collection
        $db->insert('data_collections', [
            'name' => $newName,
            'description' => $collection['description'] ?? null,
            'schema_config' => $collection['schema_config']
        ]);

        // Get the new collection ID
        $newCollection = $db->fetchOne("SELECT id FROM data_collections WHERE name = ?", [$newName]);

        // Copy all documents
        $items = $db->fetchAll(
            "SELECT id, data FROM data_items WHERE collection_id = ?",
            [$collection['id']]
        );

        foreach ($items as $item) {
            $db->insert('data_items', [
                'collection_id' => $newCollection['id'],
                'data' => $item['data']
            ]);
        }

        Response::json([
            'success' => true,
            'sourceName' => $collectionName,
            'newName' => $newName,
            'documentsCopied' => count($items)
        ]);
    } catch (Exception $e) {
        Response::badRequest($e->getMessage());
    }
}

/**
 * List folders at a given parent path
 */
function handleListFolders(?array $apiKeyData = null): void {
    $parentPath = $_GET['parent_path'] ?? '';
    $storageManager = new StorageManager();
    $folders = AccessControl::getAccessibleFolders($apiKeyData['id'] ?? 0, $parentPath);

    Response::json(['success' => true, 'folders' => $folders]);
}

/**
 * Create a new folder
 */
function handleCreateFolder(?array $apiKeyData = null): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['name'])) {
        Response::badRequest('Folder name is required');
    }

    $name = $data['name'];
    $parentPath = $data['parent_path'] ?? '';

    // Check folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $parentPath, 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to create folders here']
        ], 403);
        return;
    }

    $createdByKeyId = $apiKeyData['id'] ?? null;

    $storageManager = new StorageManager();
    $result = $storageManager->createFolder($name, $parentPath, $createdByKeyId);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'folder' => $result], 201);
}

/**
 * Rename a folder
 */
function handleRenameFolder(string $path, ?array $apiKeyData = null): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['name'])) {
        Response::badRequest('New folder name is required');
    }

    // Check folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $path, 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to rename this folder']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->renameFolder($path, $data['name']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'folder' => $result]);
}

/**
 * Delete a folder recursively
 */
function handleDeleteFolder(string $path, ?array $apiKeyData = null): void {
    // Check folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $path, 'full')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to delete this folder']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->deleteFolder($path);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true]);
}

/**
 * Move a folder to a different parent
 */
function handleMoveFolder(string $path, ?array $apiKeyData = null): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['parent_path'])) {
        Response::badRequest('Destination parent path is required');
    }

    // Check source folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $path, 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to move this folder']
        ], 403);
        return;
    }

    // Check destination folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $data['parent_path'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to move folder to destination']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->moveFolder($path, $data['parent_path']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'folder' => $result]);
}

/**
 * Copy a folder to a different parent path
 */
function handleCopyFolder(string $path, ?array $apiKeyData = null): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['parent_path'])) {
        Response::badRequest('Destination parent path is required');
    }

    // Check source folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $path, 'read')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to copy this folder']
        ], 403);
        return;
    }

    // Check destination folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $data['parent_path'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to copy folder to destination']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->copyFolder($path, $data['parent_path']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'folder' => $result]);
}

/**
 * Move a file to a different folder
 */
function handleMoveFile(int $fileId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['folder_path'])) {
        Response::badRequest('Destination folder path is required');
    }

    // Get file info for source folder check
    $file = $db->fetchOne("SELECT folder_path FROM storage_files WHERE id = ?", [$fileId]);
    if (!$file) {
        Response::notFound('File not found');
        return;
    }

    // Check source and destination folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $file['folder_path'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to move this file']
        ], 403);
        return;
    }
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $data['folder_path'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to move file to destination folder']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->moveFile($fileId, $data['folder_path']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'file' => $result]);
}

/**
 * Rename a file
 */
function handleRenameFile(int $fileId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['name'])) {
        Response::badRequest('New file name is required');
    }

    // Get file info for folder access check
    $file = $db->fetchOne("SELECT folder_path FROM storage_files WHERE id = ?", [$fileId]);
    if (!$file) {
        Response::notFound('File not found');
        return;
    }

    // Check folder access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $file['folder_path'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to rename this file']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->renameFile($fileId, $data['name']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'file' => $result]);
}

/**
 * Copy a file to a different folder
 */
function handleCopyFile(int $fileId, ?array $apiKeyData = null): void {
    $db = Database::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['folder_path'])) {
        Response::badRequest('Destination folder path is required');
    }

    // Get file info for source folder check
    $file = $db->fetchOne("SELECT folder_path FROM storage_files WHERE id = ?", [$fileId]);
    if (!$file) {
        Response::notFound('File not found');
        return;
    }

    // Check source folder read access and destination write access
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $file['folder_path'], 'read')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to read this file']
        ], 403);
        return;
    }
    if (!AccessControl::hasFolderAccess($apiKeyData['id'], $data['folder_path'], 'write')) {
        Response::json([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied to copy file to destination folder']
        ], 403);
        return;
    }

    $storageManager = new StorageManager();
    $result = $storageManager->copyFile($fileId, $data['folder_path']);

    if (isset($result['error'])) {
        Response::badRequest($result['error']);
    }

    Response::json(['success' => true, 'file' => $result], 201);
}

// ========== PERMISSION MANAGEMENT HANDLERS ==========

/**
 * Grant collection access to an API key
 */
function handleGrantCollectionAccess(int $keyId): void {
    $db = Database::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['collection_id'])) {
        Response::badRequest('collection_id is required');
    }

    $collectionId = (int) $data['collection_id'];
    $accessLevel = $data['access_level'] ?? 'read';

    if (AccessControl::grantCollectionAccess($keyId, $collectionId, $accessLevel)) {
        Response::json(['success' => true]);
    } else {
        Response::badRequest('Failed to grant access. Key or collection may not exist.');
    }
}

/**
 * Revoke collection access for an API key
 */
function handleRevokeCollectionAccess(int $keyId): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['collection_id'])) {
        Response::badRequest('collection_id is required');
    }

    $collectionId = (int) $data['collection_id'];

    if (AccessControl::revokeCollectionAccess($keyId, $collectionId)) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('Permission not found');
    }
}

/**
 * Grant folder access to an API key
 */
function handleGrantFolderAccess(int $keyId): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['folder_path'])) {
        Response::badRequest('folder_path is required');
    }

    $folderPath = $data['folder_path'];
    $accessLevel = $data['access_level'] ?? 'read';

    if (AccessControl::grantFolderAccess($keyId, $folderPath, $accessLevel)) {
        Response::json(['success' => true]);
    } else {
        Response::badRequest('Failed to grant access. Key may not exist.');
    }
}

/**
 * Revoke folder access for an API key
 */
function handleRevokeFolderAccess(int $keyId): void {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['folder_path'])) {
        Response::badRequest('folder_path is required');
    }

    $folderPath = $data['folder_path'];

    if (AccessControl::revokeFolderAccess($keyId, $folderPath)) {
        Response::json(['success' => true]);
    } else {
        Response::notFound('Permission not found');
    }
}

/**
 * Get all permissions for an API key
 */
function handleGetKeyPermissions(int $keyId): void {
    $permissions = AccessControl::getKeyPermissions($keyId);
    Response::json(['success' => true, 'permissions' => $permissions]);
}

/**
 * List all folder paths (for permissions UI)
 */
function handleListAllFolderPaths(): void {
    $db = Database::getInstance();
    $folders = $db->fetchAll(
        "SELECT path, name, parent_path FROM storage_folders ORDER BY path"
    );
    Response::json(['success' => true, 'folders' => $folders]);
}
