---
title: System Overview
section: 01
---

## 1. System Overview

### Architecture

- **Backend:** PHP 8.x with PDO
- **Database:** MySQL with table prefix `{prefix}_`
- **Auth:** API Key via `X-API-Key` header (SHA-256 hashed)
- **Access Control:** Fine-grained collection and folder permissions
- **Storage:** Local filesystem with database metadata

### Base URLs

| Entry Point | URL Pattern |
|---|---|
| Main API | `your_site/index.php` (routes: `/collections/...`, `/storage/...`, `/notifications`) |
| Admin API | `your_site/admin/api.php?route=...` |
| Cloud Functions | `your_site/functions/index.php/{function_name}` |

### Getting Started

1. Create an admin API key via the admin panel
2. Use the API key in `X-API-Key` header for all requests
3. Admin API calls require admin-level API keys

### Health Check Response

```bash
curl your_site/
```

**Response (200 OK):**
```json
{
    "status": "ok",
    "message": "Lite_Shelf API",
    "version": "1.0.0"
}
```

```bash
curl your_site/health
```

**Response (200 OK):**
```json
{
    "status": "healthy"
}
```

[[index]] | → [[02-authentication]]
