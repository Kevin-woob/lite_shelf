---
title: Authentication
section: 02
---

## 2. Authentication

All API endpoints (except health/status) require the `X-API-Key` header. API keys are validated against SHA-256 hashes stored in the database.

### Validating Your API Key (Admin API)

**Endpoint:** `POST your_site/admin/api.php?route=/validate-admin-key`

**curl:**
```bash
curl -X POST your_site/admin/api.php?route=/validate-admin-key \
  -H "Content-Type: application/json" \
  -d '{"api_key": "your_api_key_here"}'
```

**Response (200 OK):**
```json
{
    "success": true,
    "valid": true,
    "is_admin": true,
    "name": "Test Admin Key"
}
```

**Python:**
```python
import requests

def validate_admin_key(api_key: str) -> dict:
    """Validate if an API key has admin privileges."""
    response = requests.post(
        "your_site/admin/api.php?route=/validate-admin-key",
        json={"api_key": api_key}
    )
    return response.json()

result = validate_admin_key("your_api_key_here")
print(result)
```

**PHP:**
```php
function validateAdminKey(string $apiKey): array {
    $ch = curl_init("your_site/admin/api.php?route=/validate-admin-key");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(["api_key" => $apiKey]),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}

$result = validateAdminKey("your_api_key_here");
print_r($result);
```

**JavaScript (Node.js):**
```javascript
async function validateAdminKey(apiKey) {
  const response = await fetch("your_site/admin/api.php?route=/validate-admin-key", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ api_key: apiKey })
  });
  return response.json();
}

validateAdminKey("your_api_key_here").then(console.log);
```

← [[01-system-overview]] | [[index]] | → [[03-data-collections-api]]
