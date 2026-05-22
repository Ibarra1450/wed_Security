<?php
// --- ERROR REPORTING FOR DEVELOPMENT TROUBLESHOOTING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoloader for email functionality
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// --- 1. SECURE SESSION COOKIE MANAGEMENT ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Session timeout constants
define('USER_TIMEOUT', 1800);  // 30 minutes in seconds for standard accounts
define('ADMIN_TIMEOUT', 900);   // Aggressive 15 minutes in seconds for administrators

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check session timeout and update last activity
 */
function checkSessionTimeout()
{
    if (isLoggedIn()) {
        $timeoutLimit = ($_SESSION['role'] === 'admin') ? ADMIN_TIMEOUT : USER_TIMEOUT;

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutLimit)) {
            session_unset();
            session_destroy();

            session_start();
            setFlashMessage('Your session has expired due to inactivity. Please log in again.', 'error');
            header('Location: index.php');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Redirect if not logged in
 */
function requireLogin()
{
    checkSessionTimeout();

    if (!isLoggedIn()) {
        setFlashMessage('Please log in to access this page.', 'error');
        header('Location: index.php');
        exit;
    }
}

/**
 * Role-based access control
 */
function requireRole($requiredRole)
{
    requireLogin();

    if ($_SESSION['role'] !== $requiredRole) {
        setFlashMessage('You do not have permission to access this page.', 'error');

        if ($_SESSION['role'] === 'admin') {
            header('Location: dashboard_Admin.php');
        } else {
            header('Location: dashboard_User.php');
        }
        exit;
    }
}

/**
 * Generate a CSRF token and store it in the session
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token match context.');
    }
}

/**
 * Basic input sanitization
 */
function sanitizeInput($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a flash message (will be shown in the chocolate bar)
 */
function setFlashMessage($message, $type = 'success')
{
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Get user by email from specific table
 */
function getUserByEmail($email, $table)
{
    global $pdo;

    if ($table !== 'users' && $table !== 'admins') {
        return false;
    }

    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, password FROM $table WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Check if email exists in specific table
 */
function userExists($email, $table)
{
    global $pdo;

    if ($table !== 'users' && $table !== 'admins') {
        return false;
    }

    $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Log user activity
 */
function logActivity($userId, $role, $action)
{
    $logEntry = date('Y-m-d H:i:s') . " - User ID: $userId, Role: $role, Action: $action" . PHP_EOL;
    file_put_contents('activity.log', $logEntry, FILE_APPEND);
}

/**
 * --- 2. INTEGRATED PHPMailer OTP DELIVERY FUNCTION ---
 */
function sendOTPEmail($recipientEmail, $recipientName, $otpCode)
{
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("OTP Generation Warning: PHPMailer missing. Relying on backup log output instead.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // --- SMTP Server Settings ---
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = getenv('SMTP_USERNAME') ?: 'cpanel938@gmail.com';
        $mail->Password = getenv('SMTP_PASSWORD') ?: 'jymr oqka drri nwhh';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // --- SMTP Connection Tweaks for Local Dev Environments (XAMPP/WAMP) ---
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // --- Identity Headers ---
        $mail->setFrom('cpanel938@gmail.com', 'Secure Auth System');
        $mail->addAddress($recipientEmail, $recipientName);

        // --- HTML Email Content ---
        $mail->isHTML(true);
        $mail->Subject = 'Your 6-Digit Verification Code';

        $mail->Body = "
            <div style=\"font-family: 'Segoe UI', Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff;\">
                <div style=\"border-left: 5px solid #2196f3; padding-left: 15px; margin-bottom: 20px;\">
                    <h2 style=\"color: #1a1a1a; margin: 0; font-size: 20px; font-weight: 700;\">Security Verification Code</h2>
                </div>
                <p style=\"color: #4a5568; font-size: 14px; line-height: 1.6;\">Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
                <p style=\"color: #4a5568; font-size: 14px; line-height: 1.6;\">A request was made to authenticate into your account profile dashboard. Use the code below to complete authorization rules:</p>
                
                <div style=\"background-color: #f8f9fa; padding: 18px; text-align: center; border-radius: 6px; margin: 24px 0; border: 1px dashed #cbd5e0;\">
                    <span style=\"font-size: 32px; font-weight: 700; letter-spacing: 5px; color: #512da8;\">" . $otpCode . "</span>
                </div>
                
                <p style=\"color: #718096; font-size: 12px; line-height: 1.5;\">This verification system payload parameter is fragile and short-lived. If you did not execute this authentication query sequence, change your root security account parameters immediately.</p>
            </div>
        ";

        $mail->AltBody = "Your verification authorization code is: " . $otpCode;

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("SMTP Error: Code generation failed. Reason: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * --- 3. PASSWORD RESET DATABASE SYSTEM OPERATIONS ---
 */

/**
 * Store password reset token details safely
 */
function storeResetToken($email, $accountType, $token)
{
    global $pdo;
    // Clear out residual expired recovery history records matching this footprint
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND account_type = ?");
    $stmt->execute([$email, $accountType]);

    // Insert the token parameters with an explicit 1 hour expiration ceiling
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, account_type, token, expires_at) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$email, $accountType, $token, $expiresAt]);
}

/**
 * Validate token parameters vs database baseline records
 */
function verifyResetToken($token)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT email, account_type FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Update the targeted user account credential string and trash used recovery token
 */
function updatePasswordAndClearToken($email, $table, $newPassword, $token)
{
    global $pdo;
    if ($table !== 'users' && $table !== 'admins') {
        return false;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Process core account structural modification
    $stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE email = ?");
    if ($stmt->execute([$hashedPassword, $email])) {
        // Enforce singular execution constraints by erasing used validation keys
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        return true;
    }
    return false;
}

/**
 * Dedicated transactional email template delivery routine for password reset workflows
 */
function sendResetEmail($recipientEmail, $recipientName, $resetLink)
{
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cpanel938@gmail.com';
        $mail->Password = 'jymr oqka drri nwhh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('cpanel938@gmail.com', 'Secure Auth System');
        $mail->addAddress($recipientEmail, $recipientName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Account Password';

        $mail->Body = "
            <div style=\"font-family: 'Segoe UI', Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff;\">
                <div style=\"border-left: 5px solid #2196f3; padding-left: 15px; margin-bottom: 20px;\">
                    <h2 style=\"color: #1a1a1a; margin: 0; font-size: 20px; font-weight: 700;\">Password Reset Requested</h2>
                </div>
                <p style=\"color: #4a5568; font-size: 14px; line-height: 1.6;\">Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
                <p style=\"color: #4a5568; font-size: 14px; line-height: 1.6;\">We received a system request to reconfigure your portal workspace credentials. Click the validation target button link mapped below to establish a clean credential pair:</p>
                <div style=\"text-align: center; margin: 30px 0;\">
                    <a href=\"" . $resetLink . "\" style=\"background-color: #512da8; color: white; padding: 12px 24px; text-decoration: none; font-weight: 600; border-radius: 6px; display: inline-block; font-size: 14px;\">Configure New Password</a>
                </div>
                <p style=\"color: #718096; font-size: 12px; line-height: 1.5;\">This single-use reset interface remains active for precisely 60 minutes. If you did not initialize this transactional request routing thread, you can disregard this payload communication securely.</p>
            </div>
        ";
        $mail->AltBody = "Reset your system account profile credentials by executing this route verification link address: " . $resetLink;

        return $mail->send();
    } catch (\Exception $e) {
        error_log("SMTP Error: Password recovery email delivery failure: {$mail->ErrorInfo}");
        return false;
    }
}
?>