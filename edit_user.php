<?php
require_once 'functions.php';
requireRole('admin');

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');
    
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $table = ($type === 'admin') ? 'admins' : 'users';
    
    // Validation
    $errors = [];
    
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } elseif (!preg_match('/@ntc\.edu\.ph$/', $email)) {
        $errors[] = 'Only @ntc.edu.ph email addresses are allowed.';
    }
    
    // Check if username or email already exists (excluding current user)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE username = ? AND id != ?");
        if ($stmt->execute([$username, $id]) && $stmt->fetch()) {
            $errors[] = 'This username is already taken.';
        }
        
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ? AND id != ?");
        if ($stmt->execute([$email, $id]) && $stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE $table SET username = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $id])) {
            setFlashMessage(ucfirst($type) . ' updated successfully!', 'success');
            header('Location: dashboard_Admin.php');
            exit;
        } else {
            $errors[] = 'Update failed. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['message'] = implode('<br>', $errors);
        $_SESSION['message_type'] = 'error';
    }
}

// Get user data
$table = ($type === 'admin') ? 'admins' : 'users';
$stmt = $pdo->prepare("SELECT username, email FROM $table WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('User not found.', 'error');
    header('Location: dashboard_Admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Edit <?= ucfirst($type) ?></h2>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" 
                       value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" 
                       value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <button type="submit">Update</button>
            <a href="dashboard_Admin.php" class="button">Cancel</a>
        </form>
    </div>

    <div id="chocolateBar" class="chocolate-bar hidden"></div>
    <?php if (isset($_SESSION['message'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        showChocolateBar(<?= json_encode($_SESSION['message']) ?>, <?= json_encode($_SESSION['message_type'] ?? 'success') ?>);
    });
    </script>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
    <script src="main.js"></script>
</body>
</html>