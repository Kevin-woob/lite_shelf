---
title: Storage Folders API
section: 05
---

## 5. Storage Folders API

All folder operations go through the Admin API and require admin authentication.

### 5.1 List Folders at a Path

**Endpoint:** `GET your_site/admin/api.php?route=/storage/folders&parent_path=...`

**curl:**
```bash
curl "your_site/admin/api.php?route=/storage/folders&parent_path=" \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def list_folders(api_key: str, parent_path: str = "") -> dict:
    """List subfolders at a given parent path."""
    response = requests.get(
        f"your_site/admin/api.php?route=/storage/folders&parent_path={parent_path}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function listFolders(string $apiKey, string $parentPath = ""): array {
    $url = "your_site/admin/api.php?route=/storage/folders&parent_path=" . urlencode($parentPath);
    $ch = curl_init($url);
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
async function listFolders(apiKey, parentPath = "") {
  const response = await fetch(`your_site/admin/api.php?route=/storage/folders&parent_path=${encodeURIComponent(parentPath)}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

**Example Response (200 OK):**
```json
{
    "success": true,
    "folders": [
        {
            "id": 8,
            "name": "111",
            "path": "111/",
            "parent_path": "",
            "created_by_key_id": 1,
            "created_at": "2026-06-16 13:02:03"
        },
        {
            "id": 3,
            "name": "asd",
            "path": "asd/",
            "parent_path": "",
            "created_by_key_id": null,
            "created_at": "2026-06-14 08:36:42"
        }
    ]
}
```

### 5.2 List Files in a Folder

**Endpoint:** `GET your_site/admin/api.php?route=/storage/files&folder_path=...`

**curl:**
```bash
curl "your_site/admin/api.php?route=/storage/files&folder_path=documents/" \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def list_files_in_folder(api_key: str, folder_path: str = "") -> dict:
    """List files in a specific folder."""
    response = requests.get(
        f"your_site/admin/api.php?route=/storage/files&folder_path={folder_path}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function listFilesInFolder(string $apiKey, string $folderPath = ""): array {
    $url = "your_site/admin/api.php?route=/storage/files&folder_path=" . urlencode($folderPath);
    $ch = curl_init($url);
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
async function listFilesInFolder(apiKey, folderPath = "") {
  const response = await fetch(`your_site/admin/api.php?route=/storage/files&folder_path=${encodeURIComponent(folderPath)}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 5.3 Create a Folder

**Endpoint:** `POST your_site/admin/api.php?route=/storage/folders`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/storage/folders \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "new_folder", "parent_path": "documents/"}'
```

**Python:**
```python
def create_folder(name: str, api_key: str, parent_path: str = "") -> dict:
    """Create a new folder."""
    response = requests.post(
        "your_site/admin/api.php?route=/storage/folders",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"name": name, "parent_path": parent_path}
    )
    return response.json()
```

**PHP:**
```php
function createFolder(string $name, string $apiKey, string $parentPath = ""): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/folders");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["name" => $name, "parent_path" => $parentPath]),
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
async function createFolder(name, apiKey, parentPath = "") {
  const response = await fetch("your_site/admin/api.php?route=/storage/folders", {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ name, parent_path: parentPath })
  });
  return response.json();
}
```

**Example Response (201 Created):**
```json
{
    "success": true,
    "folder": {
        "success": true,
        "id": 29,
        "name": "test_folder_api_142319945",
        "path": "test_folder_api_142319945/",
        "parent_path": ""
    }
}
```

### 5.4 Rename a Folder

**Endpoint:** `PATCH your_site/admin/api.php?route=/storage/folders/{encoded_path}`

**curl:**
```bash
curl -X PATCH your_site/admin/api.php?route=/storage/folders/documents/old_name/ \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "new_name"}'
```

**Python:**
```python
import urllib.parse

def rename_folder(old_path: str, new_name: str, api_key: str) -> dict:
    """Rename a folder."""
    encoded_path = urllib.parse.quote(old_path, safe="")
    response = requests.patch(
        f"your_site/admin/api.php?route=/storage/folders/{encoded_path}",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"name": new_name}
    )
    return response.json()
```

**PHP:**
```php
function renameFolder(string $path, string $newName, string $apiKey): array {
    $encodedPath = urlencode($path);
    $ch = curl_init("your_site/admin/api.php?route=/storage/folders/{$encodedPath}");
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
async function renameFolder(path, newName, apiKey) {
  const encodedPath = encodeURIComponent(path);
  const response = await fetch(`your_site/admin/api.php?route=/storage/folders/${encodedPath}`, {
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

**Example Response (200 OK):**
```json
{
    "success": true,
    "folder": {
        "success": true,
        "old_path": "test_folder_api_142319945/",
        "new_path": "renamed_142319945/",
        "name": "renamed_142319945"
    }
}
```

### 5.5 Move a Folder

**Endpoint:** `POST your_site/admin/api.php?route=/storage/folders/{encoded_path}/move`

**curl:**
```bash
curl -X POST "your_site/admin/api.php?route=/storage/folders/documents/myfolder/move" \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"parent_path": "archive/"}'
```

**Python:**
```python
def move_folder(path: str, new_parent_path: str, api_key: str) -> dict:
    """Move a folder to a new parent."""
    encoded_path = urllib.parse.quote(path, safe="")
    response = requests.post(
        f"your_site/admin/api.php?route=/storage/folders/{encoded_path}/move",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"parent_path": new_parent_path}
    )
    return response.json()
```

**PHP:**
```php
function moveFolder(string $path, string $newParentPath, string $apiKey): array {
    $encodedPath = urlencode($path);
    $ch = curl_init("your_site/admin/api.php?route=/storage/folders/{$encodedPath}/move");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["parent_path" => $newParentPath]),
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
async function moveFolder(path, newParentPath, apiKey) {
  const encodedPath = encodeURIComponent(path);
  const response = await fetch(`your_site/admin/api.php?route=/storage/folders/${encodedPath}/move`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ parent_path: newParentPath })
  });
  return response.json();
}
```

**Example Response (200 OK):**
```json
{
    "success": true,
    "folder": {
        "success": true,
        "old_path": "renamed_142319945/",
        "new_path": "move_target_142319945/renamed_142319945/",
        "name": "renamed_142319945"
    }
}
```

### 5.6 Copy a Folder

**Endpoint:** `POST your_site/admin/api.php?route=/storage/folders/{encoded_path}/copy`

**curl:**
```bash
curl -X POST "your_site/admin/api.php?route=/storage/folders/documents/myfolder/copy" \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"parent_path": "backup/"}'
```

**Python:**
```python
def copy_folder(path: str, dest_parent_path: str, api_key: str) -> dict:
    """Copy a folder to a new parent."""
    encoded_path = urllib.parse.quote(path, safe="")
    response = requests.post(
        f"your_site/admin/api.php?route=/storage/folders/{encoded_path}/copy",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"parent_path": dest_parent_path}
    )
    return response.json()
```

**PHP:**
```php
function copyFolder(string $path, string $destParentPath, string $apiKey): array {
    $encodedPath = urlencode($path);
    $ch = curl_init("your_site/admin/api.php?route=/storage/folders/{$encodedPath}/copy");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["parent_path" => $destParentPath]),
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
async function moveFolder(path, newParentPath, apiKey) {
  const encodedPath = encodeURIComponent(path);
  const response = await fetch(`your_site/admin/api.php?route=/storage/folders/${encodedPath}/copy`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ parent_path: destParentPath })
  });
  return response.json();
}
```

**Example Response (200 OK):**
```json
{
    "success": true,
    "folder": {
        "success": true,
        "original_path": "move_target_142319945/renamed_142319945/",
        "new_path": "renamed_142319945/",
        "name": "renamed_142319945"
    }
}
```

### 5.7 Delete a Folder

**Endpoint:** `DELETE your_site/admin/api.php?route=/storage/folders/{encoded_path}`

**curl:**
```bash
curl -X DELETE "your_site/admin/api.php?route=/storage/folders/documents/old_folder/" \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def delete_folder(path: str, api_key: str) -> dict:
    """Delete a folder and all its contents recursively."""
    encoded_path = urllib.parse.quote(path, safe="")
    response = requests.delete(
        f"your_site/admin/api.php?route=/storage/folders/{encoded_path}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteFolder(string $path, string $apiKey): array {
    $encodedPath = urlencode($path);
    $ch = curl_init("your_site/admin/api.php?route=/storage/folders/{$encodedPath}");
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
async function deleteFolder(path, apiKey) {
  const encodedPath = encodeURIComponent(path);
  const response = await fetch(`your_site/admin/api.php?route=/storage/folders/${encodedPath}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 5.8 Move a File

**Endpoint:** `POST your_site/admin/api.php?route=/storage/files/{fileId}/move`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/storage/files/42/move \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"folder_path": "archive/"}'
```

**Python:**
```python
def move_file(file_id: int, folder_path: str, api_key: str) -> dict:
    """Move a file to a different folder."""
    response = requests.post(
        f"your_site/admin/api.php?route=/storage/files/{file_id}/move",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"folder_path": folder_path}
    )
    return response.json()
```

**PHP:**
```php
function moveFile(int $fileId, string $folderPath, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/files/{$fileId}/move");
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
async function moveFile(fileId, folderPath, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/storage/files/${fileId}/move`, {
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

### 5.9 Copy a File

**Endpoint:** `POST your_site/admin/api.php?route=/storage/files/{fileId}/copy`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/storage/files/42/copy \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"folder_path": "backup/"}'
```

**Python:**
```python
def copy_file(file_id: int, folder_path: str, api_key: str) -> dict:
    """Copy a file to a different folder."""
    response = requests.post(
        f"your_site/admin/api.php?route=/storage/files/{file_id}/copy",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"folder_path": folder_path}
    )
    return response.json()
```

**PHP:**
```php
function copyFile(int $fileId, string $folderPath, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/files/{$fileId}/copy");
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
async function copyFile(fileId, folderPath, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/storage/files/${fileId}/copy`, {
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

### 5.10 Rename a File

**Endpoint:** `POST your_site/admin/api.php?route=/storage/files/{fileId}/rename`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/storage/files/42/rename \
  -H "X-API-Key: your_admin_api_key" \
  -H "Content-Type: application/json" \
  -d '{"name": "new_filename.txt"}'
```

**Python:**
```python
def rename_file(file_id: int, new_name: str, api_key: str) -> dict:
    """Rename a file."""
    response = requests.post(
        f"your_site/admin/api.php?route=/storage/files/{file_id}/rename",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json={"name": new_name}
    )
    return response.json()
```

**PHP:**
```php
function renameFile(int $fileId, string $newName, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/files/{$fileId}/rename");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
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
async function renameFile(fileId, newName, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/storage/files/${fileId}/rename`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ name: newName })
  });
  return response.json();
}
```

### 5.11 Delete a File (Admin)

**Endpoint:** `DELETE your_site/admin/api.php?route=/storage/files/{fileId}`

**curl:**
```bash
curl -X DELETE your_site/admin/api.php?route=/storage/files/42 \
  -H "X-API-Key: your_admin_api_key"
```

**Python:**
```python
def delete_file_admin(file_id: int, api_key: str) -> dict:
    """Delete a file (admin API)."""
    response = requests.delete(
        f"your_site/admin/api.php?route=/storage/files/{file_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteFileAdmin(int $fileId, string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/storage/files/{$fileId}");
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
async function deleteFileAdmin(fileId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/storage/files/${fileId}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 5.12 Download a File (Admin)

**Endpoint:** `GET your_site/admin/api.php?route=/storage/files/{fileId}/download`

**curl:**
```bash
curl "your_site/admin/api.php?route=/storage/files/42/download" \
  -H "X-API-Key: your_admin_api_key" \
  -o downloaded_file.pdf
```

**Python:**
```python
def download_file_admin(file_id: int, api_key: str) -> bytes:
    """Download a file via admin API."""
    response = requests.get(
        f"your_site/admin/api.php?route=/storage/files/{file_id}/download",
        headers={"X-API-Key": api_key}
    )
    return response.content
```

**PHP:**
```php
function downloadFileAdmin(int $fileId, string $apiKey): void {
    $url = "your_site/admin/api.php?route=/storage/files/{$fileId}/download";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $content = curl_exec($ch);
    curl_close($ch);
    // Save to file or output
    file_put_contents("downloaded_file", $content);
}
```

**JavaScript:**
```javascript
async function downloadFileAdmin(fileId, apiKey) {
  const response = await fetch(`your_site/admin/api.php?route=/storage/files/${fileId}/download`, {
    headers: { "X-API-Key": apiKey }
  });
  return await response.blob();
}
```

← [[04-storage-api]] | [[index]] | → [[06-notifications-api]]
