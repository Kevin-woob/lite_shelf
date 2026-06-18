# Lite_Shelf

> **AI-native backend platform — auto-provision isolated BaaS instances for every app your agent builds.**

Lite_Shelf is a self-hosted backend-as-a-service designed for AI-assisted development. One API call gives your LLM agent a complete, isolated backend — document collections, file storage, authentication, cloud functions, and access control. No scaffolding. No rebuilding CRUD. Just hand the API docs to your agent and let it focus on shipping.

---

## The Problem

Every time your AI agent starts a new app, it wastes context on backend setup: creating auth flows, writing CRUD endpoints, managing databases, configuring storage. All of it is boilerplate. All of it distracts from the actual product.

Lite_Shelf eliminates that overhead entirely.

## How It Works

You deploy Lite_Shelf **once**. From then on, every new app your agent builds gets its own ready-made backend:

1. **Deploy Lite_Shelf** — one-time setup on any PHP/MySQL server.
2. **Create a new app** — one API call provisions an isolated database, file storage, auth system, and API key.
3. **Give the API docs to your LLM agent** — paste the tutorial or quick reference into your agent's context.
4. **Your agent ships the frontend** — mobile app, web app, desktop software — with zero backend setup required.

Your agent never writes backend boilerplate again. It just calls the API and builds.

---

## Architecture

```
┌─────────────────────────────────────────────────┐
│              Superadmin Dashboard               │
│  /dashboard/api.php                              │
│  ┌───────────────────────────────────────────┐  │
│  │  Provision / Manage App Instances         │  │
│  │  Auto-generate: DB + storage + API key    │  │
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

Your agent interacts with any app instance via REST API — no backend code needed.

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
