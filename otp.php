<?php
require_once 'functions.php';

// If already fully logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard_Admin.php');
    } else {
        header('Location: dashboard_User.php');
    }
    exit;
}

// Check if we have temporary session variables for OTP
if (!isset($_SESSION['temp_user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = implode('', $_POST['otp'] ?? []);
    
    if (isset($_SESSION['otp']) && $entered_otp === $_SESSION['otp']) {
        // Check if OTP is expired (e.g., 5 minutes)
        if (time() - $_SESSION['otp_time'] > 300) {
            setFlashMessage('OTP has expired. Please log in again.', 'error');
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['otp']);
            header('Location: index.php');
            exit;
        }

        // OTP is correct
        $_SESSION['user_id'] = $_SESSION['temp_user_id'];
        $_SESSION['username'] = $_SESSION['temp_username'];
        $_SESSION['email'] = $_SESSION['temp_email'];
        $_SESSION['role'] = $_SESSION['temp_role'];
        $_SESSION['last_activity'] = time();

        $accountType = $_SESSION['temp_role'];
        logActivity($_SESSION['temp_user_id'], $accountType, 'login');

        // Cleanup temp session
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_username']);
        unset($_SESSION['temp_email']);
        unset($_SESSION['temp_role']);
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);



        $redirect = ($accountType === 'admin') ? 'dashboard_Admin.php' : 'dashboard_User.php';
        header("Location: $redirect");
        exit;
    } else {
        setFlashMessage('Invalid OTP code. Please try again.', 'error');
    }
}

if (isset($_GET['resend'])) {
    $otp = sprintf('%06d', mt_rand(1, 999999));
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();
    
    // Fallback file generation logic
    $otpMessage = date('Y-m-d H:i:s') . " - [RESEND] OTP for " . $_SESSION['temp_username'] . " (" . $_SESSION['temp_email'] . "): " . $otp . PHP_EOL;
    file_put_contents('otp.txt', $otpMessage, FILE_APPEND);
    
    // Primary mail delivery call routine
    if (function_exists('sendOTPEmail')) {
        sendOTPEmail($_SESSION['temp_email'], $_SESSION['temp_username'], $otp);
    }
    
    setFlashMessage('A fresh verification code has been dispatched to your email inbox.', 'success');
    header('Location: otp.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Secure System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .dashboard-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 20px;
            box-sizing: border-box;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .dashboard-header h1 {
            font-size: 26px;
            color: #1a1a1a;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .dashboard-header p {
            color: #666;
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 35px 24px 30px 24px;
            margin-bottom: 20px;
            box-sizing: border-box;
            border-left: 5px solid #512da8; /* Consistent purple branding indicator */
            text-align: center;
        }

        .security-info {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background-color: #f1f3f9;
            color: #4527a0;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .security-info svg {
            stroke: #512da8;
        }

        /* Six-Box OTP Grid Formatting Structure */
        .otp-inputs-grid {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin: 25px 0 30px 0;
        }

        .otp-box {
            width: 50px;
            height: 56px;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            color: #1a1a1a;
            background-color: #fff;
            box-sizing: border-box;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .otp-box:focus {
            border-color: #512da8;
            box-shadow: 0 0 0 3px rgba(81, 45, 168, 0.15);
            outline: none;
        }

        .action-btn {
            display: block;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: background 0.2s ease;
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
            background-color: #512da8;
        }

        .action-btn:hover {
            background-color: #4527a0;
        }

        .footer-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
            font-size: 14px;
        }

        .footer-navigation a {
            color: #512da8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .footer-navigation a:hover {
            color: #311b92;
            text-decoration: underline;
        }

        .footer-navigation .divider {
            color: #cbd5e1;
            font-weight: 400;
        }

        .error-message {
            background-color: #fce8e6;
            color: #c5221f;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid #d93025;
            text-align: left;
        }

        .success-message {
            background-color: #e6f4ea;
            color: #137333;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid #188038;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <h1>Identity Verification</h1>
        <p>To secure your account workspace, please enter the 6-digit dynamic authentication token issued to your registered address.</p>
    </div>

    <div class="card">
        <div class="security-info">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <span>2FA Protected Pipeline</span>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="<?= ($_SESSION['message_type'] === 'error') ? 'error-message' : 'success-message' ?> UI-message-pane">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php if($_SESSION['message_type'] === 'success'): ?>
            <script>
                setTimeout(() => {
                    const pane = document.querySelector('.UI-message-pane');
                    if(pane) pane.style.display = 'none';
                }, 5000);
            </script>
            <?php endif; ?>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="otp-inputs-grid">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required autofocus>
                <input type="text" name="otp[]" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="otp[]" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="otp[]" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="otp[]" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
                <input type="text" name="otp[]" class="otp-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            </div>

            <button type="submit" class="action-btn">Verify Identity</button>
        </form>

        <div class="footer-navigation">
            <a href="logout.php">Cancel Authorization</a>
            <span class="divider">•</span>
            <a href="otp.php?resend=1">Resend Token</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('.otp-box');

    inputs.forEach((input, index) => {
        // Automatically switch focus forward upon typing
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Move focus backward on backspace execution
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        // Comprehensive multi-box paste engine functionality
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 6).replace(/[^0-9]/g, '');
            for (let i = 0; i < pastedData.length; i++) {
                if (inputs[i]) {
                    inputs[i].value = pastedData[i];
                    if (i < inputs.length - 1) {
                        inputs[i + 1].focus();
                    }
                }
            }
        });
    });
});
</script>
</body>
</html>