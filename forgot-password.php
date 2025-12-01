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
    <title>Forgot Password - Evently</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style3.css">
    <link rel="manifest" href="manifest.php">
    <link rel="icon" type="image/png" href="assets/images/icon-192.png">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo">
                <img src="assets/images/bookify logo (5).png" alt="Evently" height="50px" width="50px">
                <h1>Evently</h1>
            </div>
            <h3>Reset your password</h3>
            
            <div class="forgot-header">
                <a href="login.php" class="text-link back-link">
                    <i class="fas fa-arrow-left"></i> Back to login
                </a>
            </div>

            <p class="instruction-text">
                Request a one-time code and enter it below to create a new password.
            </p>

            <div id="error-message" class="error-message"></div>
            <div id="success-message" class="status-message success-message"></div>

            <form id="requestResetForm">
                <div class="form-group">
                    <label for="resetEmailRequest">Email address</label>
                    <input type="email" id="resetEmailRequest" name="resetEmailRequest" required>
                </div>
                <button type="submit" class="btn btn-primary">Send verification code</button>
            </form>
            
            <p class="auth-switch">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/forgot-password.js"></script>
</body>
</html>

