<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');
    
    $email = sanitizeInput($_POST['email']);
    $accountType = $_POST['account_type'] ?? 'user';
    $table = ($accountType === 'admin') ? 'admins' : 'users';

    $account = getUserByEmail($email, $table);

    // Security practice: Always show a success message even if the email doesn't exist. 
    // This stops hackers from guessing which emails are registered on your platform!
    if ($account) {
        // Generate a long, secure token
        $token = bin2hex(random_bytes(32));
        storeResetToken($email, $accountType, $token);

        // Build the validation confirmation link string
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        sendResetEmail($email, $email, $resetLink);
    }

    setFlashMessage('If the account exists, a secure password recovery instruction link has been dispatched.', 'success');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Secure System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-wrapper { width: 100%; max-width: 480px; padding: 20px; }
        .card { background: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 30px 24px; border-left: 5px solid #2196f3; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 11px 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .action-btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; color: #fff; cursor: pointer; background-color: #2196f3; }
        .footer-text { text-align: center; margin-top: 20px; font-size: 14px; }
        .footer-text a { color: #2196f3; text-decoration: none; font-weight: 600; }
        .success-message { background-color: #e6f4ea; color: #137333; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border-left: 3px solid #188038; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="card">
        <h2 style="margin-top:0;">Account Recovery</h2>
        <p style="color:#666; font-size:14px; margin-bottom:20px;">Provide your registered credentials below to initialize a password reset routing flow.</p>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="form-group">
                <label for="email">Account Email</label>
                <input type="email" name="email" id="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="account_type">Account Role Type</label>
                <select name="account_type" id="account_type" required>
                    <option value="user">Standard User</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <button type="submit" class="action-btn">Send Recovery Instructions</button>
        </form>
        <div class="footer-text"><a href="index.php">Back to Login</a></div>
    </div>
</div>
</body>
</html>