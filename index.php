<?php
require_once 'functions.php';

// Check if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard_Admin.php');
    } else {
        header('Location: dashboard_User.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $accountType = $_POST['account_type'] ?? 'user';

    $loginError = null;

    if (empty($email) || empty($password)) {
        $loginError = 'Please fill in all fields.';
    } elseif (empty($accountType)) {
        $loginError = 'Please select an account type.';
    } else {
        $table = ($accountType === 'admin') ? 'admins' : 'users';
        $account = getUserByEmail($email, $table);

        if (!$account) {
            $loginError = "No account found with that email for " . ucfirst($accountType);
        } elseif (!password_verify($password, $account['password'])) {
            $loginError = "Incorrect password for " . ucfirst($accountType);
        } else {
            // Initiate OTP authentication tracking parameters
            $_SESSION['temp_user_id'] = $account['id'];
            $_SESSION['temp_username'] = $account['username'];
            $_SESSION['temp_email'] = $account['email'];
            $_SESSION['temp_role'] = $accountType;

            // Generate 6-digit OTP
            $otp = sprintf('%06d', mt_rand(1, 999999));
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_time'] = time();

            // 1. Keep your original fallback process: Write to log file
            $otpMessage = date('Y-m-d H:i:s') . " - OTP for " . $account['username'] . " (" . $account['email'] . "): " . $otp . PHP_EOL;
            file_put_contents('otp.txt', $otpMessage, FILE_APPEND);

            // 2. NEW PROCESS: Instantly deliver the OTP directly to their inbox
            $emailSent = sendOTPEmail($account['email'], $account['username'], $otp);

            if ($emailSent) {
                setFlashMessage('A secure verification code has been dispatched to your email address.', 'success');
            } else {
                // If SMTP delivery completely fails, they can check otp.txt in development env
                setFlashMessage('Verification code generated. (Email dispatch failed, fallback system active)', 'warning');
            }

            header("Location: otp.php");
            exit;
        }
    }

    if ($loginError) {
        setFlashMessage($loginError, 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Secure System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">
</head>

<body>

    <div class="dashboard-wrapper">

        <!-- Left Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">AMU</div>
            </div>

            <div class="sidebar-content">
                <img src="https://i.pinimg.com/736x/83/56/55/835655dfe773e2ef81d1878777d24962.jpg"
                    alt="Network Security" class="sidebar-image">
                <h2 class="sidebar-tagline">Capturing Moments, Creating Memories</h2>
            </div>

            <div class="sidebar-footer">
                <a href="#" class="back-link">Back to website</a>
            </div>
        </div>

        <!-- Right Form Area -->
        <div class="dashboard-header">
            <div class="dashboard-header-content">
                <h1>Welcome Back</h1>
                <p>Please enter your credentials to log in.</p>
            </div>

            <div class="card login-card">

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert-message alert-<?= htmlspecialchars($_SESSION['message_type'] ?? 'success') ?>">
                        <?= htmlspecialchars($_SESSION['message']) ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Enter your email" required
                                autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="Enter your password"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="account_type">Login As</label>
                            <select name="account_type" id="account_type" required>
                                <option value="user">Standard User</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <button type="submit" class="action-btn">Login</button>
                    </form>

                    <div class="footer-text">
                        Don't have an account? <a href="register.php">Register</a>
                        <br>
                        Forgot password? <a href="forgot_password.php">Reset it here</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div id="chocolateBar" class="chocolate-bar hidden"></div>

    <?php if (isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof showChocolateBar === 'function') {
                    showChocolateBar(
                        <?= json_encode($_SESSION['message']) ?>,
                        <?= json_encode($_SESSION['message_type'] ?? 'success') ?>
                    );
                }
            });
        </script>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif;
    ?>

    <script src="main.js"></script>
</body>

</html>