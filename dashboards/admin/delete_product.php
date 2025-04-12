<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied.");
}

$id = intval($_GET['id']);
$success = false;
$error = '';

// Get product details before deletion
$product = $conn->query("SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = $id")->fetch_assoc();

if ($product) {
    // Optional: delete image file too (if stored)
    if (!empty($product['image'])) {
        @unlink("../../uploads/" . $product['image']);
    }

    // Log the deletion
    $admin_id = $conn->query("SELECT id FROM admins WHERE user_id = {$_SESSION['user']['id']}")->fetch_assoc()['id'];
    $log_stmt = $conn->prepare("
        INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
        VALUES (?, 'DELETE_PRODUCT', 'products', ?, ?)
    ");
    $log_data = json_encode([
        'product_name' => $product['title'],
        'seller' => $product['seller_name'],
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    $log_stmt->bind_param("iis", $admin_id, $id, $log_data);
    $log_stmt->execute();

    // Delete the product
    if ($conn->query("DELETE FROM products WHERE id = $id")) {
        $success = true;
    } else {
        $error = "Failed to delete product. Please try again.";
    }
} else {
    $error = "Product not found.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Product - Admin Dashboard</title>
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
            max-width: 600px;
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
            text-align: center;
        }
        .transparency-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007B5E;
            text-align: left;
        }
        .transparency-box h4 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .transparency-box p {
            margin: 5px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .transparency-box i {
            color: #007B5E;
        }
        .status-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .success-icon {
            color: #28a745;
        }
        .error-icon {
            color: #dc3545;
        }
        .message {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-wrapper">
            <?php if ($success): ?>
                <i class="fas fa-check-circle status-icon success-icon"></i>
                <div class="message">Product deleted successfully!</div>
                
                <!-- Add transparency info box -->
                <div class="transparency-box">
                    <h4>Deletion Summary:</h4>
                    <p><i class="fas fa-info-circle"></i> Product: <?= htmlspecialchars($product['title']) ?></p>
                    <p><i class="fas fa-user"></i> Seller: <?= htmlspecialchars($product['seller_name']) ?></p>
                    <p><i class="fas fa-clock"></i> Deleted at: <?= date('d M Y, h:i A') ?></p>
                    <p><i class="fas fa-bell"></i> The seller will be notified of this action</p>
                </div>
            <?php else: ?>
                <i class="fas fa-exclamation-circle status-icon error-icon"></i>
                <div class="message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <a href="products.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>
