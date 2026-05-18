<?php
require_once 'functions.php';

// Log the logout activity if user was logged in
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], $_SESSION['role'], 'logout');
}

// Destroy the session
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
setFlashMessage('You have been successfully logged out.', 'success');
header('Location: index.php');
exit;
?>