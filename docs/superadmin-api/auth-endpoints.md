# Authentication Endpoints

## 1. POST — Login

Authenticates a user and starts a session.

**Endpoint:** `POST api.php?action=login`

**Body:** JSON or form data with `username` and `password`.

**cURL**
```bash
curl -X POST 'http://your_site/dashboard/api.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"username": "admin", "password": "admin123"}' \
  -c cookies.txt -b cookies.txt
```

**Python**
```python
import requests

session = requests.Session()
resp = session.post(
    'http://your_site/dashboard/api.php?action=login',
    json={'username': 'admin', 'password': 'admin123'}
)
print(resp.json())
# Keep using `session` for subsequent requests — cookies are preserved.
```

**PHP**
```php
<?php
// Login via API (from an external script)
$ch = curl_init('http://your_site/dashboard/api.php?action=login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'username' => 'admin',
        'password' => 'admin123'
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEJAR => '/tmp/cookies.txt',
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```

**JavaScript (Browser / Node with cookie handling)**
```javascript
// Browser — session cookies are managed automatically by the browser.
const resp = await fetch('api.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',          // important for session cookies
    body: JSON.stringify({
        username: 'admin',
        password: 'admin123'
    })
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
        "message": "Login successful"
    }
}
```

---

## 2. POST — Logout

Destroys the current session.

**Endpoint:** `POST api.php?action=logout`

**cURL**
```bash
curl -X POST 'http://your_site/dashboard/api.php?action=logout' \
  -c cookies.txt -b cookies.txt
```

**Python**
```python
resp = session.post('http://your_site/dashboard/api.php?action=logout')
print(resp.json())
session.close()  # clears cookies
```

**PHP**
```php
<?php
$ch = curl_init('http://your_site/dashboard/api.php?action=logout');
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
const resp = await fetch('api.php?action=logout', {
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
        "message": "Logged out successfully"
    }
}
```

---

## Unauthorized Access Test

When accessing protected endpoints without a valid session:

**Request:** `GET api.php?action=list` (no session)

**Tested Response (401)**
```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "Please login to access the dashboard API"
    }
}
```

---

[← Back to Index](./index.md)
