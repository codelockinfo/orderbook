<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

// Forgot password configuration
if (!defined('RESET_CODE_EXPIRY_MINUTES')) {
    define('RESET_CODE_EXPIRY_MINUTES', 10); // minutes
}

if (!defined('RESET_CODE_RESEND_COOLDOWN_SECONDS')) {
    define('RESET_CODE_RESEND_COOLDOWN_SECONDS', 60); // seconds between requests
}

if (!defined('RESET_CODE_MAX_ATTEMPTS')) {
    define('RESET_CODE_MAX_ATTEMPTS', 5);
}

if (!defined('RESET_EMAIL_FROM')) {
    $defaultResetFrom = SMTP_FROM_EMAIL ?: (getenv('RESET_EMAIL_FROM') ?: 'no-reply@evently.local');
    define('RESET_EMAIL_FROM', $defaultResetFrom);
}

if (!defined('RESET_EMAIL_NAME')) {
    define('RESET_EMAIL_NAME', getenv('RESET_EMAIL_NAME') ?: (SMTP_FROM_NAME ?: 'Evently'));
}

/**
 * Send password reset code via email.
 */
function sendPasswordResetEmail(string $email, string $username, string $code): bool {
    $subject = 'Your Evently password reset code';
    $expiresText = RESET_CODE_EXPIRY_MINUTES . ' minute' . (RESET_CODE_EXPIRY_MINUTES === 1 ? '' : 's');
    
    // Create HTML email body
    $htmlMessage = generatePasswordResetEmailHTML($username, $email, $code, $expiresText);
    
    // Plain text fallback
    $textMessage = "Hi {$username},\n\n"
        . "Here is your password reset code:\n\n"
        . "{$code}\n\n"
        . "Enter this code in Evently within {$expiresText} to reset your password.\n"
        . "If you did not request this, you can safely ignore this email.\n\n"
        . "Thanks,\nEvently";

    $sent = sendAppEmail($email, $subject, $htmlMessage, $textMessage);

    if (!$sent) {
        error_log("Password reset code for {$email}: {$code}");
    }

    return $sent;
}

/**
 * Generate HTML email template for password reset
 */
function generatePasswordResetEmailHTML(string $username, string $email, string $code, string $expiresText): string {
    $baseUrl = BASE_URL;
    
    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #4A90E2;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #4A90E2; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header with illustration -->
                    <tr>
                        <td align="center" style="padding: 40px 40px 20px 40px;">
                            <div style="position: relative; display: inline-block;">
                                <!-- Envelope illustration -->
                                <div style="width: 80px; height: 60px; background-color: #E74C3C; border-radius: 4px; position: relative; margin: 0 auto;">
                                    <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 40px solid transparent; border-right: 40px solid transparent; border-bottom: 15px solid #C0392B;"></div>
                                    <div style="position: absolute; top: 15px; left: 10px; width: 60px; height: 40px; background-color: #ffffff; border-radius: 2px;"></div>
                                </div>
                                <!-- Checkmark circle -->
                                <div style="position: absolute; top: -5px; right: -10px; width: 40px; height: 40px; background-color: #27AE60; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <span style="color: #ffffff; font-size: 24px; font-weight: bold;">✓</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Heading -->
                    <tr>
                        <td align="center" style="padding: 0 40px 20px 40px;">
                            <h1 style="margin: 0; font-size: 28px; font-weight: 700; color: #2C3E50; text-align: center;">Your authentication code</h1>
                        </td>
                    </tr>
                    
                    <!-- Instructions -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <p style="margin: 0 0 15px 0; font-size: 16px; line-height: 1.6; color: #34495E; text-align: center;">
                                You\'ve entered <strong style="color: #2C3E50;">' . htmlspecialchars($email) . '</strong> as the email address for your account.
                            </p>
                            <p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #34495E; text-align: center;">
                                Please use the authentication code below to verify your identity and reset your password.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Reset Code Display -->
                    <tr>
                        <td align="center" style="padding: 0 40px 30px 40px;">
                            <div style="background-color: #F8F9FA; border: 2px dashed #DEE2E6; border-radius: 8px; padding: 25px 30px; margin: 0 auto; display: inline-block;">
                                <div style="font-size: 36px; font-weight: 700; letter-spacing: 10px; color: #2C3E50; font-family: \'Courier New\', monospace; text-align: center;">
                                    ' . htmlspecialchars($code) . '
                                </div>
                            </div>
                            <p style="margin: 20px 0 0 0; font-size: 16px; line-height: 1.5; color: #34495E; text-align: center; font-weight: 600;">
                                Enter this code in Evently to verify your identity
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Expiry notice -->
                    <tr>
                        <td align="center" style="padding: 0 40px 20px 40px;">
                            <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #7F8C8D; text-align: center;">
                                This authentication code will expire in <strong>' . htmlspecialchars($expiresText) . '</strong>.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px 40px 40px; border-top: 1px solid #ECF0F1;">
                            <p style="margin: 0 0 10px 0; font-size: 14px; line-height: 1.5; color: #95A5A6; text-align: center;">
                                If you did not request this password reset, you can safely ignore this email.
                            </p>
                            <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #95A5A6; text-align: center;">
                                For security reasons, please do not share this code with anyone.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Send an application email using SMTP when configured, otherwise fall back to PHP mail().
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody HTML email body
 * @param string|null $textBody Plain text fallback (optional)
 */
function sendAppEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool {
    $fromEmail = SMTP_FROM_EMAIL ?: RESET_EMAIL_FROM;
    $fromName = SMTP_FROM_NAME ?: RESET_EMAIL_NAME;

    // If no text body provided, create a simple one from HTML
    if ($textBody === null) {
        $textBody = strip_tags($htmlBody);
        $textBody = html_entity_decode($textBody, ENT_QUOTES, 'UTF-8');
    }

    // Create multipart email with HTML and plain text
    $boundary = uniqid('evently_email_');
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $textBody . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--{$boundary}--";

    $headers = [
        "From: " . formatEmailAddress($fromEmail, $fromName),
        "Reply-To: {$fromEmail}",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        "Content-Transfer-Encoding: 8bit"
    ];

    if (shouldUseSmtpTransport()) {
        try {
            return smtpSendEmail([
                'host' => SMTP_HOST,
                'port' => SMTP_PORT ?: 587,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USERNAME,
                'password' => SMTP_PASSWORD,
                'fromEmail' => $fromEmail,
                'fromName' => $fromName,
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'isHtml' => true,
                'boundary' => $boundary
            ]);
        } catch (Exception $e) {
            error_log('SMTP send error: ' . $e->getMessage());
        }
    }

    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function shouldUseSmtpTransport(): bool {
    return !empty(SMTP_HOST) && !empty(SMTP_USERNAME) && !empty(SMTP_PASSWORD);
}

function formatEmailAddress(string $email, string $name = ''): string {
    $cleanName = trim($name);
    if ($cleanName === '') {
        return $email;
    }
    return sprintf('"%s" <%s>', addslashes($cleanName), $email);
}

function smtpSendEmail(array $params): bool {
    $host = $params['host'] ?? '';
    if (empty($host)) {
        throw new Exception('SMTP host is not configured.');
    }

    $port = (int)($params['port'] ?? 587);
    $encryption = strtolower($params['encryption'] ?? 'tls');
    $timeout = 30;

    $transportHost = $host;
    if ($encryption === 'ssl') {
        $transportHost = 'ssl://' . $host;
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client(
        $transportHost . ':' . $port,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        throw new Exception("SMTP connection failed: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, $timeout);

    smtpExpect($socket, 220);
    $hostname = gethostname() ?: 'localhost';
    smtpCommand($socket, "EHLO {$hostname}", 250);

    if ($encryption === 'tls' || $encryption === 'starttls') {
        smtpCommand($socket, "STARTTLS", 220);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new Exception('Unable to establish TLS encryption with SMTP server.');
        }
        smtpCommand($socket, "EHLO {$hostname}", 250);
    }

    if (!empty($params['username'])) {
        smtpCommand($socket, "AUTH LOGIN", 334);
        smtpCommand($socket, base64_encode($params['username']), 334);
        smtpCommand($socket, base64_encode($params['password']), 235);
    }

    $fromEmail = $params['fromEmail'] ?? '';
    $toEmail = $params['to'] ?? '';

    smtpCommand($socket, "MAIL FROM:<{$fromEmail}>", 250);
    smtpCommand($socket, "RCPT TO:<{$toEmail}>", 250);
    smtpCommand($socket, "DATA", 354);

    $message = buildSmtpMessage(
        $params['fromEmail'] ?? '',
        $params['fromName'] ?? '',
        $toEmail,
        $params['subject'] ?? '',
        $params['body'] ?? '',
        $host,
        $params['isHtml'] ?? false,
        $params['boundary'] ?? null
    );

    fwrite($socket, $message);
    smtpCommand($socket, ".", 250);
    smtpCommand($socket, "QUIT", 221);
    fclose($socket);

    return true;
}

function buildSmtpMessage(
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $subject,
    string $body,
    string $host,
    bool $isHtml = false,
    ?string $boundary = null
): string {
    $contentType = $isHtml && $boundary 
        ? "multipart/alternative; boundary=\"{$boundary}\""
        : ($isHtml 
            ? "text/html; charset=UTF-8" 
            : "text/plain; charset=UTF-8");

    $headers = [
        "From: " . formatEmailAddress($fromEmail, $fromName),
        "To: {$toEmail}",
        "Subject: {$subject}",
        "MIME-Version: 1.0",
        "Content-Type: {$contentType}",
        "Content-Transfer-Encoding: 8bit",
        "Date: " . date(DATE_RFC2822),
        "Message-ID: <" . uniqid('evently-', true) . '@' . $host . ">"
    ];

    $normalizedBody = preg_replace("/\r\n|\r|\n/", "\r\n", $body);
    $normalizedBody = preg_replace("/^\./m", '..', $normalizedBody ?? '');

    return implode("\r\n", $headers) . "\r\n\r\n" . $normalizedBody . "\r\n";
}

function smtpCommand($socket, string $command, int $expectedCode): void {
    fwrite($socket, $command . "\r\n");
    smtpExpect($socket, $expectedCode);
}

function smtpExpect($socket, int $expectedCode): void {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if (strpos($response, (string)$expectedCode) !== 0) {
        throw new Exception("Unexpected SMTP server response: {$response}");
    }
}

/**
 * Get user data by email.
 */
function getUserByEmail(PDO $db, string $email): ?array {
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Fetch the latest active reset record for a user.
 */
function getActiveResetRecord(PDO $db, int $userId): ?array {
    $stmt = $db->prepare("
        SELECT * FROM password_reset_codes
        WHERE user_id = ? AND used = 0
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $record = $stmt->fetch();

    if ($record && strtotime($record['expires_at']) < time()) {
        markResetCodeUsed($db, (int)$record['id']);
        return null;
    }

    return $record ?: null;
}

/**
 * Mark reset code as used.
 */
function markResetCodeUsed(PDO $db, int $codeId): void {
    $stmt = $db->prepare("
        UPDATE password_reset_codes
        SET used = 1, used_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$codeId]);
}

/**
 * Increment invalid attempt counter.
 */
function incrementResetAttempts(PDO $db, int $codeId, int $currentAttempts): void {
    $newAttempts = $currentAttempts + 1;

    if ($newAttempts >= RESET_CODE_MAX_ATTEMPTS) {
        $stmt = $db->prepare("
            UPDATE password_reset_codes
            SET attempts = ?, used = 1, used_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newAttempts, $codeId]);
    } else {
        $stmt = $db->prepare("
            UPDATE password_reset_codes
            SET attempts = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newAttempts, $codeId]);
    }
}

/**
 * Calculate seconds remaining until another code can be requested.
 */
function getResetCooldown(PDO $db, int $userId): ?int {
    $stmt = $db->prepare("
        SELECT created_at FROM password_reset_codes
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $latest = $stmt->fetchColumn();

    if (!$latest) {
        return null;
    }

    $elapsed = time() - strtotime($latest);

    if ($elapsed >= RESET_CODE_RESEND_COOLDOWN_SECONDS) {
        return null;
    }

    return RESET_CODE_RESEND_COOLDOWN_SECONDS - $elapsed;
}

/**
 * Deactivate previous active codes for a user.
 */
function deactivateExistingCodes(PDO $db, int $userId): void {
    $stmt = $db->prepare("
        UPDATE password_reset_codes
        SET used = 1, used_at = NOW(), updated_at = NOW()
        WHERE user_id = ? AND used = 0
    ");
    $stmt->execute([$userId]);
}

/**
 * Validate an input code against the stored hash.
 */
function validateResetCode(PDO $db, ?array $record, string $code): array {
    if (!$record) {
        return [
            'valid' => false,
            'message' => 'Reset code expired or not found. Request a new code.'
        ];
    }

    if (strtotime($record['expires_at']) < time()) {
        markResetCodeUsed($db, (int)$record['id']);
        return [
            'valid' => false,
            'message' => 'Reset code has expired. Request a new code.'
        ];
    }

    if (!password_verify($code, $record['code_hash'])) {
        incrementResetAttempts($db, (int)$record['id'], (int)$record['attempts']);
        $attempts = (int)$record['attempts'] + 1;
        $remaining = max(0, RESET_CODE_MAX_ATTEMPTS - $attempts);
        $message = $remaining > 0
            ? "Invalid code. {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."
            : 'Too many invalid attempts. Request a new code.';

        return [
            'valid' => false,
            'message' => $message
        ];
    }

    return [
        'valid' => true,
        'record' => $record
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($action === 'register' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }
    
    try {
        // Check if username or email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}

else if ($action === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID on login for security
            session_regenerate_id(true);
            
            // IMPORTANT: Ensure session cookie is set with 7-day lifetime
            // This is critical for Flutter WebView apps to maintain login state
            ensureSessionCookieLifetime();
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_regeneration'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
    }
}

else if ($action === 'request_reset_code' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    try {
        $user = getUserByEmail($db, $email);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No account found with that email']);
            exit;
        }

        $cooldown = getResetCooldown($db, (int)$user['id']);
        if ($cooldown !== null) {
            echo json_encode([
                'success' => false,
                'message' => 'Please wait ' . $cooldown . ' seconds before requesting another code.',
                'retry_after' => $cooldown
            ]);
            exit;
        }

        deactivateExistingCodes($db, (int)$user['id']);

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . RESET_CODE_EXPIRY_MINUTES . ' minutes'));

        $stmt = $db->prepare("
            INSERT INTO password_reset_codes (user_id, code_hash, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([(int)$user['id'], $codeHash, $expiresAt]);

        $emailSent = sendPasswordResetEmail($user['email'], $user['username'], $code);

        echo json_encode([
            'success' => true,
            'message' => $emailSent
                ? 'A reset code has been sent to your email.'
                : 'Reset code generated, but the email could not be sent. Contact support if this continues.',
            'email_sent' => $emailSent,
            'expires_in' => RESET_CODE_EXPIRY_MINUTES * 60
        ]);
    } catch (Exception $e) {
        error_log('request_reset_code error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to create a reset code. Please try again.']);
    }
}

else if ($action === 'verify_reset_code' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $code = trim($data['code'] ?? '');

    if (empty($email) || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Email and code are required']);
        exit;
    }

    try {
        $user = getUserByEmail($db, $email);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or code']);
            exit;
        }

        $record = getActiveResetRecord($db, (int)$user['id']);
        $validation = validateResetCode($db, $record, $code);

        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Code verified.']);
    } catch (Exception $e) {
        error_log('verify_reset_code error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to verify code. Please try again.']);
    }
}

else if ($action === 'reset_password' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $code = trim($data['code'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($code) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email, code, and new password are required']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    try {
        $user = getUserByEmail($db, $email);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or code']);
            exit;
        }

        $record = getActiveResetRecord($db, (int)$user['id']);
        $validation = validateResetCode($db, $record, $code);

        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, (int)$user['id']]);

            markResetCodeUsed($db, (int)$validation['record']['id']);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        echo json_encode(['success' => true, 'message' => 'Password has been reset. You can now log in.']);
    } catch (Exception $e) {
        error_log('reset_password error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to reset password. Please try again.']);
    }
}

else if ($action === 'logout' && $method === 'POST') {
    // Clear all session data
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Explicitly clear the session cookie (important for WebView apps)
    $sessionName = session_name();
    $params = session_get_cookie_params();
    
    // Clear cookie for current domain and possible variations
    setcookie($sessionName, '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    setcookie($sessionName, '', time() - 3600, '/', '', $params['secure'], $params['httponly']);
    
    // Also clear for domain variations (for WebView compatibility)
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if (!empty($host)) {
        $hostParts = explode(':', $host);
        $hostDomain = $hostParts[0];
        setcookie($sessionName, '', time() - 3600, '/', $hostDomain, $params['secure'], $params['httponly']);
        setcookie($sessionName, '', time() - 3600, '/', '.' . $hostDomain, $params['secure'], $params['httponly']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

else if ($action === 'check' && $method === 'GET') {
    if (isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

