# Stats Endpoint

## 8. GET — Stats

Returns aggregated statistics for the dashboard.

**Endpoint:** `GET api.php?action=stats`

**cURL**
```bash
curl 'http://your_site/dashboard/api.php?action=stats' \
  -b cookies.txt
```

**Python**
```python
resp = session.get('http://your_site/dashboard/api.php?action=stats')
print(resp.json())
```

**PHP**
```php
<?php
$ch = curl_init('http://your_site/dashboard/api.php?action=stats');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
]);
echo curl_exec($ch);
curl_close($ch);
```

**JavaScript**
```javascript
const resp = await fetch('api.php?action=stats', {
    credentials: 'same-origin'
});
const result = await resp.json();
console.log(result);
```

**Tested Response (200)**
```json
{
    "success": true,
    "data": {
        "total_apps": 2,
        "active_apps": 1,
        "inactive_apps": 1,
        "error_apps": 0
    }
}
```

---

[← Back to Index](./index.md)
