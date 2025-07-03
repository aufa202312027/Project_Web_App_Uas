<?php
/**
 * Login Page
 * Halaman login untuk authentication
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php';
    header('Location: ' . $redirect);
    exit;
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Attempt login
        $user = validateLogin($username, $password);
        
        if ($user) {
            // Login successful
            if (loginUser($user)) {
                // Set remember me cookie if requested
                if ($remember_me) {
                    $cookie_value = base64_encode($user['id'] . ':' . $user['username']);
                    setcookie('remember_user', $cookie_value, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                // Redirect based on role
                $redirect_url = isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php';
                
                // Check for redirect parameter
                if (isset($_GET['redirect'])) {
                    $redirect_url = $_GET['redirect'];
                }
                
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $error_message = 'Login failed. Please try again.';
            }
        } else {
            $error_message = 'Invalid username or password.';
        }
    }
}

// Check for flash messages
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'error') {
        $error_message = $flash['message'];
    } else {
        $success_message = $flash['message'];
    }
}

// Check for remember me cookie
$remembered_username = '';
if (isset($_COOKIE['remember_user'])) {
    $cookie_data = base64_decode($_COOKIE['remember_user']);
    $parts = explode(':', $cookie_data);
    if (count($parts) === 2) {
        $remembered_username = $parts[1];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 400px;
            width: 100%;
            margin: 20px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 300;
        }
        
        .login-header .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .form-check {
            margin: 20px 0;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .demo-credentials h6 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .demo-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2>Welcome Back</h2>
            <p class="mb-0">Sign in to your account</p>
        </div>
        
        <div class="login-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           placeholder="Username or Email"
                           value="<?= htmlspecialchars($remembered_username) ?>"
                           required>
                    <label for="username">
                        <i class="fas fa-user me-2"></i>Username or Email
                    </label>
                    <div class="invalid-feedback">
                        Please enter your username or email.
                    </div>
                </div>
                
                <div class="form-floating">
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           placeholder="Password"
                           required>
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <div class="invalid-feedback">
                        Please enter your password.
                    </div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="remember_me" 
                           name="remember_me"
                           <?= $remembered_username ? 'checked' : '' ?>>
                    <label class="form-check-label" for="remember_me">
                        Remember me for 30 days
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In
                </button>
            </form>
            
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h6><i class="fas fa-info-circle me-2"></i>Demo Credentials</h6>
                <div class="demo-item">
                    <strong>Admin:</strong>
                    <span>admin / password</span>
                </div>
                <div class="demo-item">
                    <strong>User:</strong>
                    <span>user1 / password</span>
                </div>
            </div>
            
            <div class="footer-text">
                <small>
                    <i class="fas fa-shield-alt me-1"></i>
                    Secure login powered by <?= APP_NAME ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Focus on username field
        document.getElementById('username').focus();
        
        // Show/hide password
        const passwordField = document.getElementById('password');
        const togglePassword = document.createElement('span');
        togglePassword.innerHTML = '<i class="fas fa-eye"></i>';
        togglePassword.style.cssText = 'position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;';
        passwordField.parentNode.style.position = 'relative';
        passwordField.parentNode.appendChild(togglePassword);
        
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    </script>
</body>
</html>