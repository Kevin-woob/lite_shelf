---
title: Access Control
section: 09
---

## 9. Access Control

The system supports fine-grained access control for non-admin API keys. Admin keys have unlimited access to everything.

### 9.1 Grant Collection Access

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys/{keyId}/grant-collection`

**Access Levels:** `read`, `write`, `full`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys/5/grant-collection \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"collection_id": 3, "access_level": "write"}'
```

**Python:**
```python
def grant_collection_access(key_id: int, collection_id: int, api_key: str, access_level: str = "read") -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/grant-collection",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"collection_id": collection_id, "access_level": access_level}
    )
    return response.json()
```

**PHP:**
```php
function grantCollectionAccess(int $keyId, int $collectionId, string $apiKey, string $accessLevel = "read"): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/grant-collection");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "collection_id" => $collectionId,
            "access_level" => $accessLevel
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
async function grantCollectionAccess(keyId, collectionId, apiKey, accessLevel = "read") {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/grant-collection`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ collection_id: collectionId, access_level: accessLevel })
  });
  return response.json();
}
```

### 9.2 Revoke Collection Access

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys/{keyId}/revoke-collection`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys/5/revoke-collection \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"collection_id": 3}'
```

**Python:**
```python
def revoke_collection_access(key_id: int, collection_id: int, api_key: str) -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/revoke-collection",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"collection_id": collection_id}
    )
    return response.json()
```

**PHP:**
```php
function revokeCollectionAccess(int $keyId, int $collectionId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/revoke-collection");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["collection_id" => $collectionId]),
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
async function revokeCollectionAccess(keyId, collectionId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/revoke-collection`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ collection_id: collectionId })
  });
  return response.json();
}
```

### 9.3 Grant Folder Access

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys/{keyId}/grant-folder`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys/5/grant-folder \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"folder_path": "documents/reports/", "access_level": "write"}'
```

**Python:**
```python
def grant_folder_access(key_id: int, folder_path: str, api_key: str, access_level: str = "read") -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/grant-folder",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"folder_path": folder_path, "access_level": access_level}
    )
    return response.json()
```

**PHP:**
```php
function grantFolderAccess(int $keyId, string $folderPath, string $apiKey, string $accessLevel = "read"): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/grant-folder");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "folder_path" => $folderPath,
            "access_level" => $accessLevel
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
async function grantFolderAccess(keyId, folderPath, apiKey, accessLevel = "read") {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/grant-folder`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ folder_path: folderPath, access_level: accessLevel })
  });
  return response.json();
}
```

### 9.4 Revoke Folder Access

**Endpoint:** `POST your_site/admin/api.php?route=/api-keys/{keyId}/revoke-folder`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/api-keys/5/revoke-folder \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"folder_path": "documents/reports/"}'
```

**Python:**
```python
def revoke_folder_access(key_id: int, folder_path: str, api_key: str) -> dict:
    response = requests.post(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/revoke-folder",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"folder_path": folder_path}
    )
    return response.json()
```

**PHP:**
```php
function revokeFolderAccess(int $keyId, string $folderPath, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/revoke-folder");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["folder_path" => $folderPath]),
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
async function revokeFolderAccess(keyId, folderPath, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/revoke-folder`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ folder_path: folderPath })
  });
  return response.json();
}
```

### 9.5 Get Key Permissions

**Endpoint:** `GET your_site/admin/api.php?route=/api-keys/{keyId}/permissions`

**curl:**
```bash
curl your_site/admin/api.php?route=/api-keys/5/permissions \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def get_key_permissions(key_id: int, api_key: str) -> dict:
    response = requests.get(
        f"your_site/admin/api.php?route=/api-keys/{key_id}/permissions",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function getKeyPermissions(int $keyId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/api-keys/{$keyId}/permissions");
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
async function getKeyPermissions(keyId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/api-keys/${keyId}/permissions`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

← [[08-admin-api]] | [[index]] | → [[quick-reference]]
