---
title: Admin API
section: 08
---

## 8. Admin API

All admin endpoints require admin-level API key authentication. Routes are passed via the `?route=` query parameter.

### 8.1 Admin Login (Session-based)

**Endpoint:** `POST your_site/admin/api.php?route=/login`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/login \
  -c cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"api_key": "your_admin_api_key"}'
```

**Python:**
```python
def admin_login(api_key: str) -> requests.Session:
    """Login to admin panel (creates session)."""
    session = requests.Session()
    response = session.post(
        "your_site/admin/api.php?route=/login",
        json={"api_key": api_key}
    )
    return session  # Session cookies are maintained
```

**PHP:**
```php
function adminLogin(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["api_key" => $apiKey]),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_COOKIEJAR => "/tmp/cookies.txt"
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function adminLogin(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ api_key: apiKey })
  });
  return response.json();
}
```

### 8.2 Admin Logout

**Endpoint:** `POST your_site/admin/api.php?route=/logout`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/logout \
  -b cookies.txt
```

**Python:**
```python
def admin_logout(session: requests.Session) -> dict:
    response = session.post("your_site/admin/api.php?route=/logout")
    return response.json()
```

**PHP:**
```php
function adminLogout(): array {
    $ch = curl_init("your_site/admin/api.php?route=/logout");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_COOKIEFILE => "/tmp/cookies.txt"
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function adminLogout() {
  const response = await fetch("your_site/admin/api.php?route=/logout", {
    method: "POST",
    credentials: "include"
  });
  return response.json();
}
```

### 8.3 Get Dashboard Stats

**Endpoint:** `GET your_site/admin/api.php?route=/stats`

**curl:**
```bash
curl your_site/admin/api.php?route=/stats \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def get_stats(api_key: str) -> dict:
    """Get dashboard statistics."""
    response = requests.get(
        "your_site/admin/api.php?route=/stats",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function getStats(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/stats");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function getStats(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/stats", {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.4 List Users

**Endpoint:** `GET your_site/admin/api.php?route=/users`

**curl:**
```bash
curl your_site/admin/api.php?route=/users \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def list_users(api_key: str) -> dict:
    response = requests.get(
        "your_site/admin/api.php?route=/users",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function listUsers(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/users");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function listUsers(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/users", {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.5 Create a User

**Endpoint:** `POST your_site/admin/api.php?route=/users`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/users \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "securepassword", "display_name": "Test User"}'
```

**Python:**
```python
def create_user(email: str, password: str, api_key: str, display_name: str = None) -> dict:
    payload = {"email": email, "password": password}
    if display_name:
        payload["display_name"] = display_name
    response = requests.post(
        "your_site/admin/api.php?route=/users",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=payload
    )
    return response.json()
```

**PHP:**
```php
function createUser(string $email, string $password, string $apiKey, ?string $displayName = null): array {
    $payload = ["email" => $email, "password" => $password];
    if ($displayName) $payload["display_name"] = $displayName;
    $ch = curl_init("your_site/admin/api.php?route=/users");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function createUser(email, password, apiKey, displayName = null) {
  const payload = { email, password };
  if (displayName) payload.display_name = displayName;
  const response = await fetch("your_site/admin/api.php?route=/users", {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });
  return response.json();
}
```

### 8.6 Delete a User

**Endpoint:** `DELETE your_site/admin/api.php?route=/users/{userId}`

**curl:**
```bash
curl -X DELETE your_site/admin/api.php?route=/users/5 \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def delete_user(user_id: int, api_key: str) -> dict:
    response = requests.delete(
        f"your_site/admin/api.php?route=/users/{user_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteUser(int $userId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/users/{$userId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function deleteUser(userId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/users/${userId}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.7 List API Keys

**Endpoint:** `GET your_site/admin/api.php?route=/api-keys`

**curl:**
```bash
curl your_site/admin/api.php?route=/api-keys \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def list_api_keys(api_key: str) -> dict:
    response = requests.get(
        "your_site/admin/api.php?route=/api-keys",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function listApiKeys(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function listApiKeys(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/api-keys", {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.8 Create an API Key

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "My App Key", "is_admin": false, "rate_limit": 1000}'
```

**Python:**
```python
def create_api_key(name: str, api_key: str, is_admin: bool = False, rate_limit: int = 1000) -> dict:
    """Create a new API key. The plain-text key is returned only once!"""
    response = requests.post(
        "your_site/admin/api.php?route=/api-keys",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"name": name, "is_admin": is_admin, "rate_limit": rate_limit}
    )
    return response.json()

result = create_api_key("My App Key", "your_admin_api_key")
print("Save this key:", result["key"]["key"])  # Only shown once!
```

**PHP:**
```php
function createApiKey(string $name, string $apiKey, bool $isAdmin = false, int $rateLimit = 1000): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "name" => $name,
            "is_admin" => $isAdmin,
            "rate_limit" => $rateLimit
        ]),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function createApiKey(name, apiKey, isAdmin = false, rateLimit = 1000) {
  const response = await fetch("your_site/admin/api.php?route=/api-keys", {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ name, is_admin: isAdmin, rate_limit: rateLimit })
  });
  return response.json();
}
```

### 8.9 Revoke an API Key

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys/{keyId}/revoke`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys/3/revoke \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def revoke_api_key(key_id: int, api_key: str) -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/revoke",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function revokeApiKey(int $keyId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/revoke");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function revokeApiKey(keyId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/revoke`, {
    method: "POST",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.10 Set Admin Key

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys/{keyId}/set-admin`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys/3/set-admin \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def set_admin_key(key_id: int, api_key: str) -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/set-admin",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function setAdminKey(int $keyId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/set-admin");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function setAdminKey(keyId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/set-admin`, {
    method: "POST",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.11 List Collections (Admin)

**Endpoint:** `GET your_site/admin/api.php?route=/collections`

**curl:**
```bash
curl your_site/admin/api.php?route=/collections \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def list_collections_admin(api_key: str) -> dict:
    response = requests.get(
        "your_site/admin/api.php?route=/collections",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function listCollectionsAdmin(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function listCollectionsAdmin(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/collections", {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.12 Create a Collection (Admin)

**Endpoint:** `POST your_site/admin/api.php?route=/collections`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/collections \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "products", "description": "Product catalog"}'
```

**Python:**
```python
def create_collection_admin(name: str, api_key: str, description: str = None) -> dict:
    payload = {"name": name}
    if description:
        payload["description"] = description
    response = requests.post(
        "your_site/admin/api.php?route=/collections",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=payload
    )
    return response.json()
```

**PHP:**
```php
function createCollectionAdmin(string $name, string $apiKey, ?string $description = null): array {
    $payload = ["name" => $name];
    if ($description) $payload["description"] = $description;
    $ch = curl_init("your_site/admin/api.php?route=/collections");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function createCollectionAdmin(name, apiKey, description = null) {
  const payload = { name };
  if (description) payload.description = description;
  const response = await fetch("your_site/admin/api.php?route=/collections", {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });
  return response.json();
}
```

### 8.13 Get Collection Items (Admin)

**Endpoint:** `GET your_site/admin/api.php?route=/collections/{collectionName}`

**curl:**
```bash
curl your_site/admin/api.php?route=/collections/products \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def get_collection_items_admin(collection_name: str, api_key: str) -> dict:
    response = requests.get(
        f"your_site/admin/api.php?route=/collections/{collection_name}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function getCollectionItemsAdmin(string $collectionName, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$collectionName}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function getCollectionItemsAdmin(collectionName, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${collectionName}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.14 Create Document in Collection (Admin)

**Endpoint:** `POST your_site/admin/api.php?route=/collections/{collectionName}/documents`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/collections/products/documents \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"title": "Widget", "price": 9.99, "in_stock": true}'
```

**Python:**
```python
def create_document_admin(collection_name: str, data: dict, api_key: str) -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/collections/{collection_name}/documents",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=data
    )
    return response.json()
```

**PHP:**
```php
function createDocumentAdmin(string $collectionName, array $data, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$collectionName}/documents");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function createDocumentAdmin(collectionName, data, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${collectionName}/documents`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  });
  return response.json();
}
```

### 8.15 Get Single Document (Admin)

**Endpoint:** `GET your_site/admin/api.php?route=/collections/{collectionName}/documents/{docId}`

**curl:**
```bash
curl your_site/admin/api.php?route=/collections/products/documents/42 \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def get_document_admin(collection_name: str, doc_id: str, api_key: str) -> dict:
    response = requests.get(
        f"your_site/admin/api.php?route=/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function getDocumentAdmin(string $collectionName, string $docId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$collectionName}/documents/{$docId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function getDocumentAdmin(collectionName, docId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${collectionName}/documents/${docId}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.16 Update Document (Admin)

**Endpoint:** `PATCH your_site/admin/api.php?route=/collections/{collectionName}/documents/{docId}`

**curl:**
```bash
curl -X PATCH your_site/admin/api.php?route=/collections/products/documents/42 \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"price": 12.99}'
```

**Python:**
```python
def update_document_admin(collection_name: str, doc_id: str, data: dict, api_key: str) -> dict:
    response = requests.patch(
        f"your_site/admin/api.php?route=/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=data
    )
    return response.json()
```

**PHP:**
```php
function updateDocumentAdmin(string $collectionName, string $docId, array $data, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$collectionName}/documents/{$docId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PATCH",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function updateDocumentAdmin(collectionName, docId, data, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${collectionName}/documents/${docId}`, {
    method: "PATCH",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  });
  return response.json();
}
```

### 8.17 Delete Document (Admin)

**Endpoint:** `DELETE your_site/admin/api.php?route=/collections/{collectionName}/documents/{docId}`

**curl:**
```bash
curl -X DELETE your_site/admin/api.php?route=/collections/products/documents/42 \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def delete_document_admin(collection_name: str, doc_id: str, api_key: str) -> dict:
    response = requests.delete(
        f"your_site/admin/api.php?route=/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteDocumentAdmin(string $collectionName, string $docId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$collectionName}/documents/{$docId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function deleteDocumentAdmin(collectionName, docId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${collectionName}/documents/${docId}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.18 Rename Collection (Admin)

**Endpoint:** `PATCH your_site/admin/api.php?route=/collections/{collectionName}`

**curl:**
```bash
curl -X PATCH your_site/admin/api.php?route=/collections/products \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "catalog"}'
```

**Python:**
```python
def rename_collection(old_name: str, new_name: str, api_key: str) -> dict:
    response = requests.patch(
        f"your_site/admin/api.php?route=/collections/{old_name}",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"name": new_name}
    )
    return response.json()
```

**PHP:**
```php
function renameCollection(string $oldName, string $newName, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$oldName}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PATCH",
        CURLOPT_POSTFIELDS => json_encode(["name" => $newName]),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function renameCollection(oldName, newName, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${oldName}`, {
    method: "PATCH",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ name: newName })
  });
  return response.json();
}
```

### 8.19 Copy Collection (Admin)

**Endpoint:** `POST your_site/admin/api.php?route=/collections/{collectionName}/copy`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/collections/products/copy \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"new_name": "products_backup"}'
```

**Python:**
```python
def copy_collection(source_name: str, new_name: str, api_key: str) -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/collections/{source_name}/copy",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"new_name": new_name}
    )
    return response.json()
```

**PHP:**
```php
function copyCollection(string $sourceName, string $newName, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$sourceName}/copy");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["new_name" => $newName]),
        CURLOPT_HTTPHEADER => [
            "X-API-Key: {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function copyCollection(sourceName, newName, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${sourceName}/copy`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ new_name: newName })
  });
  return response.json();
}
```

### 8.20 Delete Collection (Admin)

**Endpoint:** `DELETE your_site/admin/api.php?route=/collections/{collectionName}`

**curl:**
```bash
curl -X DELETE your_site/admin/api.php?route=/collections/products \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def delete_collection(collection_name: str, api_key: str) -> dict:
    response = requests.delete(
        f"your_site/admin/api.php?route=/collections/{collection_name}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteCollection(string $collectionName, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/collections/{$collectionName}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function deleteCollection(collectionName, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/collections/${collectionName}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 8.21 Upload File (Admin)

**Endpoint:** `POST your_site/admin/api.php?route=/storage/upload`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/storage/upload \
  -H "X-API-Key: your_admin_api_key" \
  -F "file=@/path/to/file.pdf" \
  -F "folder_path=documents/"
```

**Python:**
```python
def upload_file_admin(file_path: str, api_key: str, folder_path: str = "") -> dict:
    with open(file_path, "rb") as f:
        files = {"file": f}
        data = {"folder_path": folder_path} if folder_path else {}
        response = requests.post(
            "your_site/admin/api.php?route=/storage/upload",
            headers={"X-API-Key": api_key},
            files=files,
            data=data
        )
    return response.json()
```

**PHP:**
```php
function uploadFileAdmin(string $filePath, string $apiKey, string $folderPath = ""): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/upload");
    $file = new CURLFile($filePath);
    $data = ["file" => $file];
    if ($folderPath) $data["folder_path"] = $folderPath;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function uploadFileAdmin(file, apiKey, folderPath = "") {
  const formData = new FormData();
  formData.append("file", file);
  if (folderPath) formData.append("folder_path", folderPath);
  const response = await fetch("your_site/admin/api.php?route=/storage/upload", {
    method: "POST",
    headers: { "X-API-Key": apiKey },
    body: formData
  });
  return response.json();
}
```

### 8.22 List All Folder Paths (Admin)

**Endpoint:** `GET your_site/admin/api.php?route=/storage/folders/all-paths`

**curl:**
```bash
curl your_site/admin/api.php?route=/storage/folders/all-paths \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def list_all_folder_paths(api_key: str) -> dict:
    response = requests.get(
        "your_site/admin/api.php?route=/storage/folders/all-paths",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function listAllFolderPaths(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/folders/all-paths");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function listAllFolderPaths(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/storage/folders/all-paths", {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

← [[07-cloud-functions-api]] | [[index]] | → [[09-access-control]]
