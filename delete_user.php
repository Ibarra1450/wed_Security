<?php
require_once 'functions.php';
requireRole('admin');

$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'user';

// Prevent deleting yourself
if ($id == $_SESSION['user_id'] && $type === $_SESSION['role']) {
    setFlashMessage('You cannot delete your own account.', 'error');
    header('Location: dashboard_Admin.php');
    exit;
}

$table = ($type === 'admin') ? 'admins' : 'users';

$stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
if ($stmt->execute([$id])) {
    logActivity($_SESSION['user_id'], $_SESSION['role'], "deleted_$type:$id");
    setFlashMessage(ucfirst($type) . ' deleted successfully!', 'success');
} else {
    setFlashMessage('Delete failed.', 'error');
}

header('Location: dashboard_Admin.php');
exit;
?>