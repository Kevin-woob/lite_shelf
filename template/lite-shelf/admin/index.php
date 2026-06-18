<?php
/**
 * Admin Dashboard - Lite_Shelf
 *
 * Secured with session-based authentication.
 * Admin users must log in with an admin API key to access the dashboard.
 */

session_start();

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lite_Shelf</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --secondary: #64748B;
            --cta: #0F172A;
            --cta-hover: #1E293B;
            --success: #059669;
            --success-hover: #047857;
            --danger: #DC2626;
            --danger-hover: #B91C1C;
            --warning: #D97706;
            --warning-hover: #B45309;
            --background: #F8FAFC;
            --surface: #FFFFFF;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --text-muted: #94A3B8;
            --border: #E2E8F0;
            --border-focus: #2563EB;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 6px;
            --radius: 8px;
            --radius-lg: 12px;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.5;
            font-size: 14px;
        }

        .container { display: flex; min-height: 100vh; }

        /* Login Page */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
            padding: 20px;
        }
        .login-box {
            background: var(--surface);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
        }
        .login-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-subtitle {
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        .login-error {
            background: #FEF2F2;
            color: #991B1B;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            display: none;
            border-left: 3px solid var(--danger);
            font-size: 0.875rem;
        }
        .login-success {
            background: #F0FDF4;
            color: #166534;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            display: none;
            border-left: 3px solid var(--success);
            font-size: 0.875rem;
        }

        /* Sidebar */
        .sidebar { 
            width: 240px; 
            background: #1E293B;
            color: white; 
            padding: 0;
            position: fixed; 
            height: 100vh; 
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            z-index: 100;
        }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
        
        .sidebar-header { 
            padding: 20px; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h1 { 
            font-size: 1.25rem; 
            margin-bottom: 4px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }
        .sidebar-header p { 
            color: rgba(255,255,255,0.6); 
            font-size: 0.8rem;
            font-weight: 400;
        }
        
        .nav-item { 
            padding: 12px 20px; 
            cursor: pointer; 
            transition: var(--transition);
            display: flex; 
            align-items: center; 
            gap: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
            border-left: 3px solid transparent;
        }
        .nav-item:hover { 
            background: rgba(255,255,255,0.05);
            color: white;
        }
        .nav-item.active { 
            background: rgba(37, 99, 235, 0.15);
            border-left-color: var(--primary);
            color: white;
        }
        .nav-item svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        
        .logout-btn { 
            margin: 16px; 
            padding: 10px 14px;
            background: transparent;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: var(--radius);
            cursor: pointer; 
            width: calc(100% - 32px);
            font-weight: 500;
            font-size: 0.875rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .logout-btn:hover { 
            background: rgba(220, 38, 38, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Main Content */
        .main-content { 
            margin-left: 240px; 
            flex: 1; 
            padding: 24px 32px;
            min-height: 100vh;
        }
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 24px;
            gap: 16px;
        }
        .page-title { 
            font-size: 1.5rem; 
            color: var(--text-primary);
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        /* Cards */
        .card { 
            background: var(--surface); 
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 20px; 
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }
        .card-header { 
            font-size: 1rem; 
            font-weight: 600; 
            margin-bottom: 16px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 16px; 
            margin-bottom: 24px;
        }
        .stat-card { 
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        .stat-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }
        .stat-value { 
            font-size: 2rem; 
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
            line-height: 1.2;
            font-family: 'Poppins', sans-serif;
        }
        .stat-label { 
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tables */
        table { 
            width: 100%; 
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        th, td { 
            padding: 12px 14px; 
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th { 
            background: #F8FAFC;
            font-weight: 600; 
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:hover { 
            background: #F8FAFC;
        }
        tr:last-child td {
            border-bottom: none;
        }

        /* Buttons */
        .btn { 
            padding: 9px 18px; 
            border: none; 
            border-radius: var(--radius);
            cursor: pointer; 
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-family: 'Open Sans', sans-serif;
            box-shadow: var(--shadow-sm);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .btn-primary { 
            background: #FFFFFF;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .btn-primary:hover { 
            background: #F8FAFC;
            box-shadow: var(--shadow);
        }
        .btn-danger { 
            background: #FFFFFF;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .btn-danger:hover { 
            background: #F8FAFC;
            box-shadow: var(--shadow);
        }
        .btn-success { 
            background: #FFFFFF;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .btn-success:hover { 
            background: #F8FAFC;
            box-shadow: var(--shadow);
        }
        .btn-warning {
            background: #FFFFFF;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .btn-warning:hover {
            background: #F8FAFC;
            box-shadow: var(--shadow);
        }
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        .btn-secondary:hover {
            background: #475569;
            box-shadow: var(--shadow);
        }

        /* Forms */
        .form-group { 
            margin-bottom: 16px;
        }
        .form-label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 500; 
            color: var(--text-primary);
            font-size: 0.875rem;
        }
        .form-input, .form-select { 
            width: 100%; 
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-family: inherit;
            transition: var(--transition);
            background: var(--surface);
        }
        .form-input:focus, .form-select:focus { 
            outline: none; 
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }

        /* Modal */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal.active { 
            display: flex; 
            justify-content: center; 
            align-items: center;
        }
        .modal-content { 
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            max-width: 520px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        .modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .modal-header h2 {
            font-size: 1.25rem;
            color: var(--text-primary);
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }
        .modal-close { 
            background: none; 
            border: none; 
            font-size: 1.5rem; 
            cursor: pointer; 
            color: var(--text-muted);
            transition: var(--transition);
            line-height: 1;
            padding: 4px;
            border-radius: var(--radius-sm);
        }
        .modal-close:hover {
            color: var(--text-primary);
            background: #F1F5F9;
        }

        /* API Keys Section */
        .api-key-display { 
            background: #F8FAFC;
            padding: 12px;
            border-radius: var(--radius);
            font-family: 'Courier New', monospace;
            word-break: break-all;
            margin: 12px 0;
            border: 1px solid var(--border);
            font-size: 0.8rem;
        }

        /* Loading */
        .loading { 
            opacity: 0.6; 
            pointer-events: none;
        }

        /* Alerts */
        .alert { 
            padding: 12px 14px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 0.875rem;
        }
        .alert-success { 
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #BBF7D0;
        }
        .alert-error { 
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        /* Hide dashboard if not logged in */
        #dashboardContainer { display: none; }
        #loginContainer { display: none; }

        /* File Explorer Styles */
        .file-explorer {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow: visible;
        }
        .file-explorer-toolbar {
            padding: 12px 16px;
            background: #F8FAFC;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .file-explorer-breadcrumb {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        .file-explorer-breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }
        .file-explorer-breadcrumb a:hover {
            background: rgba(8, 145, 178, 0.1);
        }
        .file-explorer-breadcrumb .separator {
            color: var(--text-muted);
            padding: 0 2px;
        }
        .file-explorer-list {
            padding: 16px;
        }
        .file-explorer-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            padding: 0 4px;
        }
        .file-explorer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .file-explorer-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px 12px 24px;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            border: 2px solid transparent;
            position: relative;
        }
        .file-explorer-item:hover {
            background: #F1F5F9;
            border-color: var(--border);
        }
        .file-explorer-item.selected {
            background: rgba(8, 145, 178, 0.1);
            border-color: var(--primary);
        }
        .file-explorer-item-icon {
            width: 56px;
            height: 56px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .file-explorer-item-name {
            font-size: 0.8rem;
            color: var(--text-primary);
            word-break: break-word;
            line-height: 1.3;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .file-explorer-item-actions {
            display: none;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%) translateY(100%);
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 4px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.06);
            flex-direction: column;
            gap: 2px;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        .file-explorer-item:hover .file-explorer-item-actions {
            display: flex;
        }
        .file-explorer-item-actions button {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            color: #0F172A;
            padding: 8px 12px;
            font-size: 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.15s ease;
            white-space: nowrap;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .file-explorer-item-actions button:hover {
            background: #F1F5F9 !important;
        }
        .file-explorer-item-actions button.btn-danger {
            color: #DC2626;
        }
        .file-explorer-item-actions button.btn-danger:hover {
            background: #FEF2F2 !important;
        }
        .file-explorer-item-actions button.btn-warning {
            color: #D97706;
        }
        .file-explorer-item-actions button.btn-warning:hover {
            background: #FFFBEB !important;
        }
        .file-explorer-empty {
            padding: 48px 24px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
        }
        @media (max-width: 768px) {
            .sidebar { 
                width: 100%;
                position: relative;
                height: auto;
            }
            .main-content { 
                margin-left: 0;
                padding: 16px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .file-explorer-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 12px 20px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 250px;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        .toast.success {
            border-left-color: var(--success);
        }
        .toast.error {
            border-left-color: var(--danger);
        }
        .toast.warning {
            border-left-color: var(--warning);
        }
        .toast-icon {
            flex-shrink: 0;
        }
        .toast-message {
            flex: 1;
            font-size: 0.875rem;
            color: var(--text-primary);
        }
        .toast-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toast-close:hover {
            color: var(--text-primary);
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
    <!-- Login Page -->
    <div class="login-container" id="loginContainer">
        <div class="login-box">
            <h1 class="login-title">Lite_Shelf Admin</h1>
            <p class="login-subtitle">Enter your admin API key to continue</p>

            <div class="login-error" id="loginError"></div>
            <div class="login-success" id="loginSuccess"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label class="form-label">Admin API Key</label>
                    <input type="password" class="form-input" id="apiKeyInput" placeholder="sk_admin_..." required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard Container -->
    <div id="dashboardContainer" <?php echo $isLoggedIn ? '' : 'style="display:none"'; ?>>
    <div class="container">
        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>Lite_Shelf</h1>
                <p id="adminNameDisplay"><?php echo htmlspecialchars($adminName); ?></p>
            </div>
            <nav>
                <div class="nav-item active" data-page="dashboard">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </div>
                <div class="nav-item" data-page="users">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Users
                </div>
                <div class="nav-item" data-page="api-keys">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    API Keys
                </div>
                <div class="nav-item" data-page="database">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    Database
                </div>
                <div class="nav-item" data-page="storage">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    Storage
                </div>
            </nav>
            <button class="logout-btn" id="logoutBtn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </button>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal" id="createUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New User</h2>
                <button class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
            </div>
            <form id="createUserForm">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="newEmail" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-input" id="newPassword" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" class="form-input" id="newDisplayName">
                </div>
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>

    <!-- Create API Key Modal -->
    <div class="modal" id="createApiKeyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create API Key</h2>
                <button class="modal-close" onclick="closeModal('createApiKeyModal')">&times;</button>
            </div>
            <form id="createApiKeyForm">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-input" id="apiKeyName" placeholder="My Application Key">
                </div>
                <div class="form-group">
                    <label class="form-label">Rate Limit (requests/hour)</label>
                    <input type="number" class="form-input" id="apiKeyRateLimit" value="1000">
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="apiKeyIsAdmin"> Admin Access
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Generate Key</button>
            </form>
            <div id="apiKeyResult" style="display: none;">
                <p class="alert alert-success"><strong>Save this key!</strong> It won't be shown again.</p>
                <div class="api-key-display" id="apiKeyValue"></div>
            </div>
        </div>
    </div>

    <!-- Create Collection Modal -->
    <div class="modal" id="createCollectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create Collection</h2>
                <button class="modal-close" onclick="closeModal('createCollectionModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Collection Name</label>
                <input type="text" class="form-input" id="createCollectionName" placeholder="e.g. users, products, orders">
                <p style="color: #7f8c8d; font-size: 0.9em; margin-top: 5px;">Lowercase letters, numbers, and underscores only</p>
            </div>
            <button class="btn btn-primary" onclick="createCollection()">Create Collection</button>
        </div>
    </div>
    <!-- Rename Collection Modal -->
    <div class="modal" id="renameCollectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rename Collection</h2>
                <button class="modal-close" onclick="closeModal('renameCollectionModal')">&times;</button>
            </div>
            <input type="hidden" id="renameCollectionOldName">
            <div class="form-group">
                <label class="form-label">Current Name</label>
                <input type="text" class="form-input" id="renameCollectionCurrentName" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">New Name</label>
                <input type="text" class="form-input" id="renameCollectionNewName" placeholder="e.g. users, products, orders">
                <p style="color: #7f8c8d; font-size: 0.9em; margin-top: 5px;">Lowercase letters, numbers, and underscores only</p>
            </div>
            <button class="btn btn-success" onclick="renameCollection()">Rename Collection</button>
        </div>
    </div>
    <!-- Copy Collection Modal -->
    <div class="modal" id="copyCollectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Copy Collection</h2>
                <button class="modal-close" onclick="closeModal('copyCollectionModal')">&times;</button>
            </div>
            <input type="hidden" id="copyCollectionSourceName">
            <div class="form-group">
                <label class="form-label">Source Collection</label>
                <input type="text" class="form-input" id="copyCollectionSourceNameDisplay" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">New Collection Name</label>
                <input type="text" class="form-input" id="copyCollectionNewName" placeholder="e.g. users_backup, products_copy">
                <p style="color: #7f8c8d; font-size: 0.9em; margin-top: 5px;">Lowercase letters, numbers, and underscores only</p>
            </div>
            <button class="btn btn-primary" onclick="copyCollection()">Copy Collection</button>
        </div>
    </div>
    <!-- Manage Permissions Modal -->
    <div class="modal" id="permissionsModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Manage Permissions: <span id="permissionsKeyName"></span></h2>
                <button class="modal-close" onclick="closeModal('permissionsModal')">&times;</button>
            </div>
            <input type="hidden" id="permissionsKeyId">
            
            <!-- Tab Navigation -->
            <div style="display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px;">
                <button class="tab-btn active" id="tabCollections" onclick="switchPermissionTab('collections')" style="padding: 10px 20px; border: none; background: none; cursor: pointer; border-bottom: 2px solid #3498db; font-weight: bold; color: #3498db;">Collections</button>
                <button class="tab-btn" id="tabFolders" onclick="switchPermissionTab('folders')" style="padding: 10px 20px; border: none; background: none; cursor: pointer; color: #7f8c8d;">Folders</button>
            </div>
            
            <!-- Collections Tab -->
            <div id="permissionsCollectionsTab">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Collection Name</th>
                            <th>Access Level</th>
                        </tr>
                    </thead>
                    <tbody id="permissionsCollectionsBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Folders Tab -->
            <div id="permissionsFoldersTab" style="display: none;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Folder Path</th>
                            <th>Access Level</th>
                        </tr>
                    </thead>
                    <tbody id="permissionsFoldersBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Upload File Modal -->
    <div class="modal" id="uploadFileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload File</h2>
                <button class="modal-close" onclick="closeModal('uploadFileModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Select File</label>
                <input type="file" class="form-input" id="uploadFileInput">
            </div>
            <div class="form-group">
                <label class="form-label">Folder Path (optional)</label>
                <input type="text" class="form-input" id="uploadFolderPath" placeholder="e.g. documents/images">
            </div>
            <button class="btn btn-primary" onclick="uploadFile()">Upload</button>
        </div>
    </div>
    <!-- Create Folder Modal -->
    <div class="modal" id="createFolderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Folder</h2>
                <button class="modal-close" onclick="closeModal('createFolderModal')">&times;</button>
            </div>
            <div class="form-group">
                <label class="form-label">Folder Name</label>
                <input type="text" class="form-input" id="createFolderName" placeholder="e.g. documents">
            </div>
            <button class="btn btn-success" onclick="createFolder()">Create Folder</button>
        </div>
    </div>
    <!-- Rename Folder Modal -->
    <div class="modal" id="renameFolderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rename Folder</h2>
                <button class="modal-close" onclick="closeModal('renameFolderModal')">&times;</button>
            </div>
            <input type="hidden" id="renameFolderPath">
            <input type="hidden" id="renameFolderOldName">
            <div class="form-group">
                <label class="form-label">New Name</label>
                <input type="text" class="form-input" id="renameFolderNewName">
            </div>
            <button class="btn btn-success" onclick="renameFolder()">Rename</button>
        </div>
    </div>
    <!-- Rename File Modal -->
    <div class="modal" id="renameFileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rename File</h2>
                <button class="modal-close" onclick="closeModal('renameFileModal')">&times;</button>
            </div>
            <input type="hidden" id="renameFileId">
            <input type="hidden" id="renameFileOldName">
            <div class="form-group">
                <label class="form-label">New Name</label>
                <input type="text" class="form-input" id="renameFileNewName">
            </div>
            <button class="btn btn-success" onclick="renameFile()">Rename</button>
        </div>
    </div>
    <!-- Delete Folder Confirmation Modal -->
    <div class="modal" id="deleteFolderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Folder</h2>
                <button class="modal-close" onclick="closeModal('deleteFolderModal')">&times;</button>
            </div>
            <input type="hidden" id="deleteFolderPath">
            <p class="alert alert-error" style="margin: 15px 0;">
                ⚠️ This will delete this folder and <strong>ALL</strong> its subfolders and files. This cannot be undone.
            </p>
            <p><strong>Folder:</strong> <span id="deleteFolderName"></span></p>
            <div style="margin-top: 15px;">
                <button class="btn btn-danger" onclick="confirmDeleteFolder()">Delete Everything</button>
                <button class="btn btn-primary" onclick="closeModal('deleteFolderModal')" style="margin-left: 10px;">Cancel</button>
            </div>
        </div>
    </div>
    <!-- Move File Modal -->
    <div class="modal" id="moveFileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Move File</h2>
                <button class="modal-close" onclick="closeModal('moveFileModal')">&times;</button>
            </div>
            <input type="hidden" id="moveFileId">
            <div class="form-group">
                <label class="form-label">Select Destination Folder</label>
                <div id="moveFolderPicker" style="border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; max-height: 300px; overflow-y: auto; background: var(--surface);">
                    <div style="color: var(--text-muted); text-align: center; padding: 20px;">Loading folders...</div>
                </div>
                <input type="hidden" id="moveFileDestPath" value="">
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px;">
                Selected: <strong id="moveSelectedPath">(root)</strong>
            </p>
            <button class="btn btn-primary" onclick="moveFile()">Move File</button>
        </div>
    </div>
    <!-- Move Folder Modal -->
    <div class="modal" id="moveFolderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Move Folder</h2>
                <button class="modal-close" onclick="closeModal('moveFolderModal')">&times;</button>
            </div>
            <input type="hidden" id="moveFolderPath">
            <div class="form-group">
                <label class="form-label">Select Destination Parent Folder</label>
                <div id="moveFolderDestPicker" style="border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; max-height: 300px; overflow-y: auto; background: var(--surface);">
                    <div style="color: var(--text-muted); text-align: center; padding: 20px;">Loading folders...</div>
                </div>
                <input type="hidden" id="moveFolderDestPath" value="">
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px;">
                Selected: <strong id="moveFolderSelectedPath">(root)</strong>
            </p>
            <button class="btn btn-primary" onclick="moveFolder()">Move Folder</button>
        </div>
    </div>
    <!-- Copy Folder Modal -->
    <div class="modal" id="copyFolderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Copy Folder</h2>
                <button class="modal-close" onclick="closeModal('copyFolderModal')">&times;</button>
            </div>
            <input type="hidden" id="copyFolderPath">
            <div class="form-group">
                <label class="form-label">Select Destination Parent Folder</label>
                <div id="copyFolderDestPicker" style="border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; max-height: 300px; overflow-y: auto; background: var(--surface);">
                    <div style="color: var(--text-muted); text-align: center; padding: 20px;">Loading folders...</div>
                </div>
                <input type="hidden" id="copyFolderDestPath" value="">
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px;">
                Selected: <strong id="copyFolderSelectedPath">(root)</strong>
            </p>
            <button class="btn btn-success" onclick="copyFolder()">Copy Folder</button>
        </div>
    </div>
    <!-- Copy File Modal -->
    <div class="modal" id="copyFileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Copy File</h2>
                <button class="modal-close" onclick="closeModal('copyFileModal')">&times;</button>
            </div>
            <input type="hidden" id="copyFileId">
            <div class="form-group">
                <label class="form-label">Select Destination Folder</label>
                <div id="copyFolderPicker" style="border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; max-height: 300px; overflow-y: auto; background: var(--surface);">
                    <div style="color: var(--text-muted); text-align: center; padding: 20px;">Loading folders...</div>
                </div>
                <input type="hidden" id="copyFileDestPath" value="">
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px;">
                Selected: <strong id="copySelectedPath">(root)</strong>
            </p>
            <button class="btn btn-success" onclick="copyFile()">Copy File</button>
        </div>
    </div>
    <!-- Create Document Modal -->
    <div class="modal" id="createDocumentModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Create Document</h2>
                <button class="modal-close" onclick="closeModal('createDocumentModal')">&times;</button>
            </div>
            <div class="form-group">
                <div style="display: flex; gap: 5px; margin-bottom: 15px;">
                    <button class="btn btn-primary btn-sm" id="createFormTabBtn" onclick="switchCreateMode('form')">Form Builder</button>
                    <button class="btn btn-success btn-sm" id="createJsonTabBtn" onclick="switchCreateMode('json')">Raw JSON</button>
                </div>
                <!-- Form Builder View -->
                <div id="createFormView" style="display: none;">
                    <div id="createFormFieldList"></div>
                    <button class="btn btn-success btn-sm" onclick="addCreateFormField()" style="margin-top: 10px;">+ Add Field</button>
                </div>
                <!-- Raw JSON View -->
                <div id="createJsonView">
                    <label class="form-label">JSON Data</label>
                    <textarea class="form-input" id="createDocumentJson" rows="10" style="font-family: monospace;" placeholder='{"key": "value"}'></textarea>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="createDocument()">Create</button>
                <button class="btn btn-success" onclick="syncCreateFormToJson()" id="createSyncBtn">Sync Form → JSON</button>
                <button class="btn btn-primary" onclick="syncCreateJsonToForm()" id="createSyncJsonBtn" style="display:none;">Sync JSON → Form</button>
            </div>
        </div>
    </div>
    <!-- Edit Document Modal -->
    <div class="modal" id="editDocumentModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Edit Document</h2>
                <button class="modal-close" onclick="closeModal('editDocumentModal')">&times;</button>
            </div>
            <input type="hidden" id="editCollectionName">
            <input type="hidden" id="editDocumentId">
            <div class="form-group">
                <div style="display: flex; gap: 5px; margin-bottom: 15px;">
                    <button class="btn btn-primary btn-sm" id="editFormTabBtn" onclick="switchEditMode('form')">Form Builder</button>
                    <button class="btn btn-success btn-sm" id="editJsonTabBtn" onclick="switchEditMode('json')">Raw JSON</button>
                </div>
                <!-- Form Builder View -->
                <div id="editFormFieldView" style="display: none;">
                    <div id="editFormFieldList"></div>
                    <button class="btn btn-success btn-sm" onclick="addEditFormField()" style="margin-top: 10px;">+ Add Field</button>
                </div>
                <!-- Raw JSON View -->
                <div id="editJsonView">
                    <label class="form-label">JSON Data</label>
                    <textarea class="form-input" id="editDocumentJson" rows="10" style="font-family: monospace;"></textarea>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="saveDocument()">Save</button>
                <button class="btn btn-success" onclick="syncEditFormToJson()" id="editSyncBtn">Sync Form → JSON</button>
                <button class="btn btn-primary" onclick="syncEditJsonToForm()" id="editSyncJsonBtn" style="display:none;">Sync JSON → Form</button>
            </div>
        </div>
    </div>
    </div> <!-- End dashboardContainer -->

    <script>
        // API Base URL — dynamically compute relative to current script location
        const adminDir = '/admin/';
        const currentPath = window.location.pathname;
        const basePath = currentPath.includes(adminDir)
            ? currentPath.substring(0, currentPath.lastIndexOf(adminDir))
            : '';
        const API_BASE = basePath + adminDir + 'api.php';

        // Show/hide containers
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            if (isLoggedIn) {
                document.getElementById('dashboardContainer').style.display = 'block';
                loadPage('dashboard');
            } else {
                document.getElementById('loginContainer').style.display = 'flex';
            }
        });

        // Helper to make API calls
        // Toast Notification System
        function showToast(message, type = 'success', duration = 3000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: '<svg class="toast-icon" fill="none" stroke="#22C55E" viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                error: '<svg class="toast-icon" fill="none" stroke="#DC2626" viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
                warning: '<svg class="toast-icon" fill="none" stroke="#F59E0B" viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
                info: '<svg class="toast-icon" fill="none" stroke="#0891B2" viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };
            
            toast.innerHTML = `
                ${icons[type] || icons.info}
                <div class="toast-message">${message}</div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            if (duration > 0) {
                setTimeout(() => {
                    toast.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }
        }

        async function apiCall(endpoint, options = {}) {
            const url = API_BASE + '?route=' + encodeURIComponent(endpoint);
            const response = await fetch(url, options);
            if (!response.ok) {
                const text = await response.text();
                let errorMsg = `HTTP ${response.status}: ${response.statusText}`;
                try {
                    const data = JSON.parse(text);
                    if (data.error?.message) errorMsg += ' - ' + data.error.message;
                    else if (data.message) errorMsg += ' - ' + data.message;
                } catch(e) {
                    if (text) errorMsg += ' - ' + text.substring(0, 200);
                }
                throw new Error(errorMsg);
            }
            return response.json();
        }

        // Login Form Handler
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const apiKey = document.getElementById('apiKeyInput').value;
                const errorEl = document.getElementById('loginError');
                const successEl = document.getElementById('loginSuccess');

                errorEl.style.display = 'none';
                successEl.style.display = 'none';

                try {
                    const data = await apiCall('/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ api_key: apiKey })
                    });

                    if (data.success) {
                        successEl.textContent = 'Login successful! Redirecting...';
                        successEl.style.display = 'block';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        errorEl.textContent = data.message || 'Invalid API key';
                        errorEl.style.display = 'block';
                    }
                } catch (error) {
                    errorEl.textContent = 'Login failed. Please check your API key.';
                    errorEl.style.display = 'block';
                }
            });
        }

        // Logout Handler
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to logout?')) return;

                try {
                    await apiCall('/logout', { method: 'POST' });
                    window.location.reload();
                } catch (error) {
                    alert('Logout failed');
                }
            });
        }

        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                loadPage(item.dataset.page);
            });
        });

        // Modal functions
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        // Load page content
        async function loadPage(page) {
            const mainContent = document.getElementById('mainContent');

            switch(page) {
                case 'dashboard':
                    mainContent.innerHTML = await loadDashboard();
                    break;
                case 'users':
                    mainContent.innerHTML = await loadUsers();
                    break;
                case 'api-keys':
                    mainContent.innerHTML = await loadApiKeys();
                    break;
                case 'database':
                    mainContent.innerHTML = await loadDatabase();
                    break;
                case 'storage':
                    mainContent.innerHTML = await loadStorage();
                    break;
            }
        }

        // Dashboard
        async function loadDashboard() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="totalUsers">-</div>
                        <div class="stat-label">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Total Users
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="totalApiKeys">-</div>
                        <div class="stat-label">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px; margin-right: 4px; vertical-align: middle;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Active API Keys
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="totalFiles">-</div>
                        <div class="stat-label">Files Stored</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="totalCollections">-</div>
                        <div class="stat-label">Collections</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="totalDocuments">-</div>
                        <div class="stat-label">Documents in DB</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Recent Activity</div>
                    <table id="recentActivityTable">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Details</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>Loading...</tbody>
                    </table>
                </div>
            `;

            // Fetch dashboard stats after rendering
            setTimeout(async () => {
                try {
                    const data = await apiCall('/stats');

                    if (data.success) {
                        document.getElementById('totalUsers').textContent = data.stats.total_users || 0;
                        document.getElementById('totalApiKeys').textContent = data.stats.active_api_keys || 0;
                        document.getElementById('totalFiles').textContent = data.stats.total_files || 0;
                        document.getElementById('totalCollections').textContent = data.stats.total_collections || 0;
                        document.getElementById('totalDocuments').textContent = data.stats.total_documents || 0;
                    }
                } catch (error) {
                    console.error('Failed to load dashboard:', error);
                }
            }, 0);

            return html;
        }

        // Users page
        async function loadUsers() {
            let users = [];
            try {
                const data = await apiCall('/users');
                if (data.success) users = data.users;
            } catch (error) {
                console.error('Failed to load users:', error);
            }

            const rows = users.map(user => `
                <tr>
                    <td>${user.email}</td>
                    <td>${user.display_name || '-'}</td>
                    <td>${user.email_verified ? '✓' : '✗'}</td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                    </td>
                </tr>
            `).join('');

            return `
                <div class="page-header">
                    <h1 class="page-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; margin-right: 8px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Users
                    </h1>
                    <button class="btn btn-primary" onclick="openModal('createUserModal')">+ Create User</button>
                </div>

                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Verified</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="5">No users found</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        }

        // API Keys page
        async function loadApiKeys() {
            let apiKeys = [];
            try {
                const data = await apiCall('/api-keys');
                if (data.success) apiKeys = data.api_keys;
            } catch (error) {
                console.error('Failed to load API keys:', error);
            }

            const rows = apiKeys.map(key => {
                let actions = '';
                if (key.is_active) {
                    if (!key.is_admin) {
                        actions += `<button class="btn btn-primary" onclick="openPermissionsModal(${key.id}, '${key.name.replace(/'/g, "\\'")}')">Permissions</button> `;
                    }
                    // Prevent revoking initial admin key
                    if (!key.is_initial) {
                        actions += `<button class="btn btn-danger" onclick="revokeApiKey(${key.id})">Revoke</button> `;
                    }
                    if (!key.is_admin) {
                        actions += `<button class="btn btn-success" onclick="promoteApiKey(${key.id})">Promote to Admin</button>`;
                    }
                } else {
                    actions = 'Revoked';
                }

                return `
                <tr>
                    <td>${key.name}</td>
                    <td>${new Date(key.created_at).toLocaleDateString()}</td>
                    <td>${key.is_active ? '✓' : '✗'}</td>
                    <td>${key.is_admin ? (key.is_initial ? '🔑 Admin (Initial)' : '🔑 Admin') : 'User'}</td>
                    <td>${actions}</td>
                </tr>
            `}).join('');

            return `
                <div class="page-header">
                    <h1 class="page-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; margin-right: 8px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        API Keys
                    </h1>
                    <button class="btn btn-primary" onclick="openModal('createApiKeyModal')">+ Generate Key</button>
                </div>

                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="5">No API keys found</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        }

        // Database page
        async function loadDatabase() {
            let collections = [];
            try {
                const data = await apiCall('/collections');
                if (data.success) collections = data.collections;
            } catch (error) {
                console.error('Failed to load collections:', error);
            }

            const collectionItems = collections.map(c => `
                <div class="file-explorer-item" onclick="showCollection('${c.name.replace(/'/g, "\\'")}')">
                    <div class="file-explorer-item-icon">
                        <svg fill="none" stroke="#0891B2" viewBox="0 0 24 24" style="width: 56px; height: 56px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <div class="file-explorer-item-name">${c.name}</div>
                    <div class="file-explorer-item-actions">
                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); openCopyCollectionModal('${c.name.replace(/'/g, "\\'")}')">Copy</button>
                        <button class="btn btn-warning btn-sm" onclick="event.stopPropagation(); openRenameCollectionModal('${c.name.replace(/'/g, "\\'")}')">Rename</button>
                        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); deleteCollection('${c.name.replace(/'/g, "\\'")}')">Delete</button>
                    </div>
                </div>
            `).join('');

            return `
                <div class="page-header">
                    <h1 class="page-title">Database Explorer</h1>
                    <button class="btn btn-primary" onclick="openModal('createCollectionModal')">+ Create Collection</button>
                </div>

                <div class="file-explorer">
                    <div class="file-explorer-toolbar">
                        <div class="file-explorer-breadcrumb">
                            <strong>Collections</strong> (${collections.length})
                        </div>
                    </div>
                    <div class="file-explorer-list">
                        ${collections.length > 0 ? `
                            <div class="file-explorer-section-title">Collections</div>
                            <div class="file-explorer-grid">
                                ${collectionItems}
                            </div>
                        ` : `
                            <div class="file-explorer-empty">
                                No collections found. Click "+ Create Collection" to get started.
                            </div>
                        `}
                    </div>
                </div>
            `;
        }

        // Show collection documents view
        async function showCollection(collectionName) {
            const mainContent = document.getElementById('mainContent');

            let items = [];
            try {
                const data = await apiCall(`/collections/${encodeURIComponent(collectionName)}`);
                if (data.success) items = data.items;
            } catch (error) {
                console.error('Failed to load collection:', error);
            }

            const rows = items.map(item => `
                <tr>
                    <td>${item.id}</td>
                    <td><pre style="max-height: 150px; overflow: auto; margin: 0; font-size: 0.85em;">${JSON.stringify(item.data, null, 2)}</pre></td>
                    <td>${new Date(item.created_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-primary" onclick="editDocument('${collectionName.replace(/'/g, "\\'")}', '${item.id}')">Edit</button>
                        <button class="btn btn-danger" onclick="deleteDocument('${collectionName.replace(/'/g, "\\'")}', '${item.id}')">Delete</button>
                    </td>
                </tr>
            `).join('');

            mainContent.innerHTML = `
                <div class="page-header">
                    <h1 class="page-title">Collection: ${collectionName}</h1>
                    <div>
                        <button class="btn btn-primary" onclick="openCreateDocumentModal()">+ Create Document</button>
                        <button class="btn btn-success" onclick="loadPage('database')">Back to Collections</button>
                    </div>
                </div>

                <!-- Hidden fields for collection context -->
                <input type="hidden" id="currentCollectionName" value="${collectionName}">

                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="4">No documents in this collection</td></tr>'}</tbody>
                    </table>
                </div>
            `;
        }

        // Storage page - folder-aware
        let currentFolderPath = '';

        async function loadStorage() {
            currentFolderPath = '';
            return await renderStorage();
        }

        async function navigateToFolder(path) {
            currentFolderPath = path;
            document.getElementById('mainContent').innerHTML = await renderStorage();
        }

        async function renderStorage() {
            let folders = [];
            let files = [];
            
            try {
                const folderData = await apiCall('/storage/folders?parent_path=' + encodeURIComponent(currentFolderPath));
                if (folderData.success) folders = folderData.folders;
            } catch (error) {
                console.error('Failed to load folders:', error);
            }
            
            try {
                const fileData = await apiCall('/storage/files?folder_path=' + encodeURIComponent(currentFolderPath));
                if (fileData.success) files = fileData.files;
            } catch (error) {
                console.error('Failed to load files:', error);
            }
            
            // Build breadcrumb
            const breadcrumb = buildBreadcrumb(currentFolderPath);
            
            // Build folder items
            const folderItems = folders.map(f => `
                <div class="file-explorer-item" onclick="navigateToFolder('${escAttr(f.path)}')">
                    <div class="file-explorer-item-icon">
                        <svg viewBox="0 0 24 24" style="width: 56px; height: 56px;">
                            <path fill="#F59E0B" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                        </svg>
                    </div>
                    <div class="file-explorer-item-name">${escHtml(f.name)}</div>
                    <div class="file-explorer-item-actions">
                        <button class="btn btn-warning btn-sm" onclick="event.stopPropagation(); renameFolderPrompt('${escAttr(f.path)}', '${escAttr(f.name)}')">Rename</button>
                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); moveFolderPrompt('${escAttr(f.path)}')">Move</button>
                        <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); copyFolderPrompt('${escAttr(f.path)}')">Copy</button>
                        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); deleteFolder('${escAttr(f.path)}')">Delete</button>
                    </div>
                </div>
            `).join('');
            
            // Build file items
            const fileItems = files.map(file => `
                <div class="file-explorer-item" onclick="downloadFile(${file.id})">
                    <div class="file-explorer-item-icon">
                        <svg fill="none" stroke="#64748B" viewBox="0 0 24 24" style="width: 56px; height: 56px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="file-explorer-item-name">${escHtml(file.filename_original)}</div>
                    <div class="file-explorer-item-actions">
                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); downloadFile(${file.id})">Download</button>
                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); renameFilePrompt(${file.id}, '${escAttr(file.filename_original)}')">Rename</button>
                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); moveFilePrompt(${file.id})">Move</button>
                        <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); copyFilePrompt(${file.id})">Copy</button>
                        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); deleteFile(${file.id})">Delete</button>
                    </div>
                </div>
            `).join('');
            
            return `
                <div class="page-header">
                    <h1 class="page-title">Storage</h1>
                    <div>
                        <button class="btn btn-success" onclick="openModal('createFolderModal')">+ New Folder</button>
                        <button class="btn btn-primary" onclick="openModal('uploadFileModal')">+ Upload File</button>
                    </div>
                </div>

                <div class="file-explorer">
                    <div class="file-explorer-toolbar">
                        <div class="file-explorer-breadcrumb">
                            ${breadcrumb}
                        </div>
                    </div>
                    <div class="file-explorer-list">
                        ${folders.length > 0 ? `
                            <div class="file-explorer-section-title">Folders</div>
                            <div class="file-explorer-grid">
                                ${folderItems}
                            </div>
                        ` : ''}
                        
                        ${files.length > 0 ? `
                            <div class="file-explorer-section-title">Files</div>
                            <div class="file-explorer-grid">
                                ${fileItems}
                            </div>
                        ` : ''}
                        
                        ${folders.length === 0 && files.length === 0 ? `
                            <div class="file-explorer-empty">
                                No files or folders here yet. Click "+ New Folder" or "+ Upload File" to get started.
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function buildBreadcrumb(path) {
            if (!path) return '<strong>/</strong> (Root)';
            const parts = path.split('/').filter(p => p);
            let html = '<a href="#" onclick="navigateToFolder(\'\'); return false;" style="color: #3498db;">/</a>';
            let builtPath = '';
            parts.forEach(part => {
                builtPath += part + '/';
                html += ' / <a href="#" onclick="navigateToFolder(\'' + escAttr(builtPath) + '\'); return false;" style="color: #3498db;">' + escHtml(part) + '</a>';
            });
            return html;
        }
        
        function escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        function escAttr(str) {
            if (str === undefined || str === null) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Collection document CRUD functions
        async function createCollection() {
            const name = document.getElementById('createCollectionName').value.trim().toLowerCase();

            if (!name) {
                alert('Please enter a collection name');
                return;
            }

            try {
                const data = await apiCall('/collections', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });

                if (data.success) {
                    alert('Collection created!');
                    closeModal('createCollectionModal');
                    document.getElementById('createCollectionName').value = '';
                    document.getElementById('mainContent').innerHTML = await loadDatabase();
                } else {
                    alert(data.message || 'Failed to create collection');
                }
            } catch (error) {
                alert('Error creating collection: ' + error.message);
            }
        }

        async function deleteCollection(collectionName) {
            if (!confirm(`Are you sure you want to delete the collection "${collectionName}" and ALL its documents? This cannot be undone.`)) return;

            try {
                const data = await apiCall(`/collections/${encodeURIComponent(collectionName)}`, { method: 'DELETE' });

                if (data.success) {
                    alert('Collection deleted');
                    document.getElementById('mainContent').innerHTML = await loadDatabase();
                } else {
                    alert(data.message || 'Failed to delete collection');
                }
            } catch (error) {
                alert('Error deleting collection: ' + error.message);
            }
        }

        function openRenameCollectionModal(collectionName) {
            document.getElementById('renameCollectionOldName').value = collectionName;
            document.getElementById('renameCollectionCurrentName').value = collectionName;
            document.getElementById('renameCollectionNewName').value = collectionName;
            openModal('renameCollectionModal');
        }

        async function renameCollection() {
            const oldName = document.getElementById('renameCollectionOldName').value;
            const newName = document.getElementById('renameCollectionNewName').value.trim();

            if (!newName) {
                alert('Please enter a new collection name');
                return;
            }

            if (!/^[a-z0-9_]+$/.test(newName)) {
                alert('Collection name must contain only lowercase letters, numbers, and underscores');
                return;
            }

            try {
                const data = await apiCall(`/collections/${encodeURIComponent(oldName)}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: newName })
                });

                if (data.success) {
                    alert(`Collection renamed from "${oldName}" to "${newName}"`);
                    closeModal('renameCollectionModal');
                    document.getElementById('mainContent').innerHTML = await loadDatabase();
                } else {
                    alert(data.message || 'Failed to rename collection');
                }
            } catch (error) {
                alert('Error renaming collection: ' + error.message);
            }
        }

        function openCopyCollectionModal(collectionName) {
            document.getElementById('copyCollectionSourceName').value = collectionName;
            document.getElementById('copyCollectionSourceNameDisplay').value = collectionName;
            document.getElementById('copyCollectionNewName').value = collectionName + '_copy';
            openModal('copyCollectionModal');
        }

        async function copyCollection() {
            const sourceName = document.getElementById('copyCollectionSourceName').value;
            const newName = document.getElementById('copyCollectionNewName').value.trim();

            if (!newName) {
                alert('Please enter a new collection name');
                return;
            }

            if (!/^[a-z0-9_]+$/.test(newName)) {
                alert('Collection name must contain only lowercase letters, numbers, and underscores');
                return;
            }

            if (sourceName === newName) {
                alert('New collection name must be different from the source name');
                return;
            }

            try {
                const data = await apiCall(`/collections/${encodeURIComponent(sourceName)}/copy`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ new_name: newName })
                });

                if (data.success) {
                    alert(`Collection copied from "${sourceName}" to "${newName}"`);
                    closeModal('copyCollectionModal');
                    document.getElementById('mainContent').innerHTML = await loadDatabase();
                } else {
                    alert(data.message || 'Failed to copy collection');
                }
            } catch (error) {
                alert('Error copying collection: ' + error.message);
            }
        }

        async function createDocument() {
            const collectionName = document.getElementById('currentCollectionName').value;
            const jsonInput = document.getElementById('createDocumentJson').value;

            if (!jsonInput.trim()) {
                alert('Please enter valid JSON');
                return;
            }

            try {
                const data = JSON.parse(jsonInput);
                const result = await apiCall(`/collections/${encodeURIComponent(collectionName)}/documents`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (result.success) {
                    alert('Document created!');
                    closeModal('createDocumentModal');
                    document.getElementById('createDocumentJson').value = '';
                    await showCollection(collectionName);
                } else {
                    alert(result.message || 'Failed to create document');
                }
            } catch (error) {
                alert('Invalid JSON: ' + error.message);
            }
        }

        async function editDocument(collectionName, documentId) {
            // First get the current document data
            try {
                const data = await apiCall(`/collections/${encodeURIComponent(collectionName)}/documents/${documentId}`);
                if (data.success && data.document) {
                    // Remove the id field from the data for editing (it's read-only)
                    const docData = { ...data.document };
                    delete docData.id;
                    delete docData.created_at;
                    delete docData.updated_at;

                    document.getElementById('editDocumentId').value = documentId;
                    document.getElementById('editCollectionName').value = collectionName;
                    document.getElementById('editDocumentJson').value = JSON.stringify(docData, null, 2);
                    openModal('editDocumentModal');
                } else {
                    alert('Failed to load document');
                }
            } catch (error) {
                alert('Error loading document: ' + error.message);
            }
        }

        async function saveDocument() {
            const collectionName = document.getElementById('editCollectionName').value;
            const documentId = document.getElementById('editDocumentId').value;
            const jsonInput = document.getElementById('editDocumentJson').value;

            if (!jsonInput.trim()) {
                alert('Please enter valid JSON');
                return;
            }

            try {
                const data = JSON.parse(jsonInput);
                const result = await apiCall(`/collections/${encodeURIComponent(collectionName)}/documents/${documentId}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (result.success) {
                    alert('Document updated!');
                    closeModal('editDocumentModal');
                    showCollection(collectionName);
                } else {
                    alert(result.message || 'Failed to update document');
                }
            } catch (error) {
                alert('Invalid JSON: ' + error.message);
            }
        }

        async function deleteDocument(collectionName, documentId) {
            if (!confirm('Are you sure you want to delete this document?')) return;

            try {
                const data = await apiCall(`/collections/${encodeURIComponent(collectionName)}/documents/${documentId}`, { method: 'DELETE' });

                if (data.success) {
                    await showCollection(collectionName);
                } else {
                    alert(data.message || 'Failed to delete document');
                }
            } catch (error) {
                alert('Error deleting document: ' + error.message);
            }
        }

        // Helper functions
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Event listeners for forms
        document.getElementById('createUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('newEmail').value;
            const password = document.getElementById('newPassword').value;
            const displayName = document.getElementById('newDisplayName').value;

            try {
                const data = await apiCall('/users', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, display_name: displayName })
                });

                if (data.success) {
                    alert('User created successfully!');
                    closeModal('createUserModal');
                    document.getElementById('mainContent').innerHTML = await loadUsers();
                } else {
                    alert(data.message || 'Failed to create user');
                }
            } catch (error) {
                alert('Error creating user: ' + error.message);
            }
        });

        document.getElementById('createApiKeyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('apiKeyName').value;
            const rateLimit = parseInt(document.getElementById('apiKeyRateLimit').value);
            const isAdmin = document.getElementById('apiKeyIsAdmin').checked;

            try {
                const data = await apiCall('/api-keys', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, rate_limit: rateLimit, is_admin: isAdmin })
                });

                if (data.success && data.key) {
                    document.getElementById('apiKeyValue').textContent = data.key.key;
                    document.getElementById('apiKeyResult').style.display = 'block';
                    document.querySelector('#createApiKeyForm button[type="submit"]').disabled = true;
                    document.getElementById('apiKeyName').value = '';
                    document.getElementById('apiKeyRateLimit').value = '1000';
                    document.getElementById('apiKeyIsAdmin').checked = false;
                    document.getElementById('mainContent').innerHTML = await loadApiKeys();
                } else {
                    alert(data.message || data.error || 'Failed to create API key');
                }
            } catch (error) {
                alert('Error creating API key: ' + error.message);
            }
        });

        // Delete functions
        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) return;

            try {
                const data = await apiCall(`/users/${userId}`, { method: 'DELETE' });

                if (data.success) {
                    document.getElementById('mainContent').innerHTML = await loadUsers();
                } else {
                    alert(data.message || 'Failed to delete user');
                }
            } catch (error) {
                alert('Error deleting user: ' + error.message);
            }
        }

        async function revokeApiKey(keyId) {
            if (!confirm('Are you sure you want to revoke this API key?')) return;

            try {
                const data = await apiCall(`/api-keys/${keyId}/revoke`, { method: 'POST' });

                if (data.success) {
                    document.getElementById('mainContent').innerHTML = await loadApiKeys();
                } else {
                    alert(data.message || 'Failed to revoke key');
                }
            } catch (error) {
                alert('Error revoking key: ' + error.message);
            }
        }

        async function promoteApiKey(keyId) {
            if (!confirm('Are you sure you want to promote this key to admin access?')) return;

            try {
                const data = await apiCall(`/api-keys/${keyId}/set-admin`, { method: 'POST' });

                if (data.success) {
                    document.getElementById('mainContent').innerHTML = await loadApiKeys();
                } else {
                    alert(data.message || 'Failed to promote key');
                }
            } catch (error) {
                alert('Error promoting key: ' + error.message);
            }
        }

        // ========== PERMISSION MANAGEMENT ==========

        let currentPermissions = { collections: [], folders: [] };

        async function openPermissionsModal(keyId, keyName) {
            document.getElementById('permissionsKeyId').value = keyId;
            document.getElementById('permissionsKeyName').textContent = keyName;
            
            // Reset to collections tab
            switchPermissionTab('collections');
            
            // Load current permissions
            try {
                const data = await apiCall(`/api-keys/${keyId}/permissions`);
                if (data.success) {
                    currentPermissions = data.permissions;
                }
            } catch (error) {
                console.error('Failed to load permissions:', error);
                currentPermissions = { collections: [], folders: [] };
            }
            
            // Load collections and folders
            await Promise.all([loadPermissionsCollections(), loadPermissionsFolders()]);
            
            openModal('permissionsModal');
        }

        function switchPermissionTab(tab) {
            const collectionsTab = document.getElementById('permissionsCollectionsTab');
            const foldersTab = document.getElementById('permissionsFoldersTab');
            const collectionsBtn = document.getElementById('tabCollections');
            const foldersBtn = document.getElementById('tabFolders');
            
            if (tab === 'collections') {
                collectionsTab.style.display = 'block';
                foldersTab.style.display = 'none';
                collectionsBtn.style.color = '#3498db';
                collectionsBtn.style.borderBottom = '2px solid #3498db';
                collectionsBtn.style.fontWeight = 'bold';
                foldersBtn.style.color = '#7f8c8d';
                foldersBtn.style.borderBottom = 'none';
                foldersBtn.style.fontWeight = 'normal';
            } else {
                collectionsTab.style.display = 'none';
                foldersTab.style.display = 'block';
                foldersBtn.style.color = '#3498db';
                foldersBtn.style.borderBottom = '2px solid #3498db';
                foldersBtn.style.fontWeight = 'bold';
                collectionsBtn.style.color = '#7f8c8d';
                collectionsBtn.style.borderBottom = 'none';
                collectionsBtn.style.fontWeight = 'normal';
            }
        }

        async function loadPermissionsCollections() {
            const keyId = document.getElementById('permissionsKeyId').value;
            const tbody = document.getElementById('permissionsCollectionsBody');
            
            // Fetch all collections
            let collections = [];
            try {
                const data = await apiCall('/collections');
                if (data.success) collections = data.collections;
            } catch (error) {
                console.error('Failed to load collections:', error);
            }
            
            // Build permission lookup
            const permissionMap = {};
            currentPermissions.collections.forEach(p => {
                permissionMap[p.collection_id] = p.access_level;
            });
            
            if (collections.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" style="padding: 20px; text-align: center; color: #7f8c8d;">No collections found</td></tr>';
                return;
            }
            
            tbody.innerHTML = collections.map(c => {
                const currentAccess = permissionMap[c.id] || 'none';
                return `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px;">${escHtml(c.name)}</td>
                    <td style="padding: 10px;">
                        <select onchange="updateCollectionPermission(${keyId}, ${c.id}, this.value)" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="none" ${currentAccess === 'none' ? 'selected' : ''}>None</option>
                            <option value="read" ${currentAccess === 'read' ? 'selected' : ''}>Read</option>
                            <option value="write" ${currentAccess === 'write' ? 'selected' : ''}>Write</option>
                            <option value="full" ${currentAccess === 'full' ? 'selected' : ''}>Full</option>
                        </select>
                    </td>
                </tr>
            `}).join('');
        }

        async function updateCollectionPermission(keyId, collectionId, accessLevel) {
            try {
                if (accessLevel === 'none') {
                    await apiCall(`/api-keys/${keyId}/revoke-collection`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ collection_id: collectionId })
                    });
                    showToast('Collection permission revoked', 'success');
                } else {
                    await apiCall(`/api-keys/${keyId}/grant-collection`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ collection_id: collectionId, access_level: accessLevel })
                    });
                    showToast(`Collection permission set to ${accessLevel}`, 'success');
                }
            } catch (error) {
                console.error('Failed to update collection permission:', error);
                showToast('Failed to update permission: ' + error.message, 'error');
                // Reload to refresh state
                await loadPermissionsCollections();
            }
        }

        async function loadPermissionsFolders() {
            const keyId = document.getElementById('permissionsKeyId').value;
            const tbody = document.getElementById('permissionsFoldersBody');
            
            // Fetch all folder paths
            let folders = [];
            try {
                const data = await apiCall('/storage/folders/all-paths');
                if (data.success) folders = data.folders;
            } catch (error) {
                console.error('Failed to load folders:', error);
            }
            
            // Build permission lookup
            const permissionMap = {};
            currentPermissions.folders.forEach(p => {
                permissionMap[p.folder_path] = p.access_level;
            });
            
            if (folders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" style="padding: 20px; text-align: center; color: #7f8c8d;">No folders found</td></tr>';
                return;
            }
            
            tbody.innerHTML = folders.map(f => {
                const currentAccess = permissionMap[f.path] || 'none';
                const displayPath = f.path || '(root)';
                return `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; font-family: monospace; font-size: 0.9em;">${escHtml(displayPath)}</td>
                    <td style="padding: 10px;">
                        <select onchange="updateFolderPermission(${keyId}, '${escAttr(f.path)}', this.value)" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="none" ${currentAccess === 'none' ? 'selected' : ''}>None</option>
                            <option value="read" ${currentAccess === 'read' ? 'selected' : ''}>Read</option>
                            <option value="write" ${currentAccess === 'write' ? 'selected' : ''}>Write</option>
                            <option value="full" ${currentAccess === 'full' ? 'selected' : ''}>Full</option>
                        </select>
                    </td>
                </tr>
            `}).join('');
        }

        async function updateFolderPermission(keyId, folderPath, accessLevel) {
            try {
                if (accessLevel === 'none') {
                    await apiCall(`/api-keys/${keyId}/revoke-folder`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ folder_path: folderPath })
                    });
                    showToast('Folder permission revoked', 'success');
                } else {
                    await apiCall(`/api-keys/${keyId}/grant-folder`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ folder_path: folderPath, access_level: accessLevel })
                    });
                    showToast(`Folder permission set to ${accessLevel}`, 'success');
                }
            } catch (error) {
                console.error('Failed to update folder permission:', error);
                showToast('Failed to update permission: ' + error.message, 'error');
                // Reload to refresh state
                await loadPermissionsFolders();
            }
        }

        async function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file?')) return;

            try {
                const data = await apiCall(`/storage/files/${fileId}`, { method: 'DELETE' });

                if (data.success) {
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.message || 'Failed to delete file');
                }
            } catch (error) {
                alert('Error deleting file: ' + error.message);
            }
        }

        async function downloadFile(fileId) {
            // Use a direct link to trigger download
            window.open(API_BASE + '?route=/storage/files/' + fileId + '/download', '_blank');
        }

        async function uploadFile() {
            const fileInput = document.getElementById('uploadFileInput');
            const file = fileInput.files[0];
            if (!file) {
                alert('Please select a file');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            const folderPath = document.getElementById('uploadFolderPath').value || currentFolderPath;
            formData.append('folder_path', folderPath);

            const url = API_BASE + '?route=/storage/upload';
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const text = await response.text();
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('File uploaded successfully!');
                        closeModal('uploadFileModal');
                        fileInput.value = '';
                        document.getElementById('uploadFolderPath').value = '';
                        document.getElementById('mainContent').innerHTML = await renderStorage();
                    } else {
                        const errorMsg = data.error?.message || data.message || 'Upload failed';
                        alert('Upload failed: ' + errorMsg);
                    }
                } catch (jsonError) {
                    alert('Server error: ' + text.substring(0, 200));
                }
            } catch (error) {
                alert('Error uploading file: ' + error.message);
            }
        }

        // ========== FOLDER CRUD ==========

        async function createFolder() {
            const name = document.getElementById('createFolderName').value.trim();
            if (!name) {
                alert('Please enter a folder name');
                return;
            }

            try {
                const data = await apiCall('/storage/folders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, parent_path: currentFolderPath })
                });

                if (data.success) {
                    alert('Folder created!');
                    closeModal('createFolderModal');
                    document.getElementById('createFolderName').value = '';
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to create folder');
                }
            } catch (error) {
                alert('Error creating folder: ' + error.message);
            }
        }

        function renameFolderPrompt(path, name) {
            document.getElementById('renameFolderPath').value = path;
            document.getElementById('renameFolderOldName').value = name;
            document.getElementById('renameFolderNewName').value = name;
            openModal('renameFolderModal');
        }

        async function renameFolder() {
            const path = document.getElementById('renameFolderPath').value;
            const newName = document.getElementById('renameFolderNewName').value.trim();
            if (!newName) {
                alert('Please enter a new folder name');
                return;
            }

            try {
                const data = await apiCall('/storage/folders/' + encodeURIComponent(path), {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: newName })
                });

                if (data.success) {
                    alert('Folder renamed!');
                    closeModal('renameFolderModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to rename folder');
                }
            } catch (error) {
                alert('Error renaming folder: ' + error.message);
            }
        }

        function deleteFolder(path) {
            document.getElementById('deleteFolderPath').value = path;
            document.getElementById('deleteFolderName').textContent = path;
            openModal('deleteFolderModal');
        }

        async function confirmDeleteFolder() {
            const path = document.getElementById('deleteFolderPath').value;

            try {
                const data = await apiCall('/storage/folders/' + encodeURIComponent(path), { method: 'DELETE' });

                if (data.success) {
                    alert('Folder and all contents deleted!');
                    closeModal('deleteFolderModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to delete folder');
                }
            } catch (error) {
                alert('Error deleting folder: ' + error.message);
            }
        }

        function moveFolderPrompt(path) {
            document.getElementById('moveFolderPath').value = path;
            document.getElementById('moveFolderDestPath').value = '';
            document.getElementById('moveFolderSelectedPath').textContent = '(root)';
            openModal('moveFolderModal');
            loadFolderPicker('moveFolderDestPicker', 'moveFolderDestPath', 'moveFolderSelectedPath', '');
        }

        async function moveFolder() {
            const folderPath = document.getElementById('moveFolderPath').value;
            const destPath = document.getElementById('moveFolderDestPath').value.trim();

            // Validate: cannot move a folder into itself or its subfolders
            if (destPath.startsWith(folderPath + '/') || destPath === folderPath) {
                alert('Cannot move a folder into itself or its subfolders.');
                return;
            }

            try {
                const data = await apiCall('/storage/folders/' + encodeURIComponent(folderPath) + '/move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ parent_path: destPath })
                });

                if (data.success) {
                    alert('Folder moved!');
                    closeModal('moveFolderModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to move folder');
                }
            } catch (error) {
                alert('Error moving folder: ' + error.message);
            }
        }

        function copyFolderPrompt(path) {
            document.getElementById('copyFolderPath').value = path;
            document.getElementById('copyFolderDestPath').value = '';
            document.getElementById('copyFolderSelectedPath').textContent = '(root)';
            openModal('copyFolderModal');
            loadFolderPicker('copyFolderDestPicker', 'copyFolderDestPath', 'copyFolderSelectedPath', '');
        }

        async function copyFolder() {
            const folderPath = document.getElementById('copyFolderPath').value;
            const destPath = document.getElementById('copyFolderDestPath').value.trim();

            try {
                const data = await apiCall('/storage/folders/' + encodeURIComponent(folderPath) + '/copy', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ parent_path: destPath })
                });

                if (data.success) {
                    alert('Folder copied!');
                    closeModal('copyFolderModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to copy folder');
                }
            } catch (error) {
                alert('Error copying folder: ' + error.message);
            }
        }

        // ========== FILE MOVE/COPY ==========

        async function loadFolderPicker(pickerId, hiddenInputId, displayId, currentPath) {
            const picker = document.getElementById(pickerId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const display = document.getElementById(displayId);
            
            try {
                const data = await apiCall('/storage/folders/all-paths');
                if (!data.success) {
                    picker.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 20px;">Failed to load folders</div>';
                    return;
                }
                
                const folders = data.folders || [];
                
                // Build folder tree HTML
                let html = '<div style="display: flex; flex-direction: column; gap: 4px;">';
                
                // Root option
                html += `
                    <div onclick="selectFolder('${hiddenInputId}', '${displayId}', '', this)" 
                         style="padding: 8px 12px; cursor: pointer; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 8px; ${currentPath === '' ? 'background: rgba(8, 145, 178, 0.1); border: 1px solid var(--primary);' : 'border: 1px solid transparent;'}"
                         onmouseover="this.style.background='rgba(8, 145, 178, 0.05)'"
                         onmouseout="this.style.background='${currentPath === '' ? 'rgba(8, 145, 178, 0.1)' : 'transparent'}'">
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; flex-shrink: 0;">
                            <path fill="#F59E0B" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                        </svg>
                        <span style="font-size: 0.875rem;">(root)</span>
                    </div>
                `;
                
                // Folder options
                folders.forEach(folder => {
                    const isSelected = currentPath === folder.path;
                    const indent = (folder.path.split('/').length - 1) * 20;
                    
                    html += `
                        <div onclick="selectFolder('${hiddenInputId}', '${displayId}', '${escAttr(folder.path)}', this)" 
                             style="padding: 8px 12px; cursor: pointer; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 8px; margin-left: ${indent}px; ${isSelected ? 'background: rgba(8, 145, 178, 0.1); border: 1px solid var(--primary);' : 'border: 1px solid transparent;'}"
                             onmouseover="this.style.background='rgba(8, 145, 178, 0.05)'"
                             onmouseout="this.style.background='${isSelected ? 'rgba(8, 145, 178, 0.1)' : 'transparent'}'">
                            <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; flex-shrink: 0;">
                                <path fill="#F59E0B" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                            </svg>
                            <span style="font-size: 0.875rem;">${escHtml(folder.name)}</span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: auto;">${escHtml(folder.path)}</span>
                        </div>
                    `;
                });
                
                html += '</div>';
                picker.innerHTML = html;
                
            } catch (error) {
                console.error('Failed to load folders:', error);
                picker.innerHTML = '<div style="color: var(--text-muted); text-align: center; padding: 20px;">Error loading folders</div>';
            }
        }
        
        function selectFolder(hiddenInputId, displayId, path, element) {
            const hiddenInput = document.getElementById(hiddenInputId);
            const display = document.getElementById(displayId);
            
            hiddenInput.value = path;
            display.textContent = path || '(root)';
            
            // Update visual selection
            const picker = element.parentElement;
            const items = picker.querySelectorAll('div[onclick]');
            items.forEach(item => {
                item.style.background = 'transparent';
                item.style.border = '1px solid transparent';
            });
            
            element.style.background = 'rgba(8, 145, 178, 0.1)';
            element.style.border = '1px solid var(--primary)';
        }

        function moveFilePrompt(fileId) {
            document.getElementById('moveFileId').value = fileId;
            document.getElementById('moveFileDestPath').value = currentFolderPath;
            document.getElementById('moveSelectedPath').textContent = currentFolderPath || '(root)';
            openModal('moveFileModal');
            loadFolderPicker('moveFolderPicker', 'moveFileDestPath', 'moveSelectedPath', currentFolderPath);
        }

        function renameFilePrompt(fileId, name) {
            document.getElementById('renameFileId').value = fileId;
            document.getElementById('renameFileOldName').value = name;
            document.getElementById('renameFileNewName').value = name;
            openModal('renameFileModal');
        }

        async function renameFile() {
            const fileId = document.getElementById('renameFileId').value;
            const newName = document.getElementById('renameFileNewName').value.trim();
            if (!newName) {
                alert('Please enter a new file name');
                return;
            }

            try {
                const data = await apiCall('/storage/files/' + fileId + '/rename', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: newName })
                });

                if (data.success) {
                    alert('File renamed!');
                    closeModal('renameFileModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || data.message || 'Failed to rename file');
                }
            } catch (error) {
                alert('Error renaming file: ' + error.message);
            }
        }

        async function moveFile() {
            const fileId = document.getElementById('moveFileId').value;
            const destPath = document.getElementById('moveFileDestPath').value.trim();

            try {
                const data = await apiCall('/storage/files/' + fileId + '/move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ folder_path: destPath })
                });

                if (data.success) {
                    alert('File moved!');
                    closeModal('moveFileModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to move file');
                }
            } catch (error) {
                alert('Error moving file: ' + error.message);
            }
        }

        function copyFilePrompt(fileId) {
            document.getElementById('copyFileId').value = fileId;
            document.getElementById('copyFileDestPath').value = currentFolderPath;
            document.getElementById('copySelectedPath').textContent = currentFolderPath || '(root)';
            openModal('copyFileModal');
            loadFolderPicker('copyFolderPicker', 'copyFileDestPath', 'copySelectedPath', currentFolderPath);
        }

        async function copyFile() {
            const fileId = document.getElementById('copyFileId').value;
            const destPath = document.getElementById('copyFileDestPath').value.trim();

            try {
                const data = await apiCall('/storage/files/' + fileId + '/copy', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ folder_path: destPath })
                });

                if (data.success) {
                    alert('File copied!');
                    closeModal('copyFileModal');
                    document.getElementById('mainContent').innerHTML = await renderStorage();
                } else {
                    alert(data.error || 'Failed to copy file');
                }
            } catch (error) {
                alert('Error copying file: ' + error.message);
            }
        }

        // ========== DOCUMENT FORM BUILDER ==========

        let createFormMode = 'json'; // 'form' or 'json'
        let editFormMode = 'json';
        let createFieldCounter = 0;
        let editFieldCounter = 0;

        function switchCreateMode(mode) {
            createFormMode = mode;
            document.getElementById('createFormView').style.display = mode === 'form' ? 'block' : 'none';
            document.getElementById('createJsonView').style.display = mode === 'json' ? 'block' : 'none';
            document.getElementById('createFormTabBtn').className = mode === 'form' ? 'btn btn-primary btn-sm' : 'btn btn-success btn-sm';
            document.getElementById('createJsonTabBtn').className = mode === 'json' ? 'btn btn-primary btn-sm' : 'btn btn-success btn-sm';
            document.getElementById('createSyncBtn').style.display = mode === 'form' ? 'inline-block' : 'none';
            document.getElementById('createSyncJsonBtn').style.display = mode === 'json' ? 'inline-block' : 'none';

            // Auto-init: if switching to form and it's empty, try to load from JSON
            if (mode === 'form') {
                const list = document.getElementById('createFormFieldList');
                if (list && list.children.length === 0) {
                    const jsonText = document.getElementById('createDocumentJson').value.trim();
                    if (jsonText) {
                        try {
                            const data = JSON.parse(jsonText);
                            createFieldCounter = 0;
                            loadJsonToForm('createFormFieldList', data, 0, 'create', 'removeCreateFormField');
                            return;
                        } catch(e) { /* ignore, will just add a blank row */ }
                    }
                }
                // If still empty, add a default row
                const list2 = document.getElementById('createFormFieldList');
                if (list2 && list2.children.length === 0) {
                    addCreateFormField();
                }
            }
        }

        function switchEditMode(mode) {
            editFormMode = mode;
            document.getElementById('editFormFieldView').style.display = mode === 'form' ? 'block' : 'none';
            document.getElementById('editJsonView').style.display = mode === 'json' ? 'block' : 'none';
            document.getElementById('editFormTabBtn').className = mode === 'form' ? 'btn btn-primary btn-sm' : 'btn btn-success btn-sm';
            document.getElementById('editJsonTabBtn').className = mode === 'json' ? 'btn btn-primary btn-sm' : 'btn btn-success btn-sm';
            document.getElementById('editSyncBtn').style.display = mode === 'form' ? 'inline-block' : 'none';
            document.getElementById('editSyncJsonBtn').style.display = mode === 'json' ? 'inline-block' : 'none';

            // Auto-init: if switching to form and it's empty, try to load from JSON
            if (mode === 'form') {
                const list = document.getElementById('editFormFieldList');
                if (list && list.children.length === 0) {
                    const jsonText = document.getElementById('editDocumentJson').value.trim();
                    if (jsonText) {
                        try {
                            const data = JSON.parse(jsonText);
                            editFieldCounter = 0;
                            loadJsonToForm('editFormFieldList', data, 0, 'edit', 'removeEditFormField');
                            return;
                        } catch(e) { /* ignore */ }
                    }
                }
                const list2 = document.getElementById('editFormFieldList');
                if (list2 && list2.children.length === 0) {
                    addEditFormField();
                }
            }
        }

        function renderFormField(containerId, path, label, value, type, isNested, depth, prefix, removeCallback, isArrayChild) {
            const container = document.getElementById(containerId);
            const counter = (prefix === 'create') ? ++createFieldCounter : ++editFieldCounter;
            const id = prefix + '_' + counter;
            const indent = depth * 25;

            const row = document.createElement('div');
            row.style.cssText = `display:flex; gap:8px; align-items:center; margin-bottom:8px; padding-left:${indent}px;`;
            row.id = id;
            row.dataset.depth = String(depth);

            const isPrimitive = ['string','number','boolean','null'].includes(type);
            const safeValue = (value === undefined || value === null || typeof value === 'object') ? '' : String(value);
            const safeLabel = (label === undefined || label === null) ? '' : String(label);

            // Build HTML — skip key input for array children
            let html = '';
            if (!isArrayChild) {
                html += `<input type="text" class="form-input" style="flex:1; min-width:80px;" placeholder="Key" value="${escAttr(safeLabel)}" onchange="updateFormFieldKey('${id}', this.value)">`;
            } else {
                html += `<span style="color:#3498db; font-weight:bold; min-width:30px;">[${label}]</span>`;
            }
            html += `<select class="form-select" style="width:110px;" onchange="toggleNestedValue('${id}', this.value)">
                <option value="string" ${type==='string'?'selected':''}>String</option>
                <option value="number" ${type==='number'?'selected':''}>Number</option>
                <option value="boolean" ${type==='boolean'?'selected':''}>Boolean</option>
                <option value="null" ${type==='null'?'selected':''}>Null</option>
                <option value="object" ${type==='object'?'selected':''}>Object {}</option>
                <option value="array" ${type==='array'?'selected':''}>Array []</option>
            </select>`;
            html += `${type === 'boolean' ? `<input type="checkbox" ${value===true?'checked':''} onchange="updateFormFieldBool('${id}', this.checked)">` : ''}`;
            html += `${type === 'null' ? `<span style="color:#7f8c8d;">null</span>` : ''}`;
            // Show value input only for primitive types (not for object/array)
            if (isPrimitive) {
                html += `<input type="${type==='number'?'number':'text'}" class="form-input" style="flex:2; min-width:100px;" placeholder="Value" value="${escAttr(safeValue)}" onchange="updateFormFieldValue('${id}', this.value)">`;
            }
            // Show + button only for object/array types
            html += `<button class="btn btn-success btn-sm add-nested-btn" ${isPrimitive ? 'style="display:none;"' : ''} onclick="addNestedField('${id}', '${prefix}')" title="Add nested">+</button>`;
            html += `<button class="btn btn-danger btn-sm" onclick="${removeCallback}('${id}')" title="Remove">✕</button>`;

            row.innerHTML = html;
            container.appendChild(row);

            // Create children container for object/array
            if (type === 'object' || type === 'array') {
                const childContainer = document.createElement('div');
                childContainer.id = id + '_children';
                childContainer.style.cssText = `border-left: 2px solid #3498db; padding-left: 10px; margin-left: ${indent + 20}px;`;
                container.appendChild(childContainer);

                // Render existing children for object
                if (type === 'object' && typeof value === 'object' && value !== null && !Array.isArray(value)) {
                    Object.keys(value).forEach(key => {
                        renderFormField(containerId, path + '.' + key, key, value[key], typeof value[key] === 'object' && value[key] !== null ? (Array.isArray(value[key]) ? 'array' : 'object') : typeof value[key], true, depth + 1, prefix, removeCallback, false);
                    });
                }
                // Render existing children for array
                else if (type === 'array' && Array.isArray(value)) {
                    value.forEach((item, idx) => {
                        renderFormField(containerId, path + '[' + idx + ']', String(idx), item, typeof item === 'object' && item !== null ? (Array.isArray(item) ? 'array' : 'object') : typeof item, true, depth + 1, prefix, removeCallback, true);
                    });
                }
            }
        }

        function toggleNestedValue(rowId, newType) {
            const row = document.getElementById(rowId);
            if (!row) return;
            const isPrimitive = ['string','number','boolean','null'].includes(newType);

            // Clean up all extra elements first
            row.querySelectorAll('input[type="checkbox"][onchange]').forEach(el => el.remove());
            row.querySelectorAll('span[style*="7f8c8d"]').forEach(el => el.remove());

            // Handle value input
            const valueInput = row.querySelector('input.form-input:not([placeholder="Key"])');
            if (!isPrimitive) {
                // Object/Array: hide value input
                if (valueInput) valueInput.style.display = 'none';
            } else if (newType === 'boolean') {
                if (valueInput) valueInput.style.display = 'none';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.setAttribute('onchange', `updateFormFieldBool('${rowId}', this.checked)`);
                row.insertBefore(cb, row.querySelector('.btn-danger'));
            } else if (newType === 'null') {
                if (valueInput) valueInput.style.display = 'none';
                const nullSpan = document.createElement('span');
                nullSpan.style.cssText = 'color:#7f8c8d;';
                nullSpan.textContent = 'null';
                row.insertBefore(nullSpan, row.querySelector('.btn-danger'));
            } else if (newType === 'string') {
                if (valueInput) {
                    valueInput.style.display = '';
                    valueInput.type = 'text';
                    valueInput.value = '';
                }
            } else if (newType === 'number') {
                if (valueInput) {
                    valueInput.style.display = '';
                    valueInput.type = 'number';
                    valueInput.value = '';
                }
            }

            // Handle + button — show only for object/array
            const addBtn = row.querySelector('.add-nested-btn');
            if (addBtn) {
                addBtn.style.display = (newType === 'object' || newType === 'array') ? '' : 'none';
            }

            // Handle object/array — show/create children container
            if (newType === 'object' || newType === 'array') {
                let childContainer = document.getElementById(rowId + '_children');
                if (!childContainer) {
                    childContainer = document.createElement('div');
                    childContainer.id = rowId + '_children';
                    childContainer.style.cssText = 'border-left: 2px solid #3498db; padding-left: 10px; margin-left: 20px;';
                    row.parentNode.insertBefore(childContainer, row.nextSibling);
                }
                childContainer.style.display = 'block';
            } else {
                const childContainer = document.getElementById(rowId + '_children');
                if (childContainer) childContainer.style.display = 'none';
            }
        }

        function updateFormFieldKey(rowId, key) {
            // Key is just for display; the real key is captured when building JSON
            const row = document.getElementById(rowId);
            if (row) row.dataset.key = key;
        }

        function updateFormFieldValue(rowId, value) {
            const row = document.getElementById(rowId);
            if (row) row.dataset.value = value;
        }

        function updateFormFieldBool(rowId, checked) {
            const row = document.getElementById(rowId);
            if (row) row.dataset.value = checked ? 'true' : 'false';
        }

        function addCreateFormField() {
            renderFormField('createFormFieldList', '', '', '', 'string', false, 0, 'create', 'removeCreateFormField', false);
        }

        function addEditFormField() {
            renderFormField('editFormFieldList', '', '', '', 'string', false, 0, 'edit', 'removeEditFormField', false);
        }

        function addNestedField(parentRowId, prefix) {
            const childContainer = document.getElementById(parentRowId + '_children');
            if (!childContainer) return;
            const parentRow = document.getElementById(parentRowId);
            const parentSelect = parentRow ? parentRow.querySelector('select') : null;
            const parentType = parentSelect ? parentSelect.value : 'object';
            const isArrayChild = (parentType === 'array');
            const depth = parentRow ? parseInt(parentRow.dataset.depth || '0') + 1 : 1;
            // For array children, use index as label
            const label = isArrayChild ? String(childContainer.querySelectorAll(':scope > div[id]:not([id$="_children"])').length) : '';
            renderFormField(parentRowId + '_children', '', label, '', 'string', false, depth, prefix, prefix === 'create' ? 'removeCreateFormField' : 'removeEditFormField', isArrayChild);
        }

        function removeCreateFormField(rowId) {
            const row = document.getElementById(rowId);
            const childContainer = document.getElementById(rowId + '_children');
            if (childContainer) childContainer.remove();
            if (row) row.remove();
        }

        function removeEditFormField(rowId) {
            const row = document.getElementById(rowId);
            const childContainer = document.getElementById(rowId + '_children');
            if (childContainer) childContainer.remove();
            if (row) row.remove();
        }

        // Build JSON from form fields recursively
        function buildFormJson(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return {};

            const result = {};
            const rows = container.querySelectorAll(':scope > div[id]');

            rows.forEach(row => {
                if (!row.id || row.id.endsWith('_children')) return;

                const keyInputs = row.querySelectorAll('.form-input');
                const key = keyInputs[0].value.trim();
                if (!key) return;

                const select = row.querySelector('select');
                const type = select ? select.value : 'string';

                if (type === 'object' || type === 'array') {
                    const childContainer = document.getElementById(row.id + '_children');
                    if (childContainer) {
                        if (type === 'object') {
                            result[key] = buildFormJson(row.id + '_children');
                        } else {
                            result[key] = buildFormJsonArray(row.id + '_children');
                        }
                    } else {
                        result[key] = type === 'object' ? {} : [];
                    }
                } else if (type === 'boolean') {
                    const cb = row.querySelector('input[type="checkbox"]');
                    result[key] = cb ? cb.checked : false;
                } else if (type === 'null') {
                    result[key] = null;
                } else if (type === 'number') {
                    result[key] = parseFloat(keyInputs[keyInputs.length - 1].value) || 0;
                } else {
                    result[key] = keyInputs[keyInputs.length - 1].value;
                }
            });

            return result;
        }

        function buildFormJsonArray(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return [];

            const result = [];
            const rows = container.querySelectorAll(':scope > div[id]');

            rows.forEach(row => {
                if (!row.id || row.id.endsWith('_children')) return;

                const select = row.querySelector('select');
                const type = select ? select.value : 'string';

                if (type === 'object' || type === 'array') {
                    const childContainer = document.getElementById(row.id + '_children');
                    if (childContainer) {
                        if (type === 'object') {
                            result.push(buildFormJson(row.id + '_children'));
                        } else {
                            result.push(buildFormJsonArray(row.id + '_children'));
                        }
                    } else {
                        result.push(type === 'object' ? {} : []);
                    }
                } else if (type === 'boolean') {
                    const cb = row.querySelector('input[type="checkbox"]');
                    result.push(cb ? cb.checked : false);
                } else if (type === 'null') {
                    result.push(null);
                } else if (type === 'number') {
                    const keyInputs = row.querySelectorAll('.form-input');
                    result.push(parseFloat(keyInputs[keyInputs.length - 1].value) || 0);
                } else {
                    const keyInputs = row.querySelectorAll('.form-input');
                    result.push(keyInputs[keyInputs.length - 1].value);
                }
            });

            return result;
        }

        // Sync functions
        function syncCreateFormToJson() {
            const data = buildFormJson('createFormFieldList');
            document.getElementById('createDocumentJson').value = JSON.stringify(data, null, 2);
        }

        function syncCreateJsonToForm() {
            const jsonText = document.getElementById('createDocumentJson').value.trim();
            if (!jsonText) return;
            try {
                const data = JSON.parse(jsonText);
                document.getElementById('createFormFieldList').innerHTML = '';
                createFieldCounter = 0;
                loadJsonToForm('createFormFieldList', data, 0, 'create', 'removeCreateFormField');
            } catch (e) {
                alert('Invalid JSON: ' + e.message);
            }
        }

        function syncEditFormToJson() {
            const data = buildFormJson('editFormFieldList');
            document.getElementById('editDocumentJson').value = JSON.stringify(data, null, 2);
        }

        function syncEditJsonToForm() {
            const jsonText = document.getElementById('editDocumentJson').value.trim();
            if (!jsonText) return;
            try {
                const data = JSON.parse(jsonText);
                document.getElementById('editFormFieldList').innerHTML = '';
                editFieldCounter = 0;
                loadJsonToForm('editFormFieldList', data, 0, 'edit', 'removeEditFormField');
            } catch (e) {
                alert('Invalid JSON: ' + e.message);
            }
        }

        function loadJsonToForm(containerId, data, depth, prefix, removeCallback) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (Array.isArray(data)) {
                data.forEach((item, idx) => {
                    const type = typeof item === 'object' && item !== null ? (Array.isArray(item) ? 'array' : 'object') : typeof item;
                    renderFormField(containerId, '', String(idx), item, type, true, depth, prefix, removeCallback, true);
                });
            } else if (typeof data === 'object' && data !== null) {
                Object.keys(data).forEach(key => {
                    const value = data[key];
                    const type = typeof value === 'object' && value !== null ? (Array.isArray(value) ? 'array' : 'object') : typeof value;
                    renderFormField(containerId, '', key, value, type, true, depth, prefix, removeCallback, false);
                });
            }
        }

        // Override createDocument to handle form mode
        const origCreateDocument = createDocument;
        createDocument = async function() {
            if (createFormMode === 'form') {
                const formList = document.getElementById('createFormFieldList');
                if (formList && formList.children.length > 0) {
                    syncCreateFormToJson();
                }
            }
            const collectionName = document.getElementById('currentCollectionName').value;
            const jsonInput = document.getElementById('createDocumentJson').value;

            if (!jsonInput.trim()) {
                alert('Please enter valid JSON');
                return;
            }

            try {
                const data = JSON.parse(jsonInput);
                const result = await apiCall(`/collections/${encodeURIComponent(collectionName)}/documents`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (result.success) {
                    alert('Document created!');
                    closeModal('createDocumentModal');
                    document.getElementById('createDocumentJson').value = '';
                    document.getElementById('createFormFieldList').innerHTML = '';
                    createFieldCounter = 0;
                    await showCollection(collectionName);
                } else {
                    alert(result.message || 'Failed to create document');
                }
            } catch (error) {
                alert('Invalid JSON: ' + error.message);
            }
        };

        function openCreateDocumentModal() {
            // Reset to JSON mode and clear state
            createFormMode = 'json';
            document.getElementById('createFormView').style.display = 'none';
            document.getElementById('createJsonView').style.display = 'block';
            document.getElementById('createFormTabBtn').className = 'btn btn-success btn-sm';
            document.getElementById('createJsonTabBtn').className = 'btn btn-primary btn-sm';
            document.getElementById('createSyncBtn').style.display = 'none';
            document.getElementById('createSyncJsonBtn').style.display = 'inline-block';
            document.getElementById('createDocumentJson').value = '';
            document.getElementById('createFormFieldList').innerHTML = '';
            createFieldCounter = 0;
            openModal('createDocumentModal');
        }

        // Override saveDocument to handle form mode
        const origSaveDocument = saveDocument;
        saveDocument = async function() {
            if (editFormMode === 'form') {
                const editFormList = document.getElementById('editFormFieldList');
                if (editFormList && editFormList.children.length > 0) {
                    syncEditFormToJson();
                }
            }
            const collectionName = document.getElementById('editCollectionName').value;
            const documentId = document.getElementById('editDocumentId').value;
            const jsonInput = document.getElementById('editDocumentJson').value;

            if (!jsonInput.trim()) {
                alert('Please enter valid JSON');
                return;
            }

            try {
                const data = JSON.parse(jsonInput);
                const result = await apiCall(`/collections/${encodeURIComponent(collectionName)}/documents/${documentId}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (result.success) {
                    alert('Document updated!');
                    closeModal('editDocumentModal');
                    showCollection(collectionName);
                } else {
                    alert(result.message || 'Failed to update document');
                }
            } catch (error) {
                alert('Invalid JSON: ' + error.message);
            }
        };

        // Initialize
        if (isLoggedIn) {
            loadPage('dashboard');
        }
    </script>
</body>
</html>
