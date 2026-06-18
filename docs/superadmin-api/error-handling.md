# Error Handling

## Example error responses

**Missing required field (400)**
```json
{
    "success": false,
    "error": {
        "code": "BAD_REQUEST",
        "message": "App name is required"
    }
}
```

**App not found (404)**
```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "App not found"
    }
}
```

**Unauthorized (401)**
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

## Validation rules for app name

- Length: 3–50 characters
- Allowed characters: letters, numbers, dashes (`-`), underscores (`_`)
- Regex: `/^[a-zA-Z0-9_-]+$/`
- Must be unique (cannot duplicate an existing app name or folder)

---

## Valid status values

| Status     | Description |
|------------|-------------|
| `active`   | App is running normally |
| `inactive` | App is disabled |
| `error`    | App provisioning failed |

---

[← Back to Index](./index.md)
