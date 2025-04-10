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
    $allowed_statuses = ['fulfilled', 'cancelled'];

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
        } else {
            $_SESSION['error'] = "Failed to update order status.";
        }
    }
    
    // Redirect to prevent form resubmission
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

// Build the base query
$base_query = "
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users c ON o.consumer_id = c.id
    JOIN users s ON p.user_id = s.id
    WHERE 1=1
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

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $params[] = $items_per_page;
    $params[] = $offset;
    $param_types .= "ii";
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get order statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(total_price) as total_revenue
FROM orders";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-box {
            flex: 1;
            min-width: 200px;
        }
        .status-filter {
            min-width: 150px;
        }
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
        .user-info h4 {
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .user-info p {
            margin: 5px 0;
            color: #6c757d;
        }
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .fulfill-btn {
            background-color: #28a745;
            color: white;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-shopping-cart"></i> Manage Orders</h2>
                <a href="dashboard.php" class="btn" style="background-color: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="stats-grid">
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
                               value="<?php echo htmlspecialchars($search); ?>" style="margin: 0;">
                    </div>
                    <div class="filter-group">
                        <select name="status" class="status-filter" style="margin: 0;">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="fulfilled" <?php echo $status_filter === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn">
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

                            <?php if ($order['status'] === 'pending'): ?>
                                <div style="text-align: right;">
                                    <div class="order-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="new_status" value="fulfilled">
                                            <button type="submit" class="action-btn fulfill-btn" 
                                                    onclick="return confirm('Are you sure you want to mark this order as fulfilled?');">
                                                <i class="fas fa-check"></i> Fulfill Order
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="new_status" value="cancelled">
                                            <button type="submit" class="action-btn cancel-btn"
                                                    onclick="return confirm('Are you sure you want to cancel this order?');">
                                                <i class="fas fa-times"></i> Cancel Order
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
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
</body>
</html> 