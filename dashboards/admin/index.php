<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once('../../config/db.php');

// Fetch enhanced stats
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'pending_approval' => $conn->query("SELECT COUNT(*) as count FROM products WHERE approved = 0")->fetch_assoc()['count'],
    'total_orders' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'pending_orders' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'],
    'total_householders' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'female_householder'")->fetch_assoc()['count']
];

// Fetch recent admin logs
$logs = $conn->query("
    SELECT al.*, a.user_id, u.name as admin_name 
    FROM admin_logs al
    JOIN admins a ON al.admin_id = a.id
    JOIN users u ON a.user_id = u.id
    ORDER BY al.performed_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f5f1 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .content-wrapper {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.1);
            padding: 30px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .dashboard-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            color: #007B5E;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 2rem;
        }
        .stat-card p {
            color: #6c757d;
            margin: 5px 0 0;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: #007B5E;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .logout-btn {
            background: #dc3545;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .recent-activity {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .recent-activity h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #e9f5f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007B5E;
        }
        .activity-details {
            flex: 1;
        }
        .activity-details p {
            margin: 0;
            color: #6c757d;
        }
        .activity-details strong {
            color: #2c3e50;
        }
        .activity-time {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .admin-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #007B5E;
            color: white;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="content-wrapper">
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-user-shield"></i>
                    Admin Dashboard
                </h1>
                <div>
                    <span style="color: #6c757d; margin-right: 10px;">
                        Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                        <?php if ($_SESSION['user']['is_superadmin']): ?>
                            <span class="admin-badge">Super Admin</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-home"></i>
                    <h3><?php echo $stats['total_householders']; ?></h3>
                    <p>Female Householders</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <h3><?php echo $stats['total_products']; ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $stats['pending_approval']; ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3><?php echo $stats['total_orders']; ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hourglass-half"></i>
                    <h3><?php echo $stats['pending_orders']; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>

            <div class="action-buttons">
                <a href="manage_users.php" class="action-btn">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a>
                <a href="manage_categories.php" class="action-btn">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="approve_products.php" class="action-btn">
                    <i class="fas fa-check-circle"></i> Approve Products
                </a>
                <a href="manage_orders.php" class="action-btn">
                    <i class="fas fa-shopping-basket"></i> Manage Orders
                </a>
                <?php if ($_SESSION['user']['is_superadmin']): ?>
                    <a href="manage_admins.php" class="action-btn">
                        <i class="fas fa-user-shield"></i> Manage Admins
                    </a>
                <?php endif; ?>
                <a href="../../auth/logout.php" class="action-btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <div class="recent-activity">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <ul class="activity-list">
                    <?php foreach ($logs as $log): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <?php
                                $icon = 'history';
                                switch ($log['action']) {
                                    case 'LOGIN': $icon = 'sign-in-alt'; break;
                                    case 'UPDATE': $icon = 'edit'; break;
                                    case 'DELETE': $icon = 'trash-alt'; break;
                                    case 'CREATE': $icon = 'plus'; break;
                                }
                                ?>
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <p>
                                    <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong>
                                    <?php echo htmlspecialchars($log['action']); ?> in
                                    <?php echo htmlspecialchars($log['table_affected']); ?>
                                </p>
                                <span class="activity-time">
                                    <?php echo date('M d, Y h:i A', strtotime($log['performed_at'])); ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 