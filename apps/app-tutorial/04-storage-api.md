---
title: Storage API
section: 04
---

## 4. Storage API

File storage with folder support, MIME type validation, and size limits (100MB max).

### 4.1 Upload a File

**Endpoint:** `POST your_site/storage/upload` (multipart/form-data)

**curl:**
```bash
curl -X POST your_site/storage/upload \
  -H "X-API-Key: your_api_key_here" \
  -F "file=@/path/to/your/file.pdf" \
  -F "folder_path=documents/reports/"
```

**Python:**
```python
def upload_file(file_path: str, api_key: str, folder_path: str = "") -> dict:
    """Upload a file to storage."""
    with open(file_path, "rb") as f:
        files = {"file": f}
        data = {"folder_path": folder_path} if folder_path else {}
        response = requests.post(
            "your_site/storage/upload",
            headers={"X-API-Key": api_key},
            files=files,
            data=data
        )
    return response.json()

result = upload_file("report.pdf", "your_api_key_here", "documents/reports/")
```

**PHP:**
```php
function uploadFile(string $filePath, string $apiKey, string $folderPath = ""): array {
    $ch = curl_init("your_site/storage/upload");
    $file = new CURLFile($filePath);
    $data = ["file" => $file];
    if ($folderPath) {
        $data["folder_path"] = $folderPath;
    }
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

**JavaScript (Browser):**
```javascript
async function uploadFile(file, apiKey, folderPath = "") {
  const formData = new FormData();
  formData.append("file", file);
  if (folderPath) {
    formData.append("folder_path", folderPath);
  }
  const response = await fetch("your_site/storage/upload", {
    method: "POST",
    headers: { "X-API-Key": apiKey },
    body: formData
  });
  return response.json();
}

// Usage with file input
document.getElementById("fileInput").addEventListener("change", (e) => {
  uploadFile(e.target.files[0], "your_api_key_here", "documents/").then(console.log);
});
```

**Example Response (201 Created):**
```json
{
    "success": true,
    "id": 36,
    "filename": "6a338ed89956f_16a6247ac4c6c50c.txt",
    "folder_path": "",
    "url": "/uploads/6a338ed89956f_16a6247ac4c6c50c.txt",
    "size": 35,
    "mime_type": ""
}
```

### 4.2 Get a File

**Endpoint:** `GET your_site/storage/files/{filename}`

**curl:**
```bash
curl your_site/storage/files/6a2df7819d2e5_df05df8ea5e4fbc1.txt \
  -o downloaded_file.txt
```

**Python:**
```python
def get_file(filename: str) -> bytes:
    """Download a file from storage."""
    response = requests.get(
        f"your_site/storage/files/{filename}"
    )
    return response.content

content = get_file("6a2df7819d2e5_df05df8ea5e4fbc1.txt")
with open("downloaded.txt", "wb") as f:
    f.write(content)
```

**PHP:**
```php
function getFile(string $filename): ?string {
    $ch = curl_init("your_site/storage/files/{$filename}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode === 200 ? $result : null;
}
```

**JavaScript:**
```javascript
async function getFile(filename) {
  const response = await fetch(`your_site/storage/files/${filename}`);
  if (!response.ok) throw new Error("File not found");
  return await response.blob();
}

// Download file
getFile("6a2df7819d2e5_df05df8ea5e4fbc1.txt").then(blob => {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "downloaded.txt";
  a.click();
});
```

### 4.3 Delete a File

**Endpoint:** `DELETE your_site/storage/files/{filename}`

**curl:**
```bash
curl -X DELETE your_site/storage/files/6a2df7819d2e5_df05df8ea5e4fbc1.txt \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def delete_file(filename: str, api_key: str) -> dict:
    """Delete a file from storage."""
    response = requests.delete(
        f"your_site/storage/files/{filename}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteFile(string $filename, string $apiKey): array {
    $ch = curl_init("your_site/storage/files/{$filename}");
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
async function deleteFile(filename, apiKey) {
  const response = await fetch(`your_site/storage/files/${filename}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

← [[03-data-collections-api]] | [[index]] | → [[05-storage-folders-api]]
