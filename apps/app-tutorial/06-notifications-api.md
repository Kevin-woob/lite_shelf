---
title: Notifications API
section: 06
---

## 6. Notifications API

Polling-based notification system stored in MySQL.

### 6.1 List Notifications

**Endpoint:** `GET your_site/notifications`

**curl:**
```bash
curl "your_site/notifications?limit=50&offset=0" \
  -H "X-API-Key: your_api_key_here"

# Get only unread notifications
curl "your_site/notifications?unread" \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def list_notifications(api_key: str, limit: int = 50, offset: int = 0, unread_only: bool = False) -> dict:
    """List notifications."""
    params = {"limit": limit, "offset": offset}
    if unread_only:
        params["unread"] = ""
    response = requests.get(
        "your_site/notifications",
        headers={"X-API-Key": api_key},
        params=params
    )
    return response.json()
```

**PHP:**
```php
function listNotifications(string $apiKey, int $limit = 50, int $offset = 0, bool $unreadOnly = false): array {
    $params = ["limit" => $limit, "offset" => $offset];
    if ($unreadOnly) {
        $params["unread"] = "";
    }
    $query = http_build_query($params);
    $ch = curl_init("your_site/notifications?{$query}");
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
async function listNotifications(apiKey, limit = 50, offset = 0, unreadOnly = false) {
  const params = new URLSearchParams({ limit, offset });
  if (unreadOnly) params.append("unread", "");
  const response = await fetch(`your_site/notifications?${params}`, {
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 6.2 Send a Notification

**Endpoint:** `POST your_site/notifications`

**curl:**
```bash
curl -X POST your_site/notifications \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"message": "Task completed!", "target_user_id": 42, "data": {"task_id": 123}}'
```

**Python:**
```python
def send_notification(api_key: str, message: str, target_user_id: int = None, data: dict = None) -> dict:
    """Send a notification."""
    payload = {"message": message}
    if target_user_id is not None:
        payload["target_user_id"] = target_user_id
    if data is not None:
        payload["data"] = data
    response = requests.post(
        "your_site/notifications",
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        json=payload
    )
    return response.json()
```

**PHP:**
```php
function sendNotification(string $apiKey, string $message, ?int $targetUserId = null, ?array $data = null): array {
    $payload = ["message" => $message];
    if ($targetUserId !== null) $payload["target_user_id"] = $targetUserId;
    if ($data !== null) $payload["data"] = $data;
    $ch = curl_init("your_site/notifications");
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
```

**JavaScript:**
```javascript
async function sendNotification(apiKey, message, targetUserId = null, data = null) {
  const payload = { message };
  if (targetUserId !== null) payload.target_user_id = targetUserId;
  if (data !== null) payload.data = data;
  const response = await fetch("your_site/notifications", {
    method: "POST",
    headers: {
      "X-API-Key": apiKey,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  });
  return response.json();
}
```

**Example Response (201 Created):**
```json
{
    "success": true,
    "id": 9
}
```

### 6.3 Mark Notification as Read

**Endpoint:** `POST your_site/notifications/{id}/read`

**curl:**
```bash
curl -X POST your_site/notifications/15/read \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def mark_notification_read(notification_id: int, api_key: str) -> dict:
    """Mark a notification as read."""
    response = requests.post(
        f"your_site/notifications/{notification_id}/read",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function markNotificationRead(int $notificationId, string $apiKey): array {
    $ch = curl_init("your_site/notifications/{$notificationId}/read");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["X-API-Key: {$apiKey}"]
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}
```

**JavaScript:**
```javascript
async function markNotificationRead(notificationId, apiKey) {
  const response = await fetch(`your_site/notifications/${notificationId}/read`, {
    method: "POST",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

### 6.4 Delete a Notification

**Endpoint:** `DELETE your_site/notifications/{id}`

**curl:**
```bash
curl -X DELETE your_site/notifications/15 \
  -H "X-API-Key: your_api_key_here"
```

**Python:**
```python
def delete_notification(notification_id: int, api_key: str) -> dict:
    """Delete a notification."""
    response = requests.delete(
        f"your_site/notifications/{notification_id}",
        headers={"X-API-Key": api_key}
    )
    return response.json()
```

**PHP:**
```php
function deleteNotification(int $notificationId, string $apiKey): array {
    $ch = curl_init("your_site/notifications/{$notificationId}");
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
async function deleteNotification(notificationId, apiKey) {
  const response = await fetch(`your_site/notifications/${notificationId}`, {
    method: "DELETE",
    headers: { "X-API-Key": apiKey }
  });
  return response.json();
}
```

← [[05-storage-folders-api]] | [[index]] | → [[07-cloud-functions-api]]
