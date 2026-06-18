# App Write Endpoints

## 5. POST — Create App

Creates a new application. Copies the template, generates an API key, creates database tables with a unique table prefix, and seeds an admin API key.

**Endpoint:** `POST api.php?action=create`

**Body:**

| Field             | Type   | Required | Description |
|-------------------|--------|----------|-------------|
| name              | string | Yes      | 3-50 chars, `[a-zA-Z0-9_-]` only |
| config            | object | No       | e.g. `{ "description": "..." }` |

**cURL**
```bash
curl -X POST 'http://your_site/dashboard/api.php?action=create' \
  -H 'Content-Type: application/json' \
  -b cookies.txt \
  -d '{
    "name": "my-new-app",
    "config": { "description": "A brand new app" }
  }'
```

**Python**
```python
resp = session.post(
    'http://your_site/dashboard/api.php?action=create',
    json={
        'name': 'my-new-app',
        'config': {'description': 'A brand new app'}
    }
)
print(resp.status_code, resp.json())
```

**PHP**
```php
<?php
$data = [
    'name' => 'my-new-app',
    'config' => ['description' => 'A brand new app']
];
$ch = curl_init('http://your_site/dashboard/api.php?action=create');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
echo curl_exec($ch);
curl_close($ch);
```

**JavaScript**
```javascript
const resp = await fetch('api.php?action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({
        name: 'my-new-app',
        config: { description: 'A brand new app' }
    })
});
const result = await resp.json();
console.log(result);
```

**Tested Response (201)**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "test-api-doc",
        "folder_path": "test-api-doc",
        "api_key": "app_aB4cD7eF0gH1iJ2k",
        "database_name": "test_api_doc_",
        "status": "active",
        "config": {
            "description": "API documentation test app"
        },
        "created_at": "2026-06-18 14:30:05",
        "updated_at": "2026-06-18 14:30:05"
    },
    "meta": {
        "message": "App created successfully"
    }
}
```

**Error: Missing Required Field (400)**
```json
{
    "success": false,
    "error": {
        "code": "BAD_REQUEST",
        "message": "App name is required"
    }
}
```

---

## 6. POST — Update App

Updates an existing application's fields.

**Endpoint:** `POST api.php?action=update&id={id}`

**Query Parameters:**

| Param | Type   | Required |
|-------|--------|----------|
| id    | number | Yes      |

**Body** — any combination of:

| Field       | Type   | Description |
|-------------|--------|-------------|
| name        | string | Must pass validation |
| folder_path | string | Folder name |
| api_key     | string | API key |
| database_name | string | Table prefix |
| status      | string | `active`, `inactive`, `error` |
| config      | object | Arbitrary JSON config |

**cURL**
```bash
# Update status
curl -X POST 'http://your_site/dashboard/api.php?action=update&id=2' \
  -H 'Content-Type: application/json' \
  -b cookies.txt \
  -d '{ "status": "inactive" }'

# Update config and API key
curl -X POST 'http://your_site/dashboard/api.php?action=update&id=2' \
  -H 'Content-Type: application/json' \
  -b cookies.txt \
  -d '{
    "config": { "description": "Updated description" },
    "api_key": "app_custom_key_here"
  }'
```

**Python**
```python
# Toggle status to active
resp = session.post(
    'http://your_site/dashboard/api.php?action=update',
    params={'id': 2},
    json={'status': 'active'}
)
print(resp.json())

# Update config
resp = session.post(
    'http://your_site/dashboard/api.php?action=update',
    params={'id': 2},
    json={
        'config': {'description': 'New description'},
        'api_key': 'app_custom_key'
    }
)
print(resp.json())
```

**PHP**
```php
<?php
$data = ['status' => 'inactive'];
$ch = curl_init('http://your_site/dashboard/api.php?action=update&id=2');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
echo curl_exec($ch);
curl_close($ch);
```

**JavaScript**
```javascript
const resp = await fetch('api.php?action=update&id=2', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({ status: 'inactive' })
});
const result = await resp.json();
console.log(result);
```

**Tested Response (200)**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "test-api-doc",
        "folder_path": "test-api-doc",
        "api_key": "app_aB4cD7eF0gH1iJ2k",
        "database_name": "test_api_doc_",
        "status": "inactive",
        "config": {
            "description": "Updated via API test"
        },
        "created_at": "2026-06-18 14:30:05",
        "updated_at": "2026-06-18 14:30:10"
    },
    "meta": {
        "message": "App updated successfully"
    }
}
```

---

## 7. POST — Delete App

Deletes an application, drops all its database tables (by table prefix), and removes its folder.

**Endpoint:** `POST api.php?action=delete&id={id}`

**Query Parameters:**

| Param | Type   | Required |
|-------|--------|----------|
| id    | number | Yes      |

**cURL**
```bash
curl -X POST 'http://your_site/dashboard/api.php?action=delete&id=2' \
  -b cookies.txt
```

**Python**
```python
resp = session.post(
    'http://your_site/dashboard/api.php?action=delete',
    params={'id': 2}
)
print(resp.json())
```

**PHP**
```php
<?php
$ch = curl_init('http://your_site/dashboard/api.php?action=delete&id=2');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
echo curl_exec($ch);
curl_close($ch);
```

**JavaScript**
```javascript
const resp = await fetch('api.php?action=delete&id=2', {
    method: 'POST',
    credentials: 'same-origin'
});
const result = await resp.json();
console.log(result);
```

**Tested Response (200)**
```json
{
    "success": true,
    "data": null,
    "meta": {
        "message": "App deleted successfully"
    }
}
```

---

[← Back to Index](./index.md)
