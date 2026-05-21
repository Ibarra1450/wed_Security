<?php
require_once 'functions.php';
requireRole('admin');

// Get all users and admins for management
$users = $pdo->query("SELECT id, email, first_name, last_name, middle_initial, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$admins = $pdo->query("SELECT id, email, first_name, last_name, middle_initial, created_at FROM admins ORDER BY created_at DESC")->fetchAll();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WEBSECURE Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard-admin.css">
</head>
<body>
    <div class="portal-wrapper">
        
        <!-- Sidebar Navigation -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h3 class="sidebar-title">ADMIN PORTAL</h3>
            </div>
            
            <div class="sidebar-welcome">
                <p>Welcome,<br><strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>
                <span class="admin-badge">ADMIN</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="showTab('dashboard'); return false;">
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="#" class="nav-item" onclick="showTab('users'); return false;">
                    <span class="nav-label">User Management</span>
                </a>
                <a href="#" class="nav-item" onclick="showTab('admins'); return false;">
                    <span class="nav-label">Admin Management</span>
                </a>
                <a href="#" class="nav-item" onclick="showTab('reports'); return false;">
                    <span class="nav-label">System Reports</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link">
                    <span class="nav-label">Logout</span>
                </a>
            </div>
        </div>
                <!-- Main Content -->
        <div class="portal-main">
            
            <!-- Dashboard View -->
            <div id="dashboard-tab" class="tab-content active">
                <!-- Top Header -->
                <div class="main-header">
                    <h1>Admin Dashboard</h1>
                </div>

                <!-- Admin Profile Section - FIRST -->
                <div class="admin-profile-section">
                    <div class="panel-card">
                        <div class="panel-header">
                            <h2>ADMIN PROFILE</h2>
                        </div>
                        <div class="panel-content">
                            <div class="info-row">
                                <span class="info-label">Admin Name:</span>
                                <span class="info-value"><?= htmlspecialchars($_SESSION['name'] ?? 'Not Set') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email Address:</span>
                                <span class="info-value"><?= htmlspecialchars($_SESSION['email'] ?? 'Not Set') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Role:</span>
                                <span class="info-value">Administrator</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account Status:</span>
                                <span class="info-value"><span class="status-badge active">Active</span></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Users Managed:</span>
                                <span class="info-value"><?= $stats['total_users'] ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Admins:</span>
                                <span class="info-value"><?= $stats['total_admins'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Section -->
                <div class="main-content-section">
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

                    <!-- Quick Statistics Panel -->
                    <div class="main-grid">
                        <div class="panel-card">
                            <div class="panel-header">
                                <h2>QUICK STATISTICS</h2>
                            </div>
                            <div class="panel-content">
                                <div class="info-row">
                                    <span class="info-label">New Users Today:</span>
                                    <span class="info-value stat-highlight"><?= $stats['new_users_today'] ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">New Admins Today:</span>
                                    <span class="info-value stat-highlight"><?= $stats['new_admins_today'] ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">System Health:</span>
                                    <span class="info-value"><span class="health-badge good">Optimal</span></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Database Status:</span>
                                    <span class="info-value"><span class="health-badge good">Connected</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div id="users-tab" class="tab-content">
                <div class="main-header">
                    <h1>User Management</h1>
                    <a href="logout.php" class="header-logout-btn">Logout</a>
                </div>
                
                <div class="panel-card">
                    <div class="panel-header">
                        <h2>ALL USERS</h2>
                        <span class="record-count"><?= count($users) ?> Records</span>
                    </div>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <?php
                                    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_initial'] ? $user['middle_initial'] . ' ' : '') . ($user['last_name'] ?? ''));
                                    echo htmlspecialchars($fullName ?: 'Not Set');
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" onclick="editUser(<?= $user['id'] ?>, 'user')">Edit</button>
                                        <button class="action-btn delete-btn" onclick="deleteUser(<?= $user['id'] ?>, 'user')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Admins Tab -->
            <div id="admins-tab" class="tab-content">
                <div class="main-header">
                    <h1>Admin Management</h1>
                    <a href="logout.php" class="header-logout-btn">Logout</a>
                </div>
                
                <div class="panel-card">
                    <div class="panel-header">
                        <h2>ALL ADMINS</h2>
                        <span class="record-count"><?= count($admins) ?> Records</span>
                    </div>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= $admin['id'] ?></td>
                                <td>
                                    <?php
                                    $fullName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['middle_initial'] ? $admin['middle_initial'] . ' ' : '') . ($admin['last_name'] ?? ''));
                                    echo htmlspecialchars($fullName ?: 'Not Set');
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td><?= date('Y-m-d', strtotime($admin['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" onclick="editUser(<?= $admin['id'] ?>, 'admin')">Edit</button>
                                        <button class="action-btn delete-btn" onclick="deleteUser(<?= $admin['id'] ?>, 'admin')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Reports Tab -->
            <div id="reports-tab" class="tab-content">
                <div class="main-header">
                    <h1>System Reports</h1>
                    <a href="logout.php" class="header-logout-btn">Logout</a>
                </div>
                
                <div class="panel-card">
                    <div class="panel-header">
                        <h2>ACTIVITY LOG</h2>
                    </div>
                    <div class="activity-log">
                        <?php
                        if (file_exists('activity.log')) {
                            $logs = file('activity.log', FILE_IGNORE_NEW_LINES);
                            $logs = array_slice($logs, -20); // Show last 20 entries
                            if (!empty($logs)) {
                                echo '<pre>' . htmlspecialchars(implode("\n", array_reverse($logs))) . '</pre>';
                            } else {
                                echo '<p class="no-data">No activity logs found.</p>';
                            }
                        } else {
                            echo '<p class="no-data">No activity logs available.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>

        </div>
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
        
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Show selected tab
        const tabElement = document.getElementById(tabName + '-tab');
        if (tabElement) {
            tabElement.classList.add('active');
        }
        
        // Add active class to clicked nav item
        event.target.closest('.nav-item').classList.add('active');
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