<?php
session_start();

// Check if user is logged in and is a female householder
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'female_householder') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

$user_id = $_SESSION['user']['id'];

// Handle status updates if any
if (isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['fulfilled', 'cancelled'];

    if (in_array($new_status, $allowed_statuses)) {
        // Verify the order belongs to one of this user's products
        $stmt = $conn->prepare("
            SELECT o.id 
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            WHERE o.id = ? AND p.user_id = ?
        ");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Update the order status
            $update_stmt = $conn->prepare("
                UPDATE orders 
                SET status = ?, 
                    fulfilled_at = CASE 
                        WHEN ? = 'fulfilled' THEN CURRENT_TIMESTAMP 
                        ELSE NULL 
                    END,
                    updated_at = CURRENT_TIMESTAMP,
                    updated_by = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssii", $new_status, $new_status, $user_id, $order_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Order status updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update order status.";
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: orders.php');
    exit();
}

// Get all orders for the user's products with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get total number of orders
$count_stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE p.user_id = ?
");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Get orders for current page
$stmt = $conn->prepare("
    SELECT 
        o.*,
        p.title as product_title,
        p.image as product_image,
        u.name as buyer_name,
        u.email as buyer_email
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users u ON o.consumer_id = u.id
    WHERE p.user_id = ? 
    ORDER BY o.ordered_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $items_per_page, $offset);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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
        .order-id {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-details h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        .buyer-info {
            color: #6c757d;
        }
        .order-status {
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
        .order-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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
            transition: all 0.2s ease;
        }
        .fulfill-btn {
            background-color: #28a745;
            color: white;
        }
        .fulfill-btn:hover {
            background-color: #218838;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #c82333;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        .page-link {
            padding: 8px 16px;
            border-radius: 6px;
            background-color: white;
            color: #007B5E;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .page-link:hover {
            background-color: #007B5E;
            color: white;
        }
        .page-link.active {
            background-color: #007B5E;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                            <div class="order-id">
                                Order #<?php echo $order['id']; ?>
                            </div>
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d M Y, h:i A', strtotime($order['ordered_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="product-info">
                                <img src="../../assets/uploads/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['product_title']); ?>"
                                     class="product-image">
                                <div class="product-details">
                                    <h4><?php echo htmlspecialchars($order['product_title']); ?></h4>
                                    <div>Quantity: <?php echo $order['quantity']; ?></div>
                                    <div>Total: ₹<?php echo number_format($order['total_price'], 2); ?></div>
                                </div>
                            </div>
                            
                            <div class="buyer-info">
                                <h4>Buyer Details</h4>
                                <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['buyer_name']); ?></div>
                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['buyer_email']); ?></div>
                            </div>
                            
                            <div style="text-align: right;">
                                <div style="margin-bottom: 10px;">
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <i class="fas fa-<?php echo $order['status'] === 'fulfilled' ? 'check-circle' : ($order['status'] === 'cancelled' ? 'times-circle' : 'clock'); ?>"></i>
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($order['status'] === 'pending'): ?>
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html> 