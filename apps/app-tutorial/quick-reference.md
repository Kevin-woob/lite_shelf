---
title: Quick Reference - Endpoint List
section: quick-reference
---

## Quick Reference: Complete Endpoint List

### Main API (index.php)

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| GET | `/` or `/health` | No | Health check |
| GET | `/collections/{name}/documents` | Yes | List/query documents |
| POST | `/collections/{name}/documents` | Yes | Create document |
| GET | `/collections/{name}/documents/{id}` | Yes | Get document |
| PUT | `/collections/{name}/documents/{id}` | Yes | Replace document |
| PATCH | `/collections/{name}/documents/{id}` | Yes | Update document |
| DELETE | `/collections/{name}/documents/{id}` | Yes | Delete document |
| POST | `/storage/upload` | Yes | Upload file |
| GET | `/storage/files/{filename}` | No | Get file |
| DELETE | `/storage/files/{filename}` | Yes | Delete file |
| GET | `/notifications` | No | List notifications |
| POST | `/notifications` | Yes | Create notification |
| DELETE | `/notifications/{id}` | No | Delete notification |
| POST | `/notifications/{id}/read` | No | Mark as read |

### Cloud Functions (functions/index.php)

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| POST | `/{function_name}` | Yes | Execute custom function |

### Admin API (admin/api.php?route=...)

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| POST | `/login` | No | Admin login |
| POST | `/logout` | Session | Admin logout |
| POST | `/validate-admin-key` | No | Validate admin key |
| GET | `/session-key` | Session | Check session |
| GET | `/stats` | Admin | Dashboard stats |
| GET | `/users` | Admin | List users |
| POST | `/users` | Admin | Create user |
| DELETE | `/users/{id}` | Admin | Delete user |
| GET | `/api-keys` | Admin | List API keys |
| POST | `/api-keys` | Admin | Create API key |
| POST | `/api-keys/{id}/revoke` | Admin | Revoke key |
| POST | `/api-keys/{id}/set-admin` | Admin | Set admin key |
| GET | `/collections` | Admin | List collections |
| POST | `/collections` | Admin | Create collection |
| GET | `/collections/{name}` | Admin | Get collection items |
| POST | `/collections/{name}/documents` | Admin | Create document |
| GET | `/collections/{name}/documents/{id}` | Admin | Get document |
| PATCH | `/collections/{name}/documents/{id}` | Admin | Update document |
| DELETE | `/collections/{name}/documents/{id}` | Admin | Delete document |
| PATCH | `/collections/{name}` | Admin | Rename collection |
| POST | `/collections/{name}/copy` | Admin | Copy collection |
| DELETE | `/collections/{name}` | Admin | Delete collection |
| POST | `/storage/upload` | Admin | Upload file |
| GET | `/storage/files` | Admin | List files |
| GET | `/storage/files/{id}/download` | Admin | Download file |
| DELETE | `/storage/files/{id}` | Admin | Delete file |
| POST | `/storage/files/{id}/move` | Admin | Move file |
| POST | `/storage/files/{id}/copy` | Admin | Copy file |
| POST | `/storage/files/{id}/rename` | Admin | Rename file |
| GET | `/storage/folders` | Admin | List folders |
| GET | `/storage/folders/all-paths` | Admin | List all paths |
| POST | `/storage/folders` | Admin | Create folder |
| PATCH | `/storage/folders/{path}` | Admin | Rename folder |
| DELETE | `/storage/folders/{path}` | Admin | Delete folder |
| POST | `/storage/folders/{path}/move` | Admin | Move folder |
| POST | `/storage/folders/{path}/copy` | Admin | Copy folder |
| POST | `/api-keys/{id}/grant-collection` | Admin | Grant collection access |
| POST | `/api-keys/{id}/revoke-collection` | Admin | Revoke collection access |
| POST | `/api-keys/{id}/grant-folder` | Admin | Grant folder access |
| POST | `/api-keys/{id}/revoke-folder` | Admin | Revoke folder access |
| GET | `/api-keys/{id}/permissions` | Admin | Get key permissions |

← [[09-access-control]] | [[index]]
