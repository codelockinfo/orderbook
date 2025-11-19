<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4CAF50">
    <title>Login - Evently</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style1.css">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/images/icon-192.png">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo">
                <img src="assets/images/bookify logo (5).png" alt="Evently" height="50px" width="50px">
                <h1>Evently</h1>
            </div>
            <h3>Login to your account</h3>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <div id="error-message" class="error-message"></div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="auth-switch">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>

