<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Check if email and code are provided via GET parameters
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';

if (empty($email) || empty($code)) {
    header('Location: forgot-password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4CAF50">
    <title>Reset Password - Evently</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style2.css">
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
            <h3>Create new password</h3>
            
            <div class="forgot-header">
                <a href="login.php" class="text-link back-link">
                    <i class="fas fa-arrow-left"></i> Back to login
                </a>
            </div>

            <p class="instruction-text">
                Create a new password for <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>

            <div id="error-message" class="error-message"></div>
            <div id="success-message" class="status-message success-message"></div>

            <form id="resetPasswordForm">
                <input type="hidden" id="resetEmailFinal" name="resetEmailFinal" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" id="resetCodeFinal" name="resetCodeFinal" value="<?php echo htmlspecialchars($code); ?>">
                <div class="form-group">
                    <label for="newPassword">New password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="newPassword" name="newPassword" required minlength="6">
                        <i class="fas fa-eye password-toggle" id="toggleNewPassword"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirm new password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirmNewPassword" name="confirmNewPassword" required minlength="6">
                        <i class="fas fa-eye password-toggle" id="toggleConfirmNewPassword"></i>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Reset password</button>
            </form>
            
            <p class="auth-switch">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/reset-password.js"></script>
</body>
</html>

