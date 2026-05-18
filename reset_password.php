<?php
require_once 'functions.php';

$token = $_GET['token'] ?? '';
$tokenData = verifyResetToken($token);

if (!$tokenData) {
    die("This validation token link is invalid or has expired. Please initiate another recovery cycle request.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');
    
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($newPassword) < 8) {
        setFlashMessage('Password must be at least 8 characters long.', 'error');
    } elseif ($newPassword !== $confirmPassword) {
        setFlashMessage('Passwords do not match.', 'error');
    } else {
        $email = $tokenData['email'];
        $accountType = $tokenData['account_type'];
        $table = ($accountType === 'admin') ? 'admins' : 'users';

        if (updatePasswordAndClearToken($email, $table, $newPassword, $token)) {
            setFlashMessage('Your credentials have been successfully updated. Please log in.', 'success');
            header('Location: index.php');
            exit;
        } else {
            setFlashMessage('An internal error occurred during processing.', 'error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Secure System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .dashboard-wrapper { width: 100%; max-width: 480px; padding: 20px; }
        .card { background: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 30px 24px; border-left: 5px solid #512da8; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        .form-group input { width: 100%; padding: 11px 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .action-btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; color: #fff; cursor: pointer; background-color: #512da8; }
        .error-message { background-color: #fce8e6; color: #c5221f; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border-left: 3px solid #d93025; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="card">
        <h2 style="margin-top:0;">Establish New Password</h2>
        <p style="color:#666; font-size:14px; margin-bottom:20px;">Updating security variables for associated profile: <strong><?= htmlspecialchars($tokenData['email']) ?></strong></p>

        <?php if (isset($_SESSION['message']) && $_SESSION['message_type'] === 'error'): ?>
            <div class="error-message"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" placeholder="Minimum 8 characters" required autofocus>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" class="action-btn">Update Password Credentials</button>
        </form>
    </div>
</div>
</body>
</html>