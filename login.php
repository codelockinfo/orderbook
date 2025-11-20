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
    <link rel="stylesheet" href="assets/css/style2.css">
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

            <div id="loginSection">
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

                    <div class="auth-helper-links">
                        <button type="button" class="text-link" id="forgotPasswordLink">
                            Forgot password?
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>

            <div id="forgotPasswordSection" class="hidden">
                <div class="forgot-header">
                    <button type="button" class="text-link back-link" id="backToLogin">
                        <i class="fas fa-arrow-left"></i> Back to login
                    </button>
                    <h4>Reset your password</h4>
                    <p class="instruction-text">
                        Request a one-time code and enter it below to create a new password.
                    </p>
                </div>

                <div id="forgotMessage" class="error-message"></div>
                <div id="forgotSuccess" class="status-message success-message"></div>

                <div id="forgotStepRequest" class="forgot-step active">
                    <form id="requestResetForm">
                        <div class="form-group">
                            <label for="resetEmailRequest">Email address</label>
                            <input type="email" id="resetEmailRequest" name="resetEmailRequest" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Send verification code</button>
                    </form>
                </div>

                <div id="forgotStepReset" class="forgot-step">
                    <form id="resetPasswordForm">
                        <div class="form-group">
                            <label for="resetEmailConfirm">Email address</label>
                            <input type="email" id="resetEmailConfirm" name="resetEmailConfirm" readonly>
                        </div>
                        <div class="form-group">
                            <label for="resetCode">Verification code</label>
                            <input type="text" id="resetCode" name="resetCode" maxlength="6" pattern="\d{6}" placeholder="123456" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="newPassword" name="newPassword" required>
                                <i class="fas fa-eye password-toggle" id="toggleNewPassword"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirmNewPassword">Confirm new password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                                <i class="fas fa-eye password-toggle" id="toggleConfirmNewPassword"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Reset password</button>
                    </form>
                </div>
            </div>
            
            <p class="auth-switch">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/auth1.js"></script>
</body>
</html>

