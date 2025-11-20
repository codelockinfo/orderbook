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
    $message = "Hi {$username},\n\n"
        . "Here is your password reset code:\n\n"
        . "{$code}\n\n"
        . "Enter this code in Evently within {$expiresText} to reset your password.\n"
        . "If you did not request this, you can safely ignore this email.\n\n"
        . "Thanks,\nEvently";

    $sent = sendAppEmail($email, $subject, $message);

    if (!$sent) {
        error_log("Password reset code for {$email}: {$code}");
    }

    return $sent;
}

/**
 * Send an application email using SMTP when configured, otherwise fall back to PHP mail().
 */
function sendAppEmail(string $to, string $subject, string $body): bool {
    $fromEmail = SMTP_FROM_EMAIL ?: RESET_EMAIL_FROM;
    $fromName = SMTP_FROM_NAME ?: RESET_EMAIL_NAME;

    $headers = [
        "From: " . formatEmailAddress($fromEmail, $fromName),
        "Reply-To: {$fromEmail}",
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
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
                'body' => $body
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
        $host
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
    string $host
): string {
    $headers = [
        "From: " . formatEmailAddress($fromEmail, $fromName),
        "To: {$toEmail}",
        "Subject: {$subject}",
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
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
    session_destroy();
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

