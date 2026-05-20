<?php
require_once 'functions.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Only verify CSRF if helper exists
    if (function_exists('verifyCSRF')) {
        verifyCSRF();
    }

    // Update user settings
    if (isset($_POST['update_settings'])) {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName  = sanitizeInput($_POST['last_name'] ?? '');
        $middleInitial = strtoupper(sanitizeInput($_POST['middle_initial'] ?? ''));

        $errors = [];
        if (empty($firstName)) {
            $errors[] = 'First Name cannot be empty.';
        }
        if (empty($lastName)) {
            $errors[] = 'Last Name cannot be empty.';
        }

        if (empty($errors)) {
            $update = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, middle_initial = ? WHERE id = ?");
            $update->execute([$firstName, $lastName, $middleInitial, $userId]);

            $_SESSION['message'] = 'Profile settings updated successfully.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = implode('<br>', $errors);
            $_SESSION['message_type'] = 'error';
        }

        header('Location: settings.php');
        exit;
    }

    // Change password
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();

        // Password policy checks
        $passwordLength = strlen($newPassword);
        $hasSpecialChar = preg_match('/[^A-Za-z0-9]/', $newPassword);

        if (!password_verify($currentPassword, $userData['password'])) {
            $_SESSION['message'] = 'Current password is incorrect.';
            $_SESSION['message_type'] = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['message'] = 'New passwords do not match.';
            $_SESSION['message_type'] = 'error';
        } elseif ($passwordLength < 10 || $passwordLength > 23) {
            $_SESSION['message'] = 'Password must be between 10 and 23 characters long.';
            $_SESSION['message_type'] = 'error';
        } elseif (!$hasSpecialChar) {
            $_SESSION['message'] = 'Password must include at least one special character.';
            $_SESSION['message_type'] = 'error';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashedPassword, $userId]);

            $_SESSION['message'] = 'Password changed successfully.';
            $_SESSION['message_type'] = 'success';
        }

        header('Location: settings.php');
        exit;
    }
}

// Get latest user data
$stmt = $pdo->prepare("SELECT email, first_name, last_name, middle_initial, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            font-family: 'Outfit', -apple-system, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            background-image: radial-gradient(circle at 10% 20%, rgba(124, 58, 237, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(124, 58, 237, 0.03) 0%, transparent 40%);
        }

        .dashboard-wrapper {
            width: 100%;
            max-width: 600px;
            padding: 40px 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 10px;
            position: relative;
        }

        .dashboard-header h1 {
            font-size: 26px;
            color: #ffffff;
            margin: 0 0 6px 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .dashboard-header p {
            color: #b0b0b0;
            margin: 0;
            font-size: 14px;
        }

        /* Card container styled exactly like the dashboard */
        .card {
            background-color: #1a1a1a;
            border: 1px solid #2d2d2d;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            padding: 24px;
            box-sizing: border-box;
        }

        /* Specific card colors based on your action groupings */
        .card.profile-card {
            border-left: 4px solid #7c3aed;
        }

        /* Purple */
        .card.settings-card {
            border-left: 4px solid #3b82f6;
        }

        /* Blue */
        .card.password-card {
            border-left: 4px solid #ef4444;
        }

        /* Red */

        .card h2 {
            font-size: 16px;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: -0.2px;
        }

        /* Grid data rows */
        .profile-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #2d2d2d;
        }

        .profile-item:last-child {
            border-bottom: none;
        }

        .profile-label {
            color: #b0b0b0;
            font-weight: 500;
            font-size: 13px;
        }

        .profile-value {
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }

        .role-badge {
            background: rgba(139, 92, 246, 0.15);
            color: #c084fc;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 100px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        /* Elegant form handling */
        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #b0b0b0;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #2d2d2d;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            background-color: #222222;
            color: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input:focus {
            border-color: #7c3aed;
            outline: none;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }

        .form-group input:disabled {
            background-color: #1a1a1a;
            color: #666666;
            cursor: not-allowed;
            border-color: #262626;
        }

        /* Custom Buttons aligned with dashboard UI colors */
        .action-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: center;
            text-decoration: none;
            box-sizing: border-box;
        }

        .action-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }

        .action-primary:hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(124, 58, 237, 0.3);
        }

        .back-btn {
            background-color: #222222;
            color: #b0b0b0;
            border: 1px solid #2d2d2d;
            margin-top: 10px;
        }

        .back-btn:hover {
            background-color: #2d2d2d;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>

    <div class="dashboard-wrapper">

        <div class="dashboard-header">
            <h1>Account Settings</h1>
            <p>This is your personal configuration dashboard.</p>
        </div>

        <div class="card profile-card">
            <h2>Your Profile Information</h2>

            <div class="profile-item">
                <span class="profile-label">First Name:</span>
                <span class="profile-value"><?= htmlspecialchars($user['first_name'] ?? 'Not Set') ?></span>
            </div>

            <div class="profile-item">
                <span class="profile-label">Last Name:</span>
                <span class="profile-value"><?= htmlspecialchars($user['last_name'] ?? 'Not Set') ?></span>
            </div>

            <div class="profile-item">
                <span class="profile-label">Middle Initial:</span>
                <span class="profile-value"><?= htmlspecialchars($user['middle_initial'] ?? 'None') ?></span>
            </div>

            <div class="profile-item">
                <span class="profile-label">Email:</span>
                <span class="profile-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>

            <div class="profile-item">
                <span class="profile-label">Member Since:</span>
                <span class="profile-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
            </div>

            <div class="profile-item">
                <span class="profile-label">Role:</span>
                <span class="profile-value"><span class="role-badge">Standard User</span></span>
            </div>
        </div>

        <div class="card settings-card">
            <h2>Update Settings</h2>

            <form method="POST">
                <?php if (function_exists('csrfField'))
                    echo csrfField(); ?>

                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Middle Initial</label>
                    <input type="text" name="middle_initial" value="<?= htmlspecialchars($user['middle_initial'] ?? '') ?>" maxlength="2">
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label>ttings
                </button>
            </form>
        </div>

        <div class="card password-card">
            <h2> Change Password</h2>

            <form method="POST">
                <?php if (function_exists('csrfField'))
                    echo csrfField(); ?>

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>New Password (10-23 characters, 1+ special char)</label>
                    <input type="password" name="new_password" minlength="10" maxlength="23" required>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" minlength="10" maxlength="23" required>
                </div>

                <button type="submit" name="change_password" class="action-btn action-primary">
                    Update Password
                </button>
            </form>
        </div>

        <a href="dashboard_User.php" class="action-btn back-btn">← Back to Dashboard</a>

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
                } else {
                    alert(<?= json_encode($_SESSION['message']) ?>);
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