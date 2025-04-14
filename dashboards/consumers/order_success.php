<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch order details with product and seller information
$stmt = $conn->prepare("
    SELECT o.*, p.title as product_title, p.price as unit_price, p.image,
           u.name as seller_name, u.phone as seller_phone
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE o.id = ? AND o.consumer_id = ?
");

$stmt->bind_param("ii", $order_id, $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: view_product.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmed - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f5f1 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #2c3e50;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .success-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }
        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .success-title {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 10px;
        }
        .success-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .order-details {
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .order-number {
            font-size: 1.2rem;
            color: #007B5E;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .product-info {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-details {
            flex: 1;
        }
        .product-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .price-details {
            display: grid;
            grid-template-columns: auto auto;
            gap: 10px;
            margin-bottom: 20px;
        }
        .price-label {
            color: #6c757d;
        }
        .price-value {
            text-align: right;
            font-weight: 500;
        }
        .total-price {
            font-size: 1.2rem;
            color: #007B5E;
            font-weight: 600;
        }
        .shipping-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        .info-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary {
            background: #007B5E;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
            background: #fff3cd;
            color: #856404;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .success-card {
                padding: 20px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .product-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .price-details {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .price-value {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-header">
                <i class="fas fa-check-circle success-icon"></i>
                <h1 class="success-title">Order Confirmed!</h1>
                <p class="success-message">Thank you for your order. We'll notify you once it's ready.</p>
            </div>

            <div class="order-details">
                <div class="order-number">
                    <i class="fas fa-shopping-bag"></i> Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                </div>

                <div class="status-badge">
                    <i class="fas fa-clock"></i> <?php echo ucfirst($order['status']); ?>
                </div>

                <div class="product-info">
                    <img src="../../assets/uploads/<?php echo htmlspecialchars($order['image']); ?>" 
                         alt="<?php echo htmlspecialchars($order['product_title']); ?>"
                         class="product-image">
                    <div class="product-details">
                        <h3 class="product-title"><?php echo htmlspecialchars($order['product_title']); ?></h3>
                        <div class="price-details">
                            <span class="price-label">Unit Price:</span>
                            <span class="price-value">₹<?php echo number_format($order['unit_price'], 2); ?></span>
                            
                            <span class="price-label">Quantity:</span>
                            <span class="price-value"><?php echo $order['quantity']; ?></span>
                            
                            <span class="price-label">Total Amount:</span>
                            <span class="price-value total-price">₹<?php echo number_format($order['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="shipping-info">
                    <h4 class="info-title"><i class="fas fa-map-marker-alt"></i> Shipping Address</h4>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>

                <div class="shipping-info">
                    <h4 class="info-title"><i class="fas fa-store"></i> Seller Information</h4>
                    <p><?php echo htmlspecialchars($order['seller_name']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['seller_phone']); ?></p>
                </div>
            </div>

            <div class="action-buttons">
                <a href="my_orders.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View My Orders
                </a>
                <a href="view_product.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</body>
</html>
