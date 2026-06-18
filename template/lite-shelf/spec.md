# Lite_Shelf - Technical Specification

**Version:** 1.0  
**Date:** 2024-01-XX  
**Author:** SoftwareDevAgent  

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Security](#security)
6. [Admin Panel](#admin-panel)
7. [Client SDK Examples](#client-sdk-examples)

---

## Overview

A simplified Lite_Shelf system built on PHP/MySQL for shared hosting environments. Provides core features without requiring WebSocket support or specialized server infrastructure.

### Core Features

| Feature | Implementation |
|---------|----------------|
| Authentication | API Key header validation |
| Data Storage | REST CRUD over MySQL JSON columns |
| File Storage | Protected uploads via API |
| Custom Functions | PHP scripts executed via API |
| Notifications | Polling-based from MySQL table |
| Admin Panel | Web interface with separate auth |

### Technology Stack

- **Backend:** PHP 8.x (OOP, PDO)
- **Database:** MySQL 5.7+
- **Storage:** Local filesystem
- **Auth:** API Keys + Session-based admin
- **Response Format:** JSON

---

## Architecture

### Project Structure

```
lite-shelf/
├── api/
│   ├── auth.php           # Auth middleware class
│   ├── data.php           # Data CRUD endpoints
│   ├── storage.php        # File upload/download
│   ├── functions.php      # Custom function execution
│   └── notify.php         # Notifications polling
├── functions/             # Custom PHP function files
│   ├── example.php
│   └── user_functions/    # User-deployed functions
├── uploads/               # Protected file storage
│   ├── .htaccess          # Block direct access
│   ├── index.php          # Download handler
│   └── [year]/[month]/    # Organized directories
├── config/
│   ├── database.php       # DB connection settings
│   └── settings.php       # App configuration
├── lib/
│   ├── Database.php       # PDO wrapper
│   ├── APIKey.php         # Key validation logic
│   ├── Response.php       # JSON response helper
│   └── Upload.php         # File handling class
├── admin/
│   ├── index.php          # Login page
│   ├── dashboard.php      # Stats overview
│   ├── api-keys.php       # Manage API keys
│   ├── collections.php    # Manage collections
│   ├── files.php          # View uploaded files
│   └── functions.php      # Manage custom functions
└── docs/
    └── spec.md            # This document
```

### Request Flow

1. Client sends request with `X-API-Key` header
2. `api/auth.php` validates the key
3. Request routed to appropriate endpoint
4. Response returned as JSON
5. Errors logged and reported consistently

---

## Database Schema

### Tables

#### 1. API Keys Table

```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_hash VARCHAR(64) UNIQUE NOT NULL,
    name VARCHAR(255),
    allowed_endpoints TEXT,      -- JSON array: ["data", "storage", "functions"]
    rate_limit INT DEFAULT 1000, -- requests/hour (placeholder)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_key_hash (key_hash),
    INDEX idx_active (is_active)
);
```

#### 2. Collections Table

```sql
CREATE TABLE data_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    schema_config TEXT,          -- JSON for optional validation rules
    created_by_key_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_key_id) REFERENCES api_keys(id)
);
```

#### 3. Data Items Table

```sql
CREATE TABLE data_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    data JSON NOT NULL,          -- Document content
    created_by_key_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (collection_id) REFERENCES data_collections(id),
    FOREIGN KEY (created_by_key_id) REFERENCES api_keys(id),
    INDEX idx_collection (collection_id),
    INDEX idx_created_by (created_by_key_id),
    FULLTEXT INDEX idx_data_search (data)
);
```

#### 4. Storage Files Table

```sql
CREATE TABLE storage_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename_original VARCHAR(255) NOT NULL,
    filename_stored VARCHAR(255) UNIQUE NOT NULL,
    mime_type VARCHAR(100),
    size_bytes INT NOT NULL,
    uploaded_by_key_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by_key_id) REFERENCES api_keys(id),
    INDEX idx_uploaded_by (uploaded_by_key_id),
    INDEX idx_mime_type (mime_type)
);
```

#### 5. Notifications Table

```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_user_id INT NULL,     -- NULL = broadcast to all
    sender_key_id INT NOT NULL,
    message TEXT NOT NULL,
    data JSON,                   -- Additional context/payload
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_key_id) REFERENCES api_keys(id),
    INDEX idx_target (target_user_id),
    INDEX idx_created (created_at),
    INDEX idx_unread (is_read)
);
```

#### 6. Admin Users Table

```sql
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
);
```

---

## API Endpoints

### Authentication Middleware

All endpoints require `X-API-Key` header validation via `api/auth.php`.

### 1. Data Endpoints (`api/data.php`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/data/collections` | List all collections | Yes |
| POST | `/api/data/collections` | Create new collection | Yes |
| GET | `/api/data/items/{collection}` | Query items in collection | Yes |
| POST | `/api/data/items` | Insert new item | Yes |
| PUT | `/api/data/items/{id}` | Update existing item | Yes |
| DELETE | `/api/data/items/{id}` | Delete item | Yes |

#### Query Parameters (GET /items)
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 50, max: 100)
- `sort` (string): Field to sort by
- `order` (string): `asc` or `desc` (default: desc)

#### Request Body (POST /items)
```json
{
    "collection": "users",
    "data": {
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

#### Response Format
```json
{
    "success": true,
    "data": [...],
    "meta": {
        "total": 100,
        "page": 1,
        "per_page": 50
    }
}
```

#### Error Response
```json
{
    "success": false,
    "error": {
        "code": "INVALID_KEY",
        "message": "Invalid or expired API key"
    }
}
```

### 2. Storage Endpoints (`api/storage.php`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/storage/upload` | Upload file | Yes |
| GET | `/api/storage/download/{id}` | Download file | Yes |
| DELETE | `/api/storage/{id}` | Delete file | Yes |
| GET | `/api/storage/list` | List uploaded files | Yes |

#### Upload Request (multipart/form-data)
- `file`: File object (max 100MB)
- `collection`: Optional collection reference

#### Allowed File Types
- Images: jpg, jpeg, png, gif, webp, svg
- Documents: pdf, doc, docx, txt, csv
- Archives: zip, rar, tar.gz
- Others: All except executables (.exe, .bat, .sh, etc.)

#### Response (Upload Success)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "filename": "abc123xyz.jpg",
        "original_name": "photo.jpg",
        "size_bytes": 102400,
        "mime_type": "image/jpeg",
        "url": "/api/storage/download/123"
    }
}
```

### 3. Functions Endpoints (`api/functions.php`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/functions/{name}` | Execute custom function | Yes |

#### Function Execution
- Name matches filename in `functions/` folder
- Receives POST body as `$payload` variable
- Must return array or JSON string
- Timeout: 30 seconds max

#### Example Function (`functions/hello.php`)
```php
<?php
// Payload received in $payload variable
return [
    'status' => 'success',
    'message' => 'Hello ' . ($payload['name'] ?? 'World'),
    'timestamp' => date('Y-m-d H:i:s')
];
```

#### Response
```json
{
    "success": true,
    "data": {
        "status": "success",
        "message": "Hello World",
        "timestamp": "2024-01-15 10:30:00"
    }
}
```

### 4. Notification Endpoints (`api/notify.php`)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/notify/send` | Send notification | Yes |
| GET | `/api/notify/list` | Poll for notifications | Yes |
| DELETE | `/api/notify/{id}` | Mark as read/delete | Yes |

#### Send Notification Request
```json
{
    "target_user_id": 42,          // NULL for broadcast
    "message": "Your task is complete!",
    "data": {
        "task_id": 123,
        "action": "view_task"
    }
}
```

#### Poll Notifications Request
```
GET /api/notify/list?since=123&page=1&per_page=20
```

#### Poll Response
```json
{
    "success": true,
    "data": [
        {
            "id": 124,
            "message": "Your task is complete!",
            "data": {"task_id": 123},
            "is_read": false,
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "meta": {
        "unread_count": 3,
        "last_poll_id": 124
    }
}
```

---

## Security

### API Key Security

- Keys hashed with bcrypt before storage
- Never exposed in responses
- Can be revoked (set `is_active = FALSE`)
- Can expire (set `expires_at` timestamp)

### File Upload Security

- Files stored outside web root or protected via `.htaccess`
- Filename randomized to prevent conflicts
- MIME type validation on upload
- Size limit enforced (100MB max)
- Executable files blocked

### SQL Injection Prevention

- All queries use PDO prepared statements
- Input validated before processing
- No raw SQL concatenation

### Rate Limiting (Future)

- Track requests per API key per hour
- Log violations in error log
- Consider blocking after threshold

### Admin Panel Security

- Separate session-based authentication
- Password hashing with bcrypt
- CSRF protection on forms
- Role-based access control (admin vs super_admin)

---

## Admin Panel

### Pages

#### 1. Login (`admin/index.php`)
- Username/password form
- Session creation on success
- Redirect to dashboard

#### 2. Dashboard (`admin/dashboard.php`)
- Statistics overview:
  - Total API keys (active/inactive)
  - Total data collections
  - Total uploaded files (count + size)
  - Recent API calls (last 24h)
  - Active users count

#### 3. API Keys Management (`admin/api-keys.php`)
- List all API keys
- Create new key (generates random key, stores hash)
- Edit key name and permissions
- Revoke/deactivate key
- Set expiration date

#### 4. Collections Management (`admin/collections.php`)
- List all data collections
- View items in each collection
- Delete collection (with confirmation)

#### 5. Files Management (`admin/files.php`)
- List all uploaded files
- View file details (size, type, uploader)
- Delete files
- Download preview

#### 6. Functions Management (`admin/functions.php`)
- List available functions
- Deploy new functions (upload PHP file)
- Remove functions
- View recent executions

### Admin Authentication Flow

1. User visits `/admin/index.php`
2. Enters username/password
3. Server validates against `admin_users` table
4. Creates PHP session
5. Redirects to `/admin/dashboard.php`
6. Subsequent pages check `$_SESSION['admin_logged_in']`

---

## Client SDK Examples

### JavaScript Client Example

```javascript
class Lite_Shelf {
    constructor(apiUrl, apiKey) {
        this.apiUrl = apiUrl;
        this.apiKey = apiKey;
    }

    async _request(method, endpoint, data = null) {
        const headers = {
            'X-API-Key': this.apiKey,
            'Content-Type': 'application/json'
        };

        const options = { method, headers };
        if (data) options.body = JSON.stringify(data);

        const response = await fetch(`${this.apiUrl}${endpoint}`, options);
        return response.json();
    }

    // Data operations
    async getItems(collection, params = {}) {
        const query = new URLSearchParams(params).toString();
        return this._request('GET', `/api/data/items/${collection}?${query}`);
    }

    async createItem(collection, data) {
        return this._request('POST', '/api/data/items', { collection, data });
    }

    async updateItem(id, collection, data) {
        return this._request('PUT', `/api/data/items/${id}`, { collection, data });
    }

    async deleteItem(id) {
        return this._request('DELETE', `/api/data/items/${id}`);
    }

    // Storage operations
    async uploadFile(file, collection = null) {
        const formData = new FormData();
        formData.append('file', file);
        if (collection) formData.append('collection', collection);

        const headers = { 'X-API-Key': this.apiKey };
        const options = { method: 'POST', headers, body: formData };

        const response = await fetch(`${this.apiUrl}/api/storage/upload`, options);
        return response.json();
    }

    // Notifications polling
    async pollNotifications(sinceId = 0, callback) {
        const response = await this._request('GET', `/api/notify/list?since=${sinceId}`);
        
        if (response.success && response.data.length > 0) {
            callback(response.data);
            return response.meta.last_poll_id;
        }
        return sinceId;
    }
}

// Usage
const db = new Lite_Shelf('https://yoursite.com/api/', 'your-api-key');

// Save data
db.createItem('users', { name: 'John', email: 'john@example.com' });

// Load data
db.getItems('users', { page: 1, per_page: 50 }).then(console.log);

// Upload file
document.getElementById('fileInput').addEventListener('change', (e) => {
    db.uploadFile(e.target.files[0]);
});

// Poll notifications
let lastId = 0;
setInterval(async () => {
    lastId = await db.pollNotifications(lastId, (notifications) => {
        console.log('New notifications:', notifications);
    });
}, 5000); // Check every 5 seconds
```

### PHP Client Example

```php
class Lite_ShelfClient {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiUrl, $apiKey) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    private function request($method, $endpoint, $data = null) {
        $ch = curl_init();

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("API Error: $httpCode");
        }

        return json_decode($response, true);
    }

    public function getItems($collection, $params = []) {
        $query = http_build_query($params);
        return $this->request('GET', "/api/data/items/{$collection}?{$query}");
    }

    public function createItem($collection, $data) {
        return $this->request('POST', '/api/data/items', compact('collection', 'data'));
    }
}

// Usage
$client = new Lite_ShelfClient('https://yoursite.com/api/', 'your-api-key');

try {
    $result = $client->createItem('users', ['name' => 'John']);
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Deployment Checklist

- [ ] Install PHP 8.x on server
- [ ] Install MySQL/MariaDB
- [ ] Create database and run schema migrations
- [ ] Set up `config/database.php` with credentials
- [ ] Configure `config/settings.php` (app name, time zone, etc.)
- [ ] Set proper permissions on `uploads/` directory
- [ ] Add `.htaccess` files for security
- [ ] Create initial admin user
- [ ] Test all API endpoints
- [ ] Deploy admin panel
- [ ] Generate first API key for testing

---

## Future Enhancements (Not in Scope)

- WebSocket support for real-time updates
- Social authentication (Google, Facebook)
- CDN integration for file delivery
- Email notifications instead of polling
- GraphQL API support
- Mobile app SDKs (iOS, Android)
- Analytics dashboard
- Backup/restore functionality

---

## Appendix A: Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| INVALID_KEY | 401 | API key not found or invalid |
| EXPIRED_KEY | 401 | API key has expired |
| INACTIVE_KEY | 401 | API key is deactivated |
| UNAUTHORIZED_ENDPOINT | 403 | API key doesn't have permission |
| INVALID_REQUEST | 400 | Malformed request body |
| MISSING_FIELD | 400 | Required field missing |
| FILE_TOO_LARGE | 413 | Uploaded file exceeds limit |
| INVALID_FILE_TYPE | 415 | File type not allowed |
| NOT_FOUND | 404 | Resource not found |
| SERVER_ERROR | 500 | Internal server error |

---

## Appendix B: Configuration Settings

```php
// config/settings.php
return [
    'app_name' => 'Lite_Shelf',
    'timezone' => 'UTC',
    
    // Storage limits
    'max_file_size_mb' => 100,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'csv', 'zip', 'rar'],
    'blocked_extensions' => ['exe', 'bat', 'sh', 'cmd', 'ps1', 'vbs'],
    
    // Pagination defaults
    'default_per_page' => 50,
    'max_per_page' => 100,
    
    // API timeout
    'function_timeout_seconds' => 30,
    
    // Admin settings
    'admin_session_lifetime' => 3600, // 1 hour
    
    // Logging
    'log_errors' => true,
    'log_location' => '/var/log/lite-shelf/errors.log'
];
```

---

*Document generated by SoftwareDevAgent following Superpowers methodology.*