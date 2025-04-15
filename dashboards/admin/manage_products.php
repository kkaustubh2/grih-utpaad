<?php
require_once('../../includes/auth.php');

// Additional role check for admin
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

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
    }
    
    header('Location: manage_products.php');
    exit();
}

// Fetch all products with their details
$products_query = "
    SELECT p.*, u.name as seller_name, pc.name as category_name,
    CASE 
        WHEN p.approved = 1 AND p.is_approved = 1 THEN 'approved'
        WHEN p.approved = 2 OR p.is_approved = 2 THEN 'cancelled'
        ELSE 'pending'
    END as status
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE u.role = 'female_householder'
    ORDER BY p.created_at DESC";
$products = $conn->query($products_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products - Admin Dashboard</title>
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
        .content-wrapper {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header h2 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            transform: translateX(-5px);
            color: #005b46;
        }
        .transparency-box {
            background: rgba(248, 249, 250, 0.9);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #007B5E;
        }
        .transparency-box h4 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .transparency-box p {
            margin: 10px 0;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .transparency-box i {
            color: #007B5E;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image-container {
            width: 100%;
            height: 300px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-image {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
        }
        .product-info {
            padding: 20px;
        }
        .product-category {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 123, 94, 0.1);
            color: #007B5E;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .product-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .product-seller {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .product-price {
            font-size: 1.3rem;
            color: #007B5E;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .product-description {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #007B5E;
            opacity: 0.5;
        }
        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-wrapper">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <div class="header">
                <h2><i class="fas fa-box"></i> Manage Products</h2>
            </div>

            <div class="transparency-box">
                <h4>Product Management Guidelines:</h4>
                <p><i class="fas fa-female"></i> Showing products from female householders only</p>
                <p><i class="fas fa-image"></i> Check product images for quality and appropriateness</p>
                <p><i class="fas fa-tag"></i> Verify product pricing and categorization</p>
                <p><i class="fas fa-align-left"></i> Review product descriptions for accuracy</p>
                <p><i class="fas fa-shield-alt"></i> Ensure products meet community guidelines</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($products->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>There are currently no products to review.</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>"
                                     class="product-image">
                            </div>
                            
                            <div class="product-info">
                                <?php if ($product['category_name']): ?>
                                    <span class="product-category">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                                    </span>
                                <?php endif; ?>

                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                
                                <div class="product-seller">
                                    <i class="fas fa-store"></i>
                                    <?php echo htmlspecialchars($product['seller_name']); ?>
                                </div>

                                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>

                                <div class="product-description">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                                </div>

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

                                <?php if ($product['status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="action" value="approve_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-approve" onclick="return confirm('Are you sure you want to approve this product?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="flex: 1;">
                                            <input type="hidden" name="action" value="cancel_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel this product?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html> 