---
title: Data Collections API
section: 03
---

## 3. Data Collections API

Collections are document stores similar to Firebase Firestore collections. Each document is stored as JSON in MySQL.

### 3.1 List All Documents in a Collection

**Endpoint:** `GET your_site/collections/{collectionName}/documents`

**curl:**
```bash
curl your_site/collections/users/documents \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def list_documents(collection_name: str, api_key: str) -> dict:
    """List all documents in a collection."""
    response = requests.get(
        f"your_site/collections/{collection_name}/documents",
        headers={"X-API-Key": api_key}
    )
    return response.json()

docs = list_documents("users", "your_api_key_here")
print(docs)
```

**PHP:**
```php
function listDocuments(string $collectionName, string $apiKey): array {
    $ch = curl_init("your_site/collections/{$collectionName}/documents");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}

$docs = listDocuments("users", "your_api_key_here");
print_r($docs);
```

**JavaScript (Browser/Fetch):**
```javascript
async function listDocuments(collectionName, apiKey) {
  const response = await fetch(`your_site/collections/${collectionName}/documents`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}

listDocuments("users", "your_api_key_here").then(console.log);
```

**Example Response (200 OK):**
```json
{
    "success": true,
    "documents": []
}
```

### 3.2 Query Documents with Filters

**Endpoint:** `GET your_site/collections/{collectionName}/documents?field=...&operator=...&value=...&orderBy=...&order=...&limit=...`

**curl:**
```bash
curl "your_site/collections/users/documents?field=age&operator=>&value=18&orderBy=created_at&order=DESC&limit=10" \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def query_documents(collection_name: str, api_key: str, params: dict) -> dict:
    """Query documents with filters."""
    response = requests.get(
        f"your_site/collections/{collection_name}/documents",
        headers={"X-API-Key": api_key},
        params=params
    )
    return response.json()

docs = query_documents("users", "your_api_key_here", {
    "field": "age",
    "operator": ">",
    "value": "18",
    "orderBy": "created_at",
    "order": "DESC",
    "limit": "10"
})
```

**PHP:**
```php
function queryDocuments(string $collectionName, string $apiKey, array $params): array {
    $query = http_build_query($params);
    $ch = curl_init("your_site/collections/{$collectionName}/documents?{$query}");
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
async function queryDocuments(collectionName, apiKey, params) {
  const query = new URLSearchParams(params).toString();
  const response = await fetch(`your_site/collections/${collectionName}/documents?${query}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 3.3 Create a Document

**Endpoint:** `POST your_site/collections/{collectionName}/documents`

**curl:**
```bash
curl -X POST your_site/collections/users/documents \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com", "age": 30}'
```

**Python:**
```python
def create_document(collection_name: str, data: dict, api_key: str) -> dict:
    """Create a new document in a collection."""
    response = requests.post(
        f"your_site/collections/{collection_name}/documents",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=data
    )
    return response.json()

result = create_document("users", {
    "name": "John Doe",
    "email": "john@example.com",
    "age": 30
}, "your_api_key_here")
```

**PHP:**
```php
function createDocument(string $collectionName, array $data, string $apiKey): array {
    $ch = curl_init("your_site/collections/{$collectionName}/documents");
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
async function createDocument(collectionName, data, apiKey) {
  const response = await fetch(`your_site/collections/${collectionName}/documents`, {
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

**Example Response (201 Created):**
```json
{
    "success": true,
    "id": "37"
}
```

### 3.4 Get a Single Document

**Endpoint:** `GET your_site/collections/{collectionName}/documents/{documentId}`

**curl:**
```bash
curl your_site/collections/users/documents/42 \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def get_document(collection_name: str, doc_id: str, api_key: str) -> dict:
    """Get a single document by ID."""
    response = requests.get(
        f"your_site/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function getDocument(string $collectionName, string $docId, string $apiKey): array {
    $ch = curl_init("your_site/collections/{$collectionName}/documents/{$docId}");
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
async function getDocument(collectionName, docId, apiKey) {
  const response = await fetch(`your_site/collections/${collectionName}/documents/${docId}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 3.5 Update a Document (Merge)

**Endpoint:** `PATCH your_site/collections/{collectionName}/documents/{documentId}`

**curl:**
```bash
curl -X PATCH your_site/collections/users/documents/42 \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"age": 31, "status": "active"}'
```

**Python:**
```python
def update_document(collection_name: str, doc_id: str, data: dict, api_key: str) -> dict:
    """Update a document (merge fields)."""
    response = requests.patch(
        f"your_site/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=data
    )
    return response.json()
```

**PHP:**
```php
function updateDocument(string $collectionName, string $docId, array $data, string $apiKey): array {
    $ch = curl_init("your_site/collections/{$collectionName}/documents/{$docId}");
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
async function updateDocument(collectionName, docId, data, apiKey) {
  const response = await fetch(`your_site/collections/${collectionName}/documents/${docId}`, {
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

### 3.6 Replace a Document (Set)

**Endpoint:** `PUT your_site/collections/{collectionName}/documents/{documentId}`

**curl:**
```bash
curl -X PUT your_site/collections/users/documents/42 \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"name": "Jane Doe", "email": "jane@example.com"}'
```

**Python:**
```python
def replace_document(collection_name: str, doc_id: str, data: dict, api_key: str) -> dict:
    """Replace entire document (overwrite)."""
    response = requests.put(
        f"your_site/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=data
    )
    return response.json()
```

**PHP:**
```php
function replaceDocument(string $collectionName, string $docId, array $data, string $apiKey): array {
    $ch = curl_init("your_site/collections/{$collectionName}/documents/{$docId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
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
async function replaceDocument(collectionName, docId, data, apiKey) {
  const response = await fetch(`your_site/collections/${collectionName}/documents/${docId}`, {
    method: "PUT",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  });
  return response.json();
}
```

### 3.7 Delete a Document

**Endpoint:** `DELETE your_site/collections/{collectionName}/documents/{documentId}`

**curl:**
```bash
curl -X DELETE your_site/collections/users/documents/42 \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def delete_document(collection_name: str, doc_id: str, api_key: str) -> dict:
    """Delete a document."""
    response = requests.delete(
        f"your_site/collections/{collection_name}/documents/{doc_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteDocument(string $collectionName, string $docId, string $apiKey): array {
    $ch = curl_init("your_site/collections/{$collectionName}/documents/{$docId}");
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
async function deleteDocument(collectionName, docId, apiKey) {
  const response = await fetch(`your_site/collections/${collectionName}/documents/${docId}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

← [[02-authentication]] | [[index]] | → [[04-storage-api]]
