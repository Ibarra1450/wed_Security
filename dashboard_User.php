<?php
require_once 'functions.php';
requireRole('user');

$userId = $_SESSION['user_id'];

// Handle form submissions (copied from settings.php for self-contained actions)
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

        header('Location: dashboard_User.php');
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

        header('Location: dashboard_User.php');
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
    <title>User Dashboard - WEBSECURE Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="dashboard-user.css">
</head>

<body>
    <div class="portal-wrapper">

        <!-- Main Content Panel: Clean Off-White -->
        <div class="portal-main">

            <!-- Top Header bar -->
            <div class="main-header">
                <h1>User Dashboard</h1>
                <a href="logout.php" class="header-logout-btn">Logout</a>
            </div>

            <!-- Top Grid Layout: 2 Panels -->
            <div class="main-grid">

                <!-- Panel 1: Profile Details -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h2>PROFILE DETAILS</h2>
                    </div>
                    <div class="panel-content">
                        <div class="info-row">
                            <span class="info-label">First Name:</span>
                            <span class="info-value"><?= htmlspecialchars($user['first_name'] ?? 'Not Set') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Name:</span>
                            <span class="info-value"><?= htmlspecialchars($user['last_name'] ?? 'Not Set') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Middle Initial:</span>
                            <span class="info-value"><?= htmlspecialchars($user['middle_initial'] ?? 'None') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email Address:</span>
                            <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Since:</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Account Role:</span>
                            <span class="info-value">Standard User</span>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: Account Configuration Settings -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h2>ACCOUNT CONFIGURATION</h2>
                    </div>
                    <div class="panel-content">
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
                                <label>Email Address</label>
                                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>

                            <button type="submit" name="update_settings" class="btn btn-teal">
                                Save Profile Settings
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Bottom Grid Layout: Password Update Panel -->
            <div class="main-grid mt-4">

                <!-- Panel 3: Update Password Securely (Spans full width for balanced layout) -->
                <div class="panel-card span-2">
                    <div class="panel-header">
                        <h2> UPDATE ACCOUNT PASSWORD</h2>
                    </div>
                    <div class="panel-content">
                        <form method="POST">
                            <?php if (function_exists('csrfField'))
                                echo csrfField(); ?>

                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label>New Password (10-23 characters)</label>
                                <input type="password" name="new_password" minlength="10" maxlength="23" required>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" minlength="10" maxlength="23" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-teal">
                                Update Password Securely
                            </button>
                        </form>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Session Feedback Bar -->
    <div id="chocolateBar" class="chocolate-bar hidden"></div>
    <?php if (isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof showChocolateBar === 'function') {
                    showChocolateBar(<?= json_encode($_SESSION['message']) ?>, <?= json_encode($_SESSION['message_type'] ?? 'success') ?>);
                } else {
                    alert(<?= json_encode($_SESSION['message']) ?>);
                }
            });
        </script>
        <?php unset($_SESSION['message']);
        unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    <script src="main.js"></script>
</body>

</html>