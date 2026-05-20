<?php
require_once 'functions.php';
requireRole('admin');

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');
    
    $email = sanitizeInput($_POST['email']);
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName  = sanitizeInput($_POST['last_name'] ?? '');
    $middleInitial = strtoupper(sanitizeInput($_POST['middle_initial'] ?? ''));
    $table = ($type === 'admin') ? 'admins' : 'users';
    
    // Validation
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = 'First Name is required.';
    }
    if (empty($lastName)) {
        $errors[] = 'Last Name is required.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    } elseif (!preg_match('/@ntc\.edu\.ph$/', $email)) {
        $errors[] = 'Only @ntc.edu.ph email addresses are allowed.';
    }
    
    // Check if email already exists (excluding current user)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ? AND id != ?");
        if ($stmt->execute([$email, $id]) && $stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE $table SET email = ?, first_name = ?, last_name = ?, middle_initial = ? WHERE id = ?");
        if ($stmt->execute([$email, $firstName, $lastName, $middleInitial, $id])) {
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
$stmt = $pdo->prepare("SELECT email, first_name, last_name, middle_initial FROM $table WHERE id = ?");
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
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" 
                       value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" 
                       value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="middle_initial">Middle Initial</label>
                <input type="text" name="middle_initial" id="middle_initial" 
                       value="<?= htmlspecialchars($user['middle_initial'] ?? '') ?>" maxlength="2">
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