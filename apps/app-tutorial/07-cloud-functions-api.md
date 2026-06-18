---
title: Cloud Functions API
section: 07
---

## 7. Cloud Functions API

Execute custom PHP functions stored in the `functions/` directory. Function name must match filename (without `.php` extension).

### Execute a Custom Function

**Endpoint:** `POST your_site/functions/index.php/{function_name}`

**curl:**
```bash
curl -X POST your_site/functions/index.php/hello \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"name": "John"}'
```

**Python:**
```python
def call_function(function_name: str, api_key: str, payload: dict = None) -> dict:
    """Execute a cloud function."""
    response = requests.post(
        f"your_site/functions/index.php/{function_name}",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=payload or {}
    )
    return response.json()

result = call_function("hello", "your_api_key_here", {"name": "John"})
print(result)
```

**PHP:**
```php
function callFunction(string $functionName, string $apiKey, array $payload = []): array {
    $ch = curl_init("your_site/functions/index.php/{$functionName}");
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

$result = callFunction("hello", "your_api_key_here", ["name" => "John"]);
print_r($result);
```

**JavaScript:**
```javascript
async function callFunction(functionName, apiKey, payload = {}) {
  const response = await fetch(`your_site/functions/index.php/${functionName}`, {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });
  return response.json();
}

callFunction("hello", "your_api_key_here", { name: "John" }).then(console.log);
```

### Example Function (`functions/hello.php`)

```php
<?php
// $payload variable contains the JSON body as an associative array
return [
    'status' => 'success',
    'message' => 'Hello ' . ($payload['name'] ?? 'World'),
    'timestamp' => date('Y-m-d H:i:s')
];
```

← [[06-notifications-api]] | [[index]] | → [[08-admin-api]]
