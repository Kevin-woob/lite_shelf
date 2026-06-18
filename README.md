# Lite_Shelf

> **Your own lightweight Backend-as-a-Service — in a single PHP/MySQL stack.**

Lite_Shelf is a self-hosted, multi-tenant application platform that gives every app its own isolated database, file storage, authentication, cloud functions, and fine-grained access control — all provisioned with a single API call. Think of it as a **lightweight, open-source backend platform**, running entirely on PHP 8.x and MySQL.

---

## Why Lite_Shelf?

Building backends for multiple projects usually means managing separate servers, databases, and auth systems. Lite_Shelf solves this with a **two-tier architecture** that puts you in full control:

- **Superadmin Dashboard** — provision and manage unlimited app instances from one place. Each app gets an auto-generated API key, a dedicated database (with isolated table prefixes), and its own file structure.
- **Per-App API** — every app instance ships with a complete BaaS feature set: document collections, file storage with folder management, push-style notifications, serverless cloud functions, and role-based access control — all accessible via a clean REST API.

No vendor lock-in. No monthly fees. No black boxes. Just clean, auditable PHP code you can deploy anywhere.

---

## The AI Developer Advantage

**Set up your backend once. Let your LLM build every app after that.**

Here's the workflow that changes everything:

1. **Deploy Lite_Shelf** — one-time setup. You now have a fully working backend-as-a-service.
2. **Create a new app** — one API call provisions a fresh database, storage, and auth system.
3. **Feed the API reference to your LLM agent** — give it the tutorial files. That's it.
4. **Your LLM builds the app** — mobile app, web app, desktop software, whatever — with zero backend rebuilds.

Every new project gets a ready-made backend with collections, storage, notifications, cloud functions, and access control. No scaffolding. No rewriting CRUD. No "let me set up auth again." Just hand your LLM the API docs and let it focus on the product.

**You're not building backends anymore. You're shipping products.**

---

## Architecture at a Glance

```
┌─────────────────────────────────────────────────┐
│              Superadmin Dashboard               │
│  /dashboard/api.php                              │
│  ┌───────────────────────────────────────────┐  │
│  │  Create / List / Update / Delete Apps     │  │
│  │  Auto-provision: DB tables + folders + key│  │
│  │  Aggregate stats & monitoring             │  │
│  └───────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────┘
                       │ provisions
          ┌────────────┼────────────┐
          ▼            ▼            ▼
     ┌────────┐   ┌────────┐   ┌────────┐
     │ App A  │   │ App B  │   │ App C  │
     │ index  │   │ index  │   │ index  │
     │ admin  │   │ admin  │   │ admin  │
     │ funcs  │   │ funcs  │   │ funcs  │
     └───┬────┘   └───┬────┘   └───┬────┘
         │            │            │
     ┌───▼────┐   ┌───▼────┐   ┌───▼────┐
     │ DB: a_ │   │ DB: b_ │   │ DB: c_ │
     │ files/ │   │ files/ │   │ files/ │
     └────────┘   └────────┘   └────────┘
```

---

## Features

### Superadmin Dashboard

| Feature | Description |
|---|---|
| **App Provisioning** | One call creates a new app — copies template, generates API key, creates database tables with unique prefix, seeds admin key |
| **App Management** | List, get, update, and delete apps with full lifecycle control |
| **Auto-Cleanup** | Deleting an app drops all its database tables and removes its folder |
| **Dashboard Stats** | Aggregate statistics across all managed apps |
| **Session Auth** | Secure PHP session-based authentication for all admin endpoints |

### Per-App Backend API

| Feature | Description |
|---|---|
| **Data Collections** | Full CRUD on JSON document collections — create, read, update, delete, and query documents |
| **File Storage** | Upload, download, move, copy, rename, and delete files with database-tracked metadata |
| **Folder Management** | Create, rename, move, copy, and delete storage folders — list all paths |
| **Notifications** | System-level notification create, list, mark-as-read, and delete |
| **Cloud Functions** | Deploy and execute custom server-side PHP functions via POST |
| **Access Control** | Fine-grained collection-level and folder-level permissions per API key |
| **API Key Auth** | SHA-256 hashed API keys via `X-API-Key` header — admin and standard key roles |
| **Admin Panel** | Full admin UI/API for managing users, API keys, collections, storage, and permissions |

---

## Quick Start

### 1. Deploy Lite_Shelf

Place the project on any PHP 8.x + MySQL server (Laragon, XAMPP, LAMP, Docker — it doesn't matter).

### 2. Access the Dashboard

Navigate to `http://your-domain/dashboard/api.php` and authenticate:

```bash
curl -X POST 'http://localhost/dashboard/api.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"username": "admin", "password": "admin123"}'
```

### 3. Create Your First App

```bash
curl -X POST 'http://localhost/dashboard/api.php?action=create' \
  -H 'Content-Type: application/json' \
  -b cookies.txt \
  -d '{"name": "my-app", "config": {"description": "My first app"}}'
```

This single call gives you:
- A unique API key
- A dedicated database with isolated table prefix
- A file structure ready for storage and cloud functions

### 4. Start Building

Use your app's API key to interact with collections, storage, notifications, and cloud functions:

```bash
curl 'http://localhost/my-app/index.php/collections/users/documents' \
  -H 'X-API-Key: your_app_api_key'
```

---

## API Documentation

### Superadmin Dashboard API

Full documentation with examples in cURL, Python, PHP, and JavaScript is available at:

- [Superadmin API Tutorial](dashboard/API_TUTORIAL.md)

### Per-App API (Lite_Shelf BaaS)

Complete API reference for each app instance:

- [System Overview](apps/test9/test9-tutorial/01-system-overview.md)
- [Authentication](apps/test9/test9-tutorial/02-authentication.md)
- [Data Collections API](apps/test9/test9-tutorial/03-data-collections-api.md)
- [Storage API](apps/test9/test9-tutorial/04-storage-api.md)
- [Storage Folders API](apps/test9/test9-tutorial/05-storage-folders-api.md)
- [Notifications API](apps/test9/test9-tutorial/06-notifications-api.md)
- [Cloud Functions API](apps/test9/test9-tutorial/07-cloud-functions-api.md)
- [Admin API](apps/test9/test9-tutorial/08-admin-api.md)
- [Access Control](apps/test9/test9-tutorial/09-access-control.md)
- [Quick Reference](apps/test9/test9-tutorial/quick-reference.md)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x with PDO |
| Database | MySQL (table-prefix isolation per app) |
| Authentication | API keys (SHA-256 hashed) + PHP sessions |
| Storage | Local filesystem with database metadata |
| Architecture | Multi-tenant, isolated per app |

---

## License

Lite_Shelf is open source. Deploy it, modify it, make it yours.
