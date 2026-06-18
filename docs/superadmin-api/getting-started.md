# Getting Started

## Base URL

```
http://your-domain/dashboard/api.php
```

All endpoints go through `api.php` using the `?action=` query parameter.

---

## Authentication

All endpoints (except `login` and `logout`) require a valid PHP session.
- Login sets `$_SESSION['dashboard_logged_in'] = true`
- Protected endpoints return **401 Unauthorized** without a valid session

**Default credentials** (from `config/admin.php`):
| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

> Change these immediately in production.

---

## Response Format

### Success
```json
{
    "success": true,
    "data": { ... },
    "meta": { "message": "..." }
}
```

### Error
```json
{
    "success": false,
    "error": {
        "code": "BAD_REQUEST",
        "message": "Human-readable error message",
        "details": {}
    }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200  | OK |
| 201  | Created |
| 400  | Bad Request |
| 401  | Unauthorized |
| 404  | Not Found |
| 500  | Server Error |

---

[← Back to Index](./index.md)
