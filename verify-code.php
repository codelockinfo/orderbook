<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Check if email is provided via GET parameter
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
if (empty($email)) {
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
    <title>Verify Code - Evently</title>
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
            <h3>Verify your code</h3>
            
            <div class="forgot-header">
                <a href="login.php" class="text-link back-link">
                    <i class="fas fa-arrow-left"></i> Back to login
                </a>
            </div>

            <p class="instruction-text">
                Enter the verification code sent to your email address.
            </p>

            <div id="error-message" class="error-message"></div>
            <div id="success-message" class="status-message success-message"></div>

            <form id="verifyCodeForm">
                <div class="form-group">
                    <label for="resetEmailConfirm">Email address</label>
                    <input type="email" id="resetEmailConfirm" name="resetEmailConfirm" value="<?php echo $email; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="resetCode">Verification code</label>
                    <input type="text" id="resetCode" name="resetCode" maxlength="6" pattern="\d{6}" placeholder="123456" required autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-primary">Verify code</button>
            </form>
            
            <p class="auth-switch">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
    
    <script src="assets/js/verify-code.js"></script>
</body>
</html>

