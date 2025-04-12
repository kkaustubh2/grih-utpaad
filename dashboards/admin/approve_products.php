<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once('../../config/db.php');

// Handle product approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $action = $_POST['action'];
    $admin_id = $conn->query("SELECT id FROM admins WHERE user_id = {$_SESSION['user']['id']}")->fetch_assoc()['id'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("
            UPDATE products 
            SET approved = 1, 
                approved_by = ?, 
                approved_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $admin_id, $product_id);
        $stmt->execute();

        // Log the approval
        $log_stmt = $conn->prepare("
            INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
            VALUES (?, 'APPROVE', 'products', ?, ?)
        ");
        $log_data = json_encode(['approved' => true, 'approved_at' => date('Y-m-d H:i:s')]);
        $log_stmt->bind_param("iis", $admin_id, $product_id, $log_data);
        $log_stmt->execute();
    }
}

// Fetch pending products
$products = $conn->query("
    SELECT p.*, u.name as seller_name 
    FROM products p
    JOIN users u ON p.user_id = u.id
    WHERE p.approved = 0
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Products - Admin Dashboard</title>
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
        .container {
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
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-info {
            padding: 20px;
        }
        .product-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin: 0 0 10px;
        }
        .product-seller {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
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
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #007B5E;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
        }
        .no-products {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .no-products i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #007B5E;
        }
        .transparency-box {
            background: rgba(248, 249, 250, 0.9);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007B5E;
        }
        .transparency-box h4 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .transparency-box p {
            margin: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .transparency-box i {
            color: #007B5E;
            font-size: 1.1rem;
        }
        .approval-guide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .guide-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            color: #666;
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
                <h1>
                    <i class="fas fa-check-circle"></i>
                    Approve Products
                </h1>
            </div>

            <!-- Add transparency info box -->
            <div class="transparency-box">
                <h4>Product Approval Guidelines:</h4>
                <div class="approval-guide">
                    <div class="guide-item">
                        <i class="fas fa-image"></i>
                        <span>Check image quality and clarity</span>
                    </div>
                    <div class="guide-item">
                        <i class="fas fa-tag"></i>
                        <span>Verify price and category</span>
                    </div>
                    <div class="guide-item">
                        <i class="fas fa-align-left"></i>
                        <span>Review description accuracy</span>
                    </div>
                    <div class="guide-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Ensure product safety</span>
                    </div>
                </div>
                <p><i class="fas fa-info-circle"></i> Products must meet community guidelines and quality standards</p>
                <p><i class="fas fa-bell"></i> Sellers will be notified of approval decisions</p>
                <p><i class="fas fa-exclamation-triangle"></i> Rejected products can be resubmitted after improvements</p>
            </div>

            <?php if (empty($products)): ?>
                <div class="no-products">
                    <i class="fas fa-check-double"></i>
                    <h2>All Caught Up!</h2>
                    <p>There are no products pending approval.</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="../../assets/uploads/<?php echo $product['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 class="product-image">
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <div class="product-seller">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($product['seller_name']); ?>
                                </div>
                                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                                </p>
                                <form method="POST" class="action-buttons">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn-approve">
                                        <i class="fas fa-check"></i> Approve Product
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include('../../includes/footer.php'); ?>
</body>
</html> 