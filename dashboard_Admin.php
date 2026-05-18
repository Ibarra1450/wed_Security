<?php
require_once 'functions.php';
requireRole('admin');

// Get all users and admins for management
$users = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$admins = $pdo->query("SELECT id, username, email, created_at FROM admins ORDER BY created_at DESC")->fetchAll();

// Get statistics
$stats = [
    'total_users' => count($users),
    'total_admins' => count($admins),
    'new_users_today' => $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'new_admins_today' => $pdo->query("SELECT COUNT(*) FROM admins WHERE DATE(created_at) = CURDATE()")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dashboard-admin.css">
</head>
<body>
    <div class="container" style="max-width: 1200px;">
        <h2>Admin Dashboard</h2>
        <p>Welcome, Administrator <?= htmlspecialchars($_SESSION['username']) ?>!</p>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_admins'] ?></div>
                <div class="stat-label">Total Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['new_users_today'] ?></div>
                <div class="stat-label">New Users Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['new_admins_today'] ?></div>
                <div class="stat-label">New Admins Today</div>
            </div>
        </div>
        
        <!-- Tabs for User Management -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('users')">Users</button>
            <button class="tab" onclick="showTab('admins')">Admins</button>
            <button class="tab" onclick="showTab('reports')">Reports</button>
        </div>
        
        <!-- Users Tab -->
        <div id="users-tab" class="tab-content active">
            <h3>User Management</h3>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="editUser(<?= $user['id'] ?>, 'user')">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteUser(<?= $user['id'] ?>, 'user')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Admins Tab -->
        <div id="admins-tab" class="tab-content">
            <h3>Admin Management</h3>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= $admin['id'] ?></td>
                        <td><?= htmlspecialchars($admin['username']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td><?= date('Y-m-d', strtotime($admin['created_at'])) ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="editUser(<?= $admin['id'] ?>, 'admin')">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteUser(<?= $admin['id'] ?>, 'admin')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Reports Tab -->
        <div id="reports-tab" class="tab-content">
            <h3>System Reports</h3>
            <div class="dashboard-stats">
                <h4>Activity Log</h4>
                <?php
                if (file_exists('activity.log')) {
                    $logs = file('activity.log', FILE_IGNORE_NEW_LINES);
                    $logs = array_slice($logs, -20); // Show last 20 entries
                    echo '<pre>' . implode("\n", array_reverse($logs)) . '</pre>';
                } else {
                    echo '<p>No activity logs available.</p>';
                }
                ?>
            </div>
        </div>
        
        <a href="logout.php" class="logout-btn">Logout</a>
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
    
    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }
    
    function editUser(id, type) {
        if (confirm('Edit user ' + id + ' (' + type + ')?')) {
            window.location.href = 'edit_user.php?id=' + id + '&type=' + type;
        }
    }
    
    function deleteUser(id, type) {
        if (confirm('Are you sure you want to delete this ' + type + '? This action cannot be undone.')) {
            window.location.href = 'delete_user.php?id=' + id + '&type=' + type;
        }
    }
    </script>
    <script src="main.js"></script>
</body>
</html>