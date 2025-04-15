<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

// First, check if the columns exist and add them if they don't
$check_columns_query = "SHOW COLUMNS FROM orders LIKE 'updated_by'";
$result = $conn->query($check_columns_query);
if ($result->num_rows === 0) {
    // Add the required columns
    $alter_table_query = "ALTER TABLE orders 
        ADD COLUMN updated_by INT NULL,
        ADD COLUMN updated_at TIMESTAMP NULL,
        ADD FOREIGN KEY (updated_by) REFERENCES users(id)";
    $conn->query($alter_table_query);
}

// Handle status updates if any
if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $allowed_statuses = ['fulfilled', 'cancelled'];

    // Only require reason for cancellations
    if ($new_status === 'cancelled' && empty($reason)) {
        $_SESSION['error'] = "A reason is required when cancelling an order.";
        header('Location: manage_orders.php');
        exit();
    }

    if (in_array($new_status, $allowed_statuses)) {
        $update_stmt = $conn->prepare("
            UPDATE orders 
            SET status = ?, 
                fulfilled_at = CASE 
                    WHEN ? = 'fulfilled' THEN CURRENT_TIMESTAMP 
                    ELSE NULL 
                END,
                updated_by = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $admin_id = $_SESSION['user']['id'];
        $update_stmt->bind_param("ssii", $new_status, $new_status, $admin_id, $order_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Order status updated successfully!";
            
            // Get the admin ID from the admins table
            $admin_stmt = $conn->prepare("SELECT id FROM admins WHERE user_id = ?");
            $user_id = $_SESSION['user']['id'];
            $admin_stmt->bind_param("i", $user_id);
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            
            if ($admin_row = $admin_result->fetch_assoc()) {
                // Log the action in admin_logs table
                $log_stmt = $conn->prepare("
                    INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
                    VALUES (?, ?, 'orders', ?, ?)
                ");
                $action_type = 'UPDATE_ORDER_STATUS';
                $log_data = json_encode([
                    'new_status' => $new_status,
                    'reason' => $reason,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $admin_id = $admin_row['id'];
                $log_stmt->bind_param("isis", $admin_id, $action_type, $order_id, $log_data);
                $log_stmt->execute();
            }
        } else {
            $_SESSION['error'] = "Failed to update order status.";
        }
    }
    
    header('Location: manage_orders.php');
    exit();
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get order statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN o.status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_orders,
    SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(o.total_price) as total_revenue
FROM orders o
JOIN products p ON o.product_id = p.id
JOIN users s ON p.user_id = s.id
WHERE s.role = 'female_householder'";
$stats = $conn->query($stats_query)->fetch_assoc();

// Build the base query
$base_query = "
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users c ON o.consumer_id = c.id
    JOIN users s ON p.user_id = s.id
    WHERE s.role = 'female_householder'
";

// Add filters to query
$params = [];
$param_types = "";

if ($status_filter) {
    $base_query .= " AND o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($search) {
    $search_term = "%$search%";
    $base_query .= " AND (p.title LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total " . $base_query;
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Get orders for current page
$query = "
    SELECT 
        o.*,
        p.title as product_title,
        p.image as product_image,
        c.name as buyer_name,
        c.email as buyer_email,
        s.name as seller_name,
        s.email as seller_email
    " . $base_query . "
    ORDER BY o.ordered_at DESC
    LIMIT ? OFFSET ?
";

// Add pagination parameters to the existing params array
$params[] = $items_per_page;
$params[] = $offset;
$param_types .= "ii";

// Prepare and execute the statement with error handling
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    if (!$stmt->bind_param($param_types, ...$params)) {
        die("Binding parameters failed: " . $stmt->error);
    }
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Getting result set failed: " . $stmt->error);
}

$orders = $result->fetch_all(MYSQLI_ASSOC);

// Add product approval section
$pending_products = $conn->query("
    SELECT p.*, u.name as seller_name,
    CASE 
        WHEN p.approved = 1 AND p.is_approved = 1 THEN 'approved'
        WHEN p.approved = 2 OR p.is_approved = 2 THEN 'cancelled'
        ELSE 'pending'
    END as status
    FROM products p
    JOIN users u ON p.user_id = u.id
    WHERE u.role = 'female_householder'
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Handle product approval/cancellation
if (isset($_POST['action']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $user_id = $_SESSION['user']['id'];
    
    // Get admin ID from admins table
    $admin_stmt = $conn->prepare("SELECT id FROM admins WHERE user_id = ?");
    $admin_stmt->bind_param("i", $user_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    
    if ($admin_row = $admin_result->fetch_assoc()) {
        $admin_id = $admin_row['id'];
        $action = $_POST['action'];
        
        if ($action === 'approve_product') {
            $status_value = 1;
            $action_type = 'APPROVE';
            $success_message = "Product approved successfully!";
        } else if ($action === 'cancel_product') {
            $status_value = 2;
            $action_type = 'CANCEL';
            $success_message = "Product cancelled successfully!";
        }
        
        $stmt = $conn->prepare("
            UPDATE products 
            SET approved = ?,
                is_approved = ?,
                approved_by = ?, 
                approved_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->bind_param("iiii", $status_value, $status_value, $admin_id, $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = $success_message;
            
            // Log the action
            $log_stmt = $conn->prepare("
                INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
                VALUES (?, ?, 'products', ?, ?)
            ");
            $log_data = json_encode([
                'status' => $action_type,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $log_stmt->bind_param("isis", $admin_id, $action_type, $product_id, $log_data);
            $log_stmt->execute();
        } else {
            $_SESSION['error'] = "Failed to update product status.";
        }
    } else {
        $_SESSION['error'] = "Admin record not found.";
    }
    
    header('Location: manage_orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-image: url('../../assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            margin-bottom: 20px;
        }

        .card-header h3 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .card-header h3 i {
            color: #007B5E;
        }

        .card-body {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        .table th {
            background: rgba(0, 123, 94, 0.1);
            color: #2c3e50;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0, 123, 94, 0.1);
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
        }

        .status-approved {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #007B5E;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-danger {
            background: #dc3545;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
        }

        .alert-success {
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-error {
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert-info {
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }

        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-box {
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid rgba(0, 123, 94, 0.2);
            border-radius: 8px;
            font-size: 0.95rem;
            background: #fff;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #007B5E;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid rgba(0, 123, 94, 0.2);
            border-radius: 8px;
            font-size: 0.95rem;
            background: #fff;
            min-width: 150px;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #007B5E;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #fff;
            color: #007B5E;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .back-link:hover {
            background: rgba(0, 123, 94, 0.1);
            color: #007B5E;
            transform: translateY(-2px);
        }

        .header {
            margin-bottom: 25px;
        }

        .header h2 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
        }

        .header h2 i {
            color: #007B5E;
        }

        .transparency-box {
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #007B5E;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .transparency-box h4 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .transparency-box p {
            margin: 8px 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .transparency-box i {
            color: #007B5E;
            width: 20px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease;
            border: 1px solid rgba(0, 123, 94, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 123, 94, 0.1);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: #007B5E;
            margin: 10px 0;
        }

        .stat-label {
            color: #2c3e50;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 123, 94, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 123, 94, 0.1);
        }

        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
        }

        .product-info {
            display: flex;
            gap: 15px;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .user-info h4 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 1rem;
        }

        .user-info p {
            margin: 5px 0;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .fulfill-btn {
            background: #28a745;
            color: white;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
        }

        .fulfill-btn:hover, .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .confirmation-dialog {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            z-index: 1001;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .action-reason {
            margin: 20px 0;
        }

        .action-reason label {
            display: block;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 500;
        }

        .action-reason textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 123, 94, 0.2);
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }

            .filters form {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-shopping-cart"></i> Manage Orders</h3>
            </div>

            <!-- Add transparency info box -->
            <div class="transparency-box">
                <h4>Orders Management Overview</h4>
                <p><i class="fas fa-female"></i> Showing orders from female householder products only</p>
                <p><i class="fas fa-info-circle"></i> Monitor order status and details</p>
                <p><i class="fas fa-history"></i> Track order history and updates</p>
                <p><i class="fas fa-shield-alt"></i> Ensure transparency in order management</p>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Fulfilled Orders</div>
                    <div class="stat-value"><?php echo $stats['fulfilled_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Cancelled Orders</div>
                    <div class="stat-value"><?php echo $stats['cancelled_orders']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                </div>
            </div>

            <div class="filters">
                <form method="GET" style="width: 100%; display: flex; gap: 15px; flex-wrap: wrap;">
                    <div class="filter-group search-box">
                        <input type="text" name="search" placeholder="Search by product, buyer, or seller..." 
                               value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    </div>
                    <div class="filter-group">
                        <select name="status" class="filter-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="fulfilled" <?php echo $status_filter === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Add product approval section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-box"></i> Product Management</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_products)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-check-double"></i> No products to display.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_products as $product): ?>
                                        <tr>
                                            <td>
                                                <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                     alt="Product Image" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                                            <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                                    <?php 
                                                    switch($product['status']) {
                                                        case 'approved':
                                                            echo '<i class="fas fa-check-circle"></i> Approved';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<i class="fas fa-times-circle"></i> Cancelled';
                                                            break;
                                                        default:
                                                            echo '<i class="fas fa-clock"></i> Pending';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($product['status'] === 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="approve_product">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this product?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline; margin-left: 5px;">
                                                        <input type="hidden" name="action" value="cancel_product">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this product?')">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No orders found.
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span style="font-size: 1.1rem; font-weight: 600; color: #2c3e50;">
                                    Order #<?php echo $order['id']; ?>
                                </span>
                                <span style="margin-left: 15px; color: #6c757d;">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d M Y, h:i A', strtotime($order['ordered_at'])); ?>
                                </span>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <i class="fas fa-<?php echo $order['status'] === 'fulfilled' ? 'check-circle' : ($order['status'] === 'cancelled' ? 'times-circle' : 'clock'); ?>"></i>
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="product-info">
                                <img src="../../assets/uploads/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['product_title']); ?>"
                                     class="product-image">
                                <div>
                                    <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($order['product_title']); ?></h4>
                                    <p style="margin: 0;">Quantity: <?php echo $order['quantity']; ?></p>
                                    <p style="margin: 5px 0;">Total: ₹<?php echo number_format($order['total_price'], 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="user-info">
                                <h4>Buyer Details</h4>
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['buyer_email']); ?></p>
                            </div>
                            
                            <div class="user-info">
                                <h4>Seller Details</h4>
                                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['seller_name']); ?></p>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['seller_email']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include('../../includes/footer.php'); ?>
    <div class="overlay" id="overlay"></div>
    <div class="confirmation-dialog" id="confirmationDialog">
        <h3>Update Order Status</h3>
        <p id="confirmationMessage"></p>
        <div class="action-reason" id="reasonContainer">
            <label for="actionReason">Reason for Cancellation (required):</label>
            <textarea id="actionReason" rows="3" placeholder="Please provide a detailed reason for cancelling this order"></textarea>
        </div>
        <div class="action-buttons">
            <button onclick="cancelAction()" class="btn" style="background: #6c757d;">Cancel</button>
            <button onclick="proceedAction()" class="btn" style="background: #28a745;">Update Status</button>
        </div>
    </div>
    <script>
    let currentForm = null;
    let currentStatus = null;

    function confirmStatusChange(form, orderId, newStatus, orderDetails) {
        currentForm = form;
        currentStatus = newStatus;
        const dialog = document.getElementById('confirmationDialog');
        const overlay = document.getElementById('overlay');
        const message = document.getElementById('confirmationMessage');
        const reasonContainer = document.getElementById('reasonContainer');
        
        message.innerHTML = `
            <strong>Order #${orderId}</strong><br>
            Change status to: <strong>${newStatus}</strong><br>
            <small>
                Product: ${orderDetails.product}<br>
                Buyer: ${orderDetails.buyer}<br>
                Current Status: ${orderDetails.currentStatus}
            </small>
        `;
        
        // Only show reason field for cancellations
        reasonContainer.style.display = newStatus === 'cancelled' ? 'block' : 'none';
        
        dialog.style.display = 'block';
        overlay.style.display = 'block';
        
        return false;
    }

    function cancelAction() {
        const dialog = document.getElementById('confirmationDialog');
        const overlay = document.getElementById('overlay');
        const reason = document.getElementById('actionReason');
        
        dialog.style.display = 'none';
        overlay.style.display = 'none';
        reason.value = '';
        currentForm = null;
        currentStatus = null;
    }

    function proceedAction() {
        const reason = document.getElementById('actionReason');
        
        // Only validate reason for cancellations
        if (currentStatus === 'cancelled' && !reason.value.trim()) {
            alert('Please provide a reason for cancelling this order.');
            return;
        }
        
        // Add reason to form only for cancellations
        if (currentStatus === 'cancelled') {
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason.value;
            currentForm.appendChild(reasonInput);
        }
        
        // Submit the form
        currentForm.submit();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const dialog = document.getElementById('confirmationDialog');
        const overlay = document.getElementById('overlay');
        if (event.target === overlay) {
            dialog.style.display = 'none';
            overlay.style.display = 'none';
        }
    }
    </script>
</body>
</html> 