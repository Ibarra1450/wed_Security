<?php
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $value = sanitizeInput($_POST['value'] ?? '');
    $accountType = $_POST['account_type'] ?? 'user';
    
    if (empty($value) || empty($type)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    $table = ($accountType === 'admin') ? 'admins' : 'users';
    
    if ($type === 'email') {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
    } else {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    $stmt->execute([$value]);
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);
    exit;
}
?>