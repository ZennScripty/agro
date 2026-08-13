<?php
/**
 * SAMRIDHI AGRO - Agent Login
 * 
 * This page handles agent authentication with secure login functionality.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration and functions
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// If already logged in and is agent, redirect to dashboard
if (isLoggedIn() && hasRole('agent')) {
    redirect('agent/dashboard.php');
    exit;
}

$error = '';
$username = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize input
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username/email and password.';
        } else {
            // Authenticate user
            $authResult = authenticateUser($username, $password);
            
            if ($authResult['success']) {
                // Check if user has agent role
                if (hasRole('agent')) {
                    // Check if agent is approved
                    $db = getDB();
                    $sql = "SELECT status FROM agents WHERE user_id = ?";
                    $agent = $db->fetchOne($sql, [$_SESSION['user_id']]);
                    
                    if ($agent && $agent['status'] === 'approved') {
                        // Clear any redirect URL
                        unset($_SESSION['redirect_after_login']);
                        
                        // Set success message
                        setFlashMessage('success', 'Welcome back, ' . $_SESSION['user_name'] . '!');
                        
                        // Redirect to dashboard
                        redirect('agent/dashboard.php');
                        exit;
                    } else {
                        // Logout non-approved agent
                        logoutUser();
                        $error = 'Your agent account is pending approval. Please wait for admin approval.';
                    }
                } else {
                    // Logout non-agent user
                    logoutUser();
                    $error = 'You do not have agent access. Please use the correct portal.';
                }
            } else {
                $error = $authResult['error'] ?? 'Invalid username or password.';
            }
        }
    }
}

// Generate CSRF token for the form
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - Samridhi Agro</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #F7FCF7 0%, #DCFCE7 100%);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .login-page::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(22, 163, 74, 0.05) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .login-page::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(101, 163, 13, 0.05) 0%, transparent 70%);
            animation: float 25s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(20, 83, 45, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .login-card:hover {
            box-shadow: 0 30px 80px rgba(20, 83, 45, 0.16);
            transform: translateY(-2px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #14532D, #16A34A);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: 0 8px 24px rgba(22, 163, 74, 0.25);
        }
        
        .login-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #052E16;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            color: #6B7A7B;
            font-weight: 400;
        }
        
        .login-subtitle span {
            color: #16A34A;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #14532D;
            margin-bottom: 8px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7A7B;
            font-size: 16px;
        }
        
        .input-group .form-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            border: 2px solid #E5EDE7;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            color: #052E16;
        }
        
        .input-group .form-input:focus {
            outline: none;
            border-color: #16A34A;
            background: white;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1);
        }
        
        .input-group .form-input.error {
            border-color: #DC2626;
            background: rgba(220, 38, 38, 0.05);
        }
        
        .input-group .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6B7A7B;
            cursor: pointer;
            padding: 4px;
        }
        
        .input-group .toggle-password:hover {
            color: #14532D;
        }
        
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #4A5B5D;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #16A34A;
            cursor: pointer;
        }
        
        .forgot-link {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #16A34A;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            color: #14532D;
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #14532D, #16A34A);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        .alert-success {
            background: #DCFCE7;
            color: #065F46;
            border: 1px solid #BBF7D0;
        }
        
        .alert-icon {
            font-size: 18px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #E5EDE7;
        }
        
        .login-footer p {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #6B7A7B;
        }
        
        .login-footer .portal-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 12px;
        }
        
        .login-footer .portal-links a {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #16A34A;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer .portal-links a:hover {
            color: #14532D;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
            .login-title {
                font-size: 24px;
            }
            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            .login-logo {
                width: 64px;
                height: 64px;
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h1 class="login-title">Samridhi Agro</h1>
                    <p class="login-subtitle">
                        <i class="fas fa-shield-alt" style="color: #16A34A; margin-right: 6px;"></i>
                        Agent <span>Portal</span>
                    </p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <span><?php echo escapeHtml($error); ?></span>
                </div>
                <?php endif; ?>
                
                <?php
                $flashMessages = getFlashMessages();
                foreach ($flashMessages as $type => $messages):
                    foreach ($messages as $message):
                ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'error' : 'success'; ?>">
                    <i class="fas fa-<?php echo $type === 'error' ? 'exclamation-circle' : 'check-circle'; ?> alert-icon"></i>
                    <span><?php echo escapeHtml($message); ?></span>
                </div>
                <?php 
                    endforeach;
                endforeach; 
                ?>
                
                <form method="POST" action="" id="loginForm" autocomplete="off">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <i class="fas fa-user" style="margin-right: 6px; color: #16A34A;"></i>
                            Username or Email
                        </label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input <?php echo $error ? 'error' : ''; ?>" 
                                placeholder="Enter your username or email"
                                value="<?php echo escapeHtml($username); ?>"
                                required
                                autofocus
                            >
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock" style="margin-right: 6px; color: #16A34A;"></i>
                            Password
                        </label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input <?php echo $error ? 'error' : ''; ?>" 
                                placeholder="Enter your password"
                                required
                            >
                            <i class="fas fa-key input-icon"></i>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-group">
                            <input type="checkbox" name="remember" value="1">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginButton">
                        <span id="buttonText">
                            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                            Sign In
                        </span>
                        <span class="spinner" id="buttonSpinner"></span>
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>
                        <i class="fas fa-copyright" style="margin-right: 4px;"></i>
                        <?php echo date('Y'); ?> Samridhi Agro. All rights reserved.
                    </p>
                    <div class="portal-links">
                        <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                        <a href="../admin/login.php"><i class="fas fa-user-shield"></i> Admin</a>
                        <a href="../shop/login.php"><i class="fas fa-store"></i> Shop</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        const buttonSpinner = document.getElementById('buttonSpinner');
        
        loginForm.addEventListener('submit', function() {
            loginButton.disabled = true;
            buttonText.style.display = 'none';
            buttonSpinner.style.display = 'block';
        });
        
        if (document.getElementById('username').value === '') {
            document.getElementById('username').focus();
        }
    });
    </script>
</body>
</html>