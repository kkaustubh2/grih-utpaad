<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

// Get cart count for header
$cart_count_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE consumer_id = ?");
$cart_count_query->bind_param("i", $_SESSION['user']['id']);
$cart_count_query->execute();
$cart_count = $cart_count_query->get_result()->fetch_assoc()['count'];

// Verify order exists and belongs to current user
if (!isset($_GET['id'])) {
    header('Location: view_product.php');
    exit();
}

$order_id = intval($_GET['id']);
$order_query = $conn->prepare("
    SELECT o.*, oi.quantity, p.title, p.price, p.image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.consumer_id = ?
");
$order_query->bind_param("ii", $order_id, $_SESSION['user']['id']);
$order_query->execute();
$result = $order_query->get_result();

if ($result->num_rows === 0) {
    header('Location: view_product.php');
    exit();
}

$order_items = [];
$total = 0;
while ($item = $result->fetch_assoc()) {
    $order = $item; // Save order details
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $order_items[] = $item;
    $total += $item['subtotal'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Success - Grih Utpaad</title>
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
            padding: 20px;
        }
        .success-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .success-icon {
            color: #28a745;
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .order-details {
            margin-top: 30px;
            text-align: left;
        }
        .product-list {
            margin: 20px 0;
        }
        .product-item {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr;
            gap: 20px;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .product-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #2c3e50;
        }
        .quantity {
            color: #666;
        }
        .price {
            color: #007B5E;
            font-weight: 600;
        }
        .total {
            text-align: right;
            font-size: 1.3rem;
            color: #2c3e50;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .shipping-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007B5E;
            color: white;
        }
        .btn-primary:hover {
            background: #005b46;
            transform: translateY(-2px);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-name {
            font-weight: 500;
            color: #2c3e50;
        }
        .cart-btn {
            background: #007B5E;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .cart-count {
            background: white;
            color: #007B5E;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <i class="fas fa-user-circle fa-2x" style="color: #007B5E;"></i>
                <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
            </div>
            <a href="view_cart.php" class="cart-btn">
                <i class="fas fa-shopping-cart"></i>
                Cart
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="success-card">
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your order. Your order ID is: #<?php echo $order_id; ?></p>

            <div class="order-details">
                <h2>Order Summary</h2>
                <div class="product-list">
                    <?php foreach ($order_items as $item): ?>
                        <div class="product-item">
                            <img src="../../assets/uploads/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['title']) ?>" 
                                 class="product-image">
                            
                            <div class="product-info">
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                            </div>

                            <div class="quantity">
                                Quantity: <?= $item['quantity'] ?>
                            </div>

                            <div class="price">
                                ₹<?= number_format($item['subtotal'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total">
                    Total Amount: ₹<?= number_format($total, 2) ?>
                </div>

                <div class="shipping-info">
                    <h3>Shipping Details</h3>
                    <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                </div>
            </div>

            <a href="view_product.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    </div>
</body>
</html>
