<?php
session_start();
if (!isset($_SESSION['dashboard_logged_in']) || $_SESSION['dashboard_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite_Shelf</title>
    <style>
        :root {
            --primary: #3a7cb8;
            --primary-dark: #1e3a5f;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
            background: var(--gray-100);
            color: var(--gray-900);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            padding: 0 32px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logo {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
        }

        /* Main Content */
        .main {
            max-width: 1280px;
            margin: 0 auto;
            padding: 32px;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card.total { border-left-color: var(--primary); }
        .stat-card.active { border-left-color: var(--success); }
        .stat-card.inactive { border-left-color: var(--warning); }
        .stat-card.error { border-left-color: var(--danger); }

        .stat-label {
            font-size: 13px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .toolbar h2 {
            font-size: 18px;
            color: var(--gray-700);
        }

        .create-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58, 124, 184, 0.35);
        }

        /* Apps Table */
        .table-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .apps-table {
            width: 100%;
            border-collapse: collapse;
        }

        .apps-table th {
            background: var(--gray-50);
            padding: 14px 20px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            font-weight: 600;
            border-bottom: 1px solid var(--gray-200);
        }

        .apps-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
            vertical-align: middle;
        }

        .apps-table tr {
            transition: background 0.15s;
        }

        .apps-table tbody tr:hover {
            background: var(--gray-50);
        }

        .apps-table tbody tr:last-child td {
            border-bottom: none;
        }

        .app-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        .app-folder {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.active::before { background: var(--success); }

        .status-badge.inactive {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.inactive::before { background: var(--warning); }

        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-badge.error::before { background: var(--danger); }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 7px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .action-btn.view {
            background: #e0f2fe;
            color: #0369a1;
        }
        .action-btn.view:hover { background: #bae6fd; }

        .action-btn.configure {
            background: #f3e8ff;
            color: #7c3aed;
        }
        .action-btn.configure:hover { background: #ddd6fe; }

        .action-btn.launch {
            background: #d1fae5;
            color: #065f46;
        }
        .action-btn.launch:hover { background: #a7f3d0; }

        .action-btn.toggle {
            background: #fef3c7;
            color: #92400e;
        }
        .action-btn.toggle:hover { background: #fde68a; }

        .action-btn.delete {
            background: #fee2e2;
            color: #991b1b;
        }
        .action-btn.delete:hover { background: #fecaca; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }

        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--gray-700);
        }

        .empty-state p {
            font-size: 14px;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s;
            padding: 20px;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: var(--radius);
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            transform: scale(0.95) translateY(20px);
            transition: transform 0.25s;
        }

        .modal-overlay.show .modal {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 24px 24px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
            color: var(--gray-900);
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--gray-100);
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        .modal-close:hover {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body .form-group {
            margin-bottom: 20px;
        }

        .modal-body label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .modal-body input,
        .modal-body textarea,
        .modal-body select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            outline: none;
            font-family: inherit;
        }

        .modal-body input:focus,
        .modal-body textarea:focus,
        .modal-body select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(58, 124, 184, 0.12);
        }

        .modal-body textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            border: none;
        }

        .modal-btn.secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .modal-btn.secondary:hover { background: var(--gray-200); }

        .modal-btn.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        .modal-btn.primary:hover {
            box-shadow: 0 4px 12px rgba(58, 124, 184, 0.3);
        }

        .modal-btn.danger {
            background: var(--danger);
            color: white;
        }
        .modal-btn.danger:hover {
            background: #dc2626;
        }

        .modal-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Detail View */
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 13px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .detail-value {
            font-size: 14px;
            color: var(--gray-900);
            font-weight: 500;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: white;
            padding: 14px 20px;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            min-width: 280px;
        }

        .toast.success { border-left: 4px solid var(--success); }
        .toast.error { border-left: 4px solid var(--danger); }
        .toast.info { border-left: 4px solid var(--primary); }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--gray-500);
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header { padding: 0 16px; }
            .main { padding: 20px 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-card { padding: 16px; }
            .stat-value { font-size: 24px; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .create-btn { justify-content: center; }
            .apps-table { font-size: 13px; }
            .apps-table th, .apps-table td { padding: 10px 12px; }
            .action-group { flex-direction: column; }
            .action-btn { text-align: center; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 16px; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="header-logo">&#9776;</div>
            <h1>Lite_Shelf</h1>
        </div>
        <button class="logout-btn" onclick="handleLogout()">Sign Out</button>
    </header>

    <!-- Main Content -->
    <main class="main">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total Apps</div>
                <div class="stat-value" id="statTotal">-</div>
            </div>
            <div class="stat-card active">
                <div class="stat-label">Active</div>
                <div class="stat-value" id="statActive">-</div>
            </div>
            <div class="stat-card inactive">
                <div class="stat-label">Inactive</div>
                <div class="stat-value" id="statInactive">-</div>
            </div>
            <div class="stat-card error">
                <div class="stat-label">Error</div>
                <div class="stat-value" id="statError">-</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <h2>Applications</h2>
            <button class="create-btn" onclick="openCreateModal()">
                <span>+</span> Create New App
            </button>
        </div>

        <!-- Apps Table -->
        <div class="table-container">
            <table class="apps-table">
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="appsTableBody">
                    <tr>
                        <td colspan="4">
                            <div class="loading">
                                <div class="spinner"></div>
                                <p>Loading applications...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Create App Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Create New Application</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form id="createAppForm" onsubmit="handleCreateApp(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="appName">Application Name *</label>
                        <input type="text" id="appName" name="name" placeholder="e.g., my-app" required pattern="[a-zA-Z0-9_-]+">
                    </div>
                    <div class="form-group">
                        <label for="appDescription">Description (optional)</label>
                        <textarea id="appDescription" name="description" placeholder="Brief description of your application..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="modal-btn primary" id="createBtn">Create App</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View/Configure App Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="viewModalTitle">App Details</h3>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Populated dynamically -->
            </div>
            <div class="modal-footer" id="viewModalFooter">
                <button class="modal-btn secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Delete Application</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteAppName"></strong>?</p>
                <p style="margin-top: 8px; color: var(--gray-500); font-size: 14px;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="modal-btn danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        let apps = [];
        let deleteTargetId = null;

        // Auth check
        if (!sessionStorage.getItem('dashboard_auth')) {
            window.location.href = 'login.php';
        }

        // Load apps on page load
        document.addEventListener('DOMContentLoaded', loadApps);

        // Modal helpers
        function openModal(id) {
            document.getElementById(id).classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
            document.body.style.overflow = '';
        }

        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(m => {
                    m.classList.remove('show');
                });
                document.body.style.overflow = '';
            }
        });

        // Toast notifications
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icons = { success: '\u2705', error: '\u274C', info: '\u2139\uFE0F' };
            toast.innerHTML = `<span>${icons[type] || '\u2139\uFE0F'}</span><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // API call helper
        async function apiCall(action, id = null, body = null) {
            let url = `api.php?action=${action}`;
            if (id !== null) url += `&id=${id}`;
            const options = { method: body ? 'POST' : 'GET', headers: {} };
            if (body) {
                if (body instanceof FormData) {
                    options.body = body;
                } else {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }
            }
            const response = await fetch(url, options);
            const result = await response.json();
            return result;
        }

        // Helper to extract error message from API response
        function getErrorMessage(result) {
            if (typeof result.error === 'string') return result.error;
            if (result.error && typeof result.error.message === 'string') return result.error.message;
            return 'An error occurred';
        }

        // Load apps
        async function loadApps() {
            try {
                const result = await apiCall('list');
                if (result.success) {
                    apps = result.data || [];
                    updateStats();
                    renderTable();
                } else {
                    showToast(getErrorMessage(result), 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }

        // Update stats
        function updateStats() {
            const total = apps.length;
            const active = apps.filter(a => a.status === 'active').length;
            const inactive = apps.filter(a => a.status === 'inactive').length;
            const error = apps.filter(a => a.status === 'error').length;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statActive').textContent = active;
            document.getElementById('statInactive').textContent = inactive;
            document.getElementById('statError').textContent = error;
        }

        // Render table
        function renderTable() {
            const tbody = document.getElementById('appsTableBody');

            if (apps.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="icon">&#128230;</div>
                                <h3>No applications yet</h3>
                                <p>Click "Create New App" to get started.</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = apps.map(app => {
                const created = new Date(app.created_at).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'short', day: 'numeric'
                });

                const statusBtnText = app.status === 'active' ? 'Deactivate' : 'Activate';

                return `
                    <tr>
                        <td>
                            <div class="app-name">${escapeHtml(app.name)}</div>
                            <div class="app-folder">${escapeHtml(app.folder_path || '')}</div>
                        </td>
                        <td>
                            <span class="status-badge ${app.status}">${app.status}</span>
                        </td>
                        <td>${created}</td>
                        <td>
                            <div class="action-group">
                                <button class="action-btn view" onclick="viewApp(${app.id})">View</button>
                                <button class="action-btn configure" onclick="configureApp(${app.id})">Configure</button>
                                <button class="action-btn toggle" onclick="toggleStatus(${app.id}, '${app.status}')">${statusBtnText}</button>
                                <button class="action-btn launch" onclick="launchApp('${escapeHtml(app.folder_path)}')" ${app.status !== 'active' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''}>Launch</button>
                                <button class="action-btn delete" onclick="openDeleteModal(${app.id}, '${escapeHtml(app.name)}')">Delete</button>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        // Create app modal
        function openCreateModal() {
            document.getElementById('createAppForm').reset();
            openModal('createModal');
        }

        // Handle create app
        async function handleCreateApp(e) {
            e.preventDefault();
            const btn = document.getElementById('createBtn');
            btn.disabled = true;
            btn.textContent = 'Creating...';

            try {
                const formData = new FormData(e.target);
                const name = formData.get('name');
                const description = formData.get('description');
                const payload = { name };
                if (description) payload.config = { description };

                const result = await apiCall('create', null, payload);

                if (result.success) {
                    closeModal('createModal');
                    if (result.meta && result.meta.provision_error) {
                        showToast('App created but provisioning failed: ' + result.meta.provision_error, 'error');
                    } else {
                        showToast('Application created successfully', 'success');
                    }
                    await loadApps();
                } else {
                    showToast(getErrorMessage(result), 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create App';
            }
        }

        // View app details
        function viewApp(id) {
            const app = apps.find(a => a.id === id);
            if (!app) return;

            document.getElementById('viewModalTitle').textContent = app.name;

            const config = app.config && typeof app.config === 'object' ? app.config : {};
            const configText = config.description || 'No description';

            document.getElementById('viewModalBody').innerHTML = `
                <div class="detail-row">
                    <span class="detail-label">Name</span>
                    <span class="detail-value">${escapeHtml(app.name)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value"><span class="status-badge ${app.status}">${app.status}</span></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Folder</span>
                    <span class="detail-value">${escapeHtml(app.folder_path || 'N/A')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span class="detail-value">${escapeHtml(configText)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Database</span>
                    <span class="detail-value">${escapeHtml(app.database_name || 'N/A')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created</span>
                    <span class="detail-value">${new Date(app.created_at).toLocaleString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Last Updated</span>
                    <span class="detail-value">${new Date(app.updated_at).toLocaleString()}</span>
                </div>
            `;

            document.getElementById('viewModalFooter').innerHTML = `
                <button class="modal-btn secondary" onclick="closeModal('viewModal')">Close</button>
                <button class="modal-btn primary" onclick="closeModal('viewModal'); configureApp(${app.id})">Configure</button>
            `;

            openModal('viewModal');
        }

        // Configure app
        function configureApp(id) {
            const app = apps.find(a => a.id === id);
            if (!app) return;

            const config = app.config && typeof app.config === 'object' ? app.config : {};

            document.getElementById('viewModalTitle').textContent = `Configure: ${app.name}`;
            document.getElementById('viewModalBody').innerHTML = `
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="configDescription">${escapeHtml(config.description || '')}</textarea>
                </div>
                <div class="form-group">
                    <label>API Key</label>
                    <input type="text" id="configApiKey" value="${escapeHtml(app.api_key || '')}" placeholder="Leave empty to auto-generate">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="configStatus">
                        <option value="active" ${app.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${app.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                        <option value="error" ${app.status === 'error' ? 'selected' : ''}>Error</option>
                    </select>
                </div>
            `;

            document.getElementById('viewModalFooter').innerHTML = `
                <button class="modal-btn secondary" onclick="closeModal('viewModal')">Cancel</button>
                <button class="modal-btn primary" onclick="saveConfig(${app.id})" id="saveConfigBtn">Save Changes</button>
            `;

            openModal('viewModal');
        }

        // Save config
        async function saveConfig(id) {
            const btn = document.getElementById('saveConfigBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const description = document.getElementById('configDescription').value;
            const apiKey = document.getElementById('configApiKey').value;
            const status = document.getElementById('configStatus').value;

            const payload = { status };
            payload.config = { description };
            if (apiKey) payload.api_key = apiKey;

            try {
                const result = await apiCall('update', id, payload);

                if (result.success) {
                    closeModal('viewModal');
                    showToast('Configuration saved', 'success');
                    await loadApps();
                } else {
                    showToast(getErrorMessage(result), 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
        }

        // Toggle status
        async function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            try {
                const result = await apiCall('update', id, { status: newStatus });
                if (result.success) {
                    showToast(`App ${newStatus === 'active' ? 'activated' : 'deactivated'}`, 'success');
                    await loadApps();
                } else {
                    showToast(getErrorMessage(result), 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }

        // Launch app
        function launchApp(folderPath) {
            if (folderPath) {
                window.open(`../apps/${folderPath}/index.php`, '_blank');
            } else {
                showToast('No folder path configured for this app', 'error');
            }
        }

        // Delete modal
        function openDeleteModal(id, name) {
            deleteTargetId = id;
            document.getElementById('deleteAppName').textContent = name;
            openModal('deleteModal');
        }

        async function confirmDelete() {
            if (!deleteTargetId) return;

            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            try {
                const result = await apiCall('delete', deleteTargetId);
                if (result.success) {
                    closeModal('deleteModal');
                    showToast('Application deleted', 'success');
                    await loadApps();
                } else {
                    showToast(getErrorMessage(result), 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Delete';
                deleteTargetId = null;
            }
        }

        // Logout
        async function handleLogout() {
            try {
                await fetch('api.php?action=logout', { method: 'POST' });
            } catch (e) { /* ignore */ }
            sessionStorage.removeItem('dashboard_auth');
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
