<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard-header {
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
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
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
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c3e50;
        }
        .action-card:hover {
            background: #007B5E;
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
        </div>
    </div>
</body>
</html>
