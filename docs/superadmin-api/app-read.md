# App Read Endpoints

## 3. GET — List All Apps

Returns all applications with decoded JSON config.

**Endpoint:** `GET api.php?action=list`

**Query Parameters:** None

**cURL**
```bash
curl 'http://your_site/dashboard/api.php?action=list' \
  -b cookies.txt
```

**Python**
```python
resp = session.get('http://your_site/dashboard/api.php?action=list')
apps = resp.json()
for app in apps.get('data', []):
    print(f"  #{app['id']}  {app['name']}  [{app['status']}]")
```

**PHP**
```php
<?php
$ch = curl_init('http://your_site/dashboard/api.php?action=list');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
$result = json_decode(curl_exec($ch), true);
curl_close($ch);

foreach ($result['data'] as $app) {
    echo "  #{$app['id']}  {$app['name']}  [{$app['status']}]\n";
}
```

**JavaScript**
```javascript
const resp = await fetch('api.php?action=list', {
    credentials: 'same-origin'
});
const result = await resp.json();
result.data.forEach(app => {
    console.log(`  #${app.id}  ${app.name}  [${app.status}]`);
});
```

**Tested Response (200)**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "test9",
            "folder_path": "test9",
            "api_key": "app_xK9mP2nQ5rT8wY3v",
            "database_name": "test9_",
            "status": "active",
            "config": {
                "description": "Test application"
            },
            "created_at": "2025-01-15 10:30:00",
            "updated_at": "2025-01-15 10:30:00"
        }
    ]
}
```

---

## 4. GET — Get Single App

Returns one application by ID.

**Endpoint:** `GET api.php?action=get&id={id}`

**Query Parameters:**

| Param | Type   | Required |
|-------|--------|----------|
| id    | number | Yes      |

**cURL**
```bash
curl 'http://your_site/dashboard/api.php?action=get&id=1' \
  -b cookies.txt
```

**Python**
```python
resp = session.get('http://your_site/dashboard/api.php?action=get',
                   params={'id': 1})
print(resp.json())
```

**PHP**
```php
<?php
$ch = curl_init('http://your_site/dashboard/api.php?action=get&id=1');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
echo curl_exec($ch);
curl_close($ch);
```

**JavaScript**
```javascript
const resp = await fetch('api.php?action=get&id=1', {
    credentials: 'same-origin'
});
const result = await resp.json();
console.log(result.data);
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
        "status": "active",
        "config": {
            "description": "API documentation test app"
        },
        "created_at": "2026-06-18 14:30:05",
        "updated_at": "2026-06-18 14:30:05"
    }
}
```

---

[← Back to Index](./index.md)
