<?php
session_start();
// If PHP session is still valid, redirect to dashboard (avoids redirect loop
// when sessionStorage exists but PHP session expired)
if (isset($_SESSION['dashboard_logged_in']) && $_SESSION['dashboard_logged_in'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite_Shelf - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3a7cb8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .login-header .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #3a7cb8, #1e3a5f);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: white;
        }

        .login-header h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #3a7cb8;
            box-shadow: 0 0 0 4px rgba(58, 124, 184, 0.15);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3a7cb8, #1e3a5f);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(58, 124, 184, 0.35);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .error-msg {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid #fecaca;
        }

        .error-msg.show {
            display: block;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">&#9776;</div>
            <h1>Lite_Shelf</h1>
            <p>Sign in to manage your applications</p>
        </div>

        <div class="error-msg" id="errorMsg"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
        </form>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const errorMsg = document.getElementById('errorMsg');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.classList.remove('show');
            loginBtn.disabled = true;
            loginBtn.textContent = 'Signing in...';

            try {
                const formData = new FormData(loginForm);
                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    sessionStorage.setItem('dashboard_auth', 'true');
                    window.location.href = 'index.php';
                } else {
                    const message = result.error?.message || result.error || 'Invalid username or password';
                    errorMsg.textContent = message;
                    errorMsg.classList.add('show');
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Sign In';
                }
            } catch (err) {
                errorMsg.textContent = 'Connection error. Please try again.';
                errorMsg.classList.add('show');
                loginBtn.disabled = false;
                loginBtn.textContent = 'Sign In';
            }
        });
    </script>
</body>
</html>
