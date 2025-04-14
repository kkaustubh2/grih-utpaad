<?php
require_once('../../includes/auth.php');

// Additional role check for admin
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

// Check if registration_date column exists and add it if it doesn't
$check_column_query = "SHOW COLUMNS FROM users LIKE 'registration_date'";
$result = $conn->query($check_column_query);
if ($result->num_rows === 0) {
    // Add registration_date column with current timestamp for existing users
    $alter_table_query = "ALTER TABLE users 
        ADD COLUMN registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $conn->query($alter_table_query);
}

// Get total users count
$users_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'consumer' THEN 1 ELSE 0 END) as total_consumers,
    SUM(CASE WHEN role = 'female_householder' THEN 1 ELSE 0 END) as total_sellers
FROM users";
$users_stats = $conn->query($users_query)->fetch_assoc();

// Get orders statistics
$orders_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_orders,
    SUM(total_price) as total_revenue
FROM orders";
$orders_stats = $conn->query($orders_query)->fetch_assoc();

// Get products statistics
$products_query = "SELECT COUNT(*) as total_products FROM products";
$products_stats = $conn->query($products_query)->fetch_assoc();

// Get recent orders
$recent_orders_query = "
    SELECT o.*, 
           p.title as product_title,
           c.name as buyer_name,
           s.name as seller_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users c ON o.consumer_id = c.id
    JOIN users s ON p.user_id = s.id
    ORDER BY o.ordered_at DESC
    LIMIT 5";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);

// Get recent users
$recent_users_query = "
    SELECT id, name, email, role, registration_date
    FROM users
    ORDER BY registration_date DESC
    LIMIT 5";
$recent_users = $conn->query($recent_users_query)->fetch_all(MYSQLI_ASSOC);

// Check if admin_logs table exists and create if it doesn't
$table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        details TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )";
    $conn->query($create_table_sql);
}

// Add admin activity logging with error handling
function logAdminActivity($admin_id, $action_type, $details) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action_type, details) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $admin_id, $action_type, $details);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silently fail if there's an issue with logging
        error_log("Failed to log admin activity: " . $e->getMessage());
    }
}

// System status monitoring with error handling
$system_status = [
    'database' => $conn->ping(),
    'disk_space' => @disk_free_space("/") !== false ? (disk_free_space("/") / disk_total_space("/") * 100) : 0,
    'last_backup' => @filemtime('../../backup') ? date("Y-m-d H:i:s", filemtime('../../backup')) : 'No backup found',
    'php_version' => PHP_VERSION
];

// Enhanced statistics queries with error handling
try {
    $enhanced_stats_query = "SELECT 
        COUNT(DISTINCT o.consumer_id) as unique_customers,
        COALESCE(AVG(o.total_price), 0) as average_order_value,
        COUNT(DISTINCT p.category_id) as active_categories,
        (SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_products_week
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id";
    $enhanced_stats = $conn->query($enhanced_stats_query)->fetch_assoc();
} catch (Exception $e) {
    $enhanced_stats = [
        'unique_customers' => 0,
        'average_order_value' => 0,
        'active_categories' => 0,
        'new_products_week' => 0
    ];
    error_log("Failed to fetch enhanced stats: " . $e->getMessage());
}

// Get recent admin activities with error handling
try {
    $recent_activities_query = "
        SELECT al.*, u.name as admin_name 
        FROM admin_logs al
        JOIN users u ON al.admin_id = u.id
        ORDER BY al.timestamp DESC
        LIMIT 5";
    $recent_activities = $conn->query($recent_activities_query)->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $recent_activities = [];
    error_log("Failed to fetch recent activities: " . $e->getMessage());
}
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
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.9) 0%, rgba(233, 245, 241, 0.9) 100%),
                url('../../assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .dashboard-header {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
            padding: 30px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .welcome-text {
            font-size: 1.2rem;
            color: #6c757d;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
            transition: transform 0.3s ease;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            color: #007B5E;
            margin-bottom: 15px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dashboard-card h3 {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .action-card {
            background: rgba(248, 249, 250, 0.9);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c3e50;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .action-card:hover {
            background: rgba(0, 123, 94, 0.9);
            color: white;
            transform: translateY(-3px);
        }
        .action-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .recent-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-fulfilled {
            background-color: #28a745;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .role-admin {
            background-color: #007B5E;
            color: white;
        }
        .role-consumer {
            background-color: #17a2b8;
            color: white;
        }
        .role-seller {
            background-color: #6f42c1;
            color: white;
        }
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
        .system-status {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(249, 250, 251, 0.95) 100%);
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .status-item {
            padding: 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
        }
        .status-item i {
            font-size: 1.5rem;
            color: #007B5E;
        }
        .status-item.healthy i {
            color: #28a745;
        }
        .status-item.warning i {
            color: #ffc107;
        }
        .status-text {
            font-weight: 500;
            font-size: 0.9rem;
        }
        .activity-timeline {
            margin-top: 20px;
        }
        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 123, 94, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007B5E;
        }
        .activity-content {
            flex: 1;
        }
        .activity-content p {
            margin: 5px 0;
            color: #6c757d;
        }
        .activity-content small {
            color: #adb5bd;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h2>Admin Dashboard</h2>
                <p class="welcome-text">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</p>
            </div>
            <a href="../../logout.php" class="btn" style="background-color: #dc3545;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="quick-actions">
            <a href="manage_orders.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h4>Manage Orders</h4>
                <p><?php echo $orders_stats['pending_orders']; ?> pending orders</p>
            </a>
            <a href="manage_users.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4>Manage Users</h4>
                <p><?php echo $users_stats['total_users']; ?> total users</p>
            </a>
            <a href="manage_categories.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h4>Manage Categories</h4>
                <p>Organize products</p>
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $users_stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
                <div style="margin-top: 10px; font-size: 0.9rem; color: #6c757d;">
                    <?php echo $users_stats['total_consumers']; ?> Consumers<br>
                    <?php echo $users_stats['total_sellers']; ?> Sellers
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value"><?php echo $orders_stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
                <div style="margin-top: 10px; font-size: 0.9rem; color: #6c757d;">
                    <?php echo $orders_stats['pending_orders']; ?> Pending<br>
                    <?php echo $orders_stats['fulfilled_orders']; ?> Fulfilled
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-value"><?php echo $products_stats['total_products']; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-value">₹<?php echo number_format($orders_stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">₹<?php echo number_format($enhanced_stats['average_order_value'], 2); ?></div>
                <div class="stat-label">Avg. Order Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?php echo $enhanced_stats['unique_customers']; ?></div>
                <div class="stat-label">Unique Customers</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                <?php if (empty($recent_orders)): ?>
                    <p>No recent orders found.</p>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="recent-item">
                            <div>
                                <strong><?php echo htmlspecialchars($order['product_title']); ?></strong><br>
                                <small>
                                    By <?php echo htmlspecialchars($order['buyer_name']); ?> •
                                    ₹<?php echo number_format($order['total_price'], 2); ?>
                                </small>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 15px;">
                        <a href="manage_orders.php" class="btn">View All Orders</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-user-clock"></i> Recent Users</h3>
                <?php if (empty($recent_users)): ?>
                    <p>No recent users found.</p>
                <?php else: ?>
                    <?php foreach ($recent_users as $user): ?>
                        <div class="recent-item">
                            <div>
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                <small>
                                    <?php echo htmlspecialchars($user['email']); ?> •
                                    <?php echo date('d M Y', strtotime($user['registration_date'])); ?>
                                </small>
                            </div>
                            <span class="role-badge role-<?php echo $user['role'] === 'female_householder' ? 'seller' : $user['role']; ?>">
                                <?php echo $user['role'] === 'female_householder' ? 'Seller' : ucfirst($user['role']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 15px;">
                        <a href="manage_users.php" class="btn">View All Users</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card system-status">
                <h3><i class="fas fa-server"></i> System Status</h3>
                <div class="status-grid">
                    <div class="status-item <?php echo $system_status['database'] ? 'healthy' : 'warning'; ?>">
                        <i class="fas fa-database"></i>
                        <span>Database Connection</span>
                        <span class="status-text"><?php echo $system_status['database'] ? 'Connected' : 'Issue Detected'; ?></span>
                    </div>
                    <div class="status-item <?php echo $system_status['disk_space'] > 20 ? 'healthy' : 'warning'; ?>">
                        <i class="fas fa-hdd"></i>
                        <span>Disk Space</span>
                        <span class="status-text"><?php echo number_format($system_status['disk_space'], 1); ?>% free</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-clock"></i>
                        <span>Last Backup</span>
                        <span class="status-text"><?php echo $system_status['last_backup']; ?></span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-code"></i>
                        <span>PHP Version</span>
                        <span class="status-text"><?php echo $system_status['php_version']; ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-history"></i> Recent Admin Activities</h3>
                <?php if (empty($recent_activities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history fa-3x"></i>
                        <p>No recent activities found. New activities will appear here as administrators perform actions.</p>
                    </div>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['action_type']); ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($activity['admin_name']); ?></strong>
                                    <p><?php echo htmlspecialchars($activity['details']); ?></p>
                                    <small><?php echo timeAgo($activity['timestamp']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include('../../includes/footer.php'); ?>

</body>
</html>

<?php
function getActivityIcon($action_type) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'create' => 'plus-circle',
        'update' => 'edit',
        'delete' => 'trash-alt',
        'view' => 'eye'
    ];
    return $icons[$action_type] ?? 'circle';
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>
