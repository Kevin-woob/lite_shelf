# Quick Reference Card

| Action | Method | Endpoint | Auth? | Body |
|--------|--------|----------|-------|------|
| Login | POST | `?action=login` | No | `{ username, password }` |
| Logout | POST | `?action=logout` | No | — |
| List | GET | `?action=list` | Yes | — |
| Get | GET | `?action=get&id=X` | Yes | — |
| Create | POST | `?action=create` | Yes | `{ name, config? }` |
| Update | POST | `?action=update&id=X` | Yes | Any app field |
| Delete | POST | `?action=delete&id=X` | Yes | — |
| Stats | GET | `?action=stats` | Yes | — |

---

## Base URL

```
http://your-domain/dashboard/api.php
```

## Default Credentials

| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

> Change these immediately in production.

## App Name Rules

- Length: 3–50 characters
- Regex: `/^[a-zA-Z0-9_-]+$/`
- Must be unique

## Valid Status Values

| Status     | Description |
|------------|-------------|
| `active`   | Running normally |
| `inactive` | Disabled |
| `error`    | Provisioning failed |

---

[← Back to Index](./index.md)
