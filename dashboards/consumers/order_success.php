<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: view_product.php');
    exit();
}

$order_id = intval($_GET['id']);

// Fetch orders with the same shipping address and phone (grouped order)
$query = "SELECT o.*, p.title, p.image, u.name as seller_name
          FROM orders o
          JOIN products p ON o.product_id = p.id
          JOIN users u ON p.user_id = u.id
          WHERE o.consumer_id = ? 
          AND o.shipping_address = (SELECT shipping_address FROM orders WHERE id = ?)
          AND o.phone = (SELECT phone FROM orders WHERE id = ?)
          AND o.ordered_at = (SELECT ordered_at FROM orders WHERE id = ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $_SESSION['user']['id'], $order_id, $order_id, $order_id);
$stmt->execute();
$result = $stmt->get_result();

// If no order found, redirect
if ($result->num_rows === 0) {
    header('Location: view_product.php');
    exit();
}

// Calculate total
$total = 0;
$order_items = [];
while ($item = $result->fetch_assoc()) {
    $total += $item['total_price'];
    $order_items[] = $item;
}

// Get the first item for order details (they'll all have the same order info)
$order = $order_items[0];
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
            margin: 0 auto;
            padding: 20px;
        }

        .success-container {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-message {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 30px;
        }

        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 10px;
        }

        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .items-list {
            margin-top: 30px;
        }

        .item {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr;
            gap: 20px;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
        }

        .item-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .seller-info {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .quantity {
            color: #2c3e50;
        }

        .price {
            color: #007B5E;
            font-weight: 600;
        }

        .total {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            font-size: 1.2rem;
            color: #2c3e50;
        }

        .total span {
            color: #007B5E;
            font-weight: 600;
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007B5E;
            color: white;
        }

        .btn-primary:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .order-info {
                grid-template-columns: 1fr;
            }

            .item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 10px;
            }

            .item-image {
                margin: 0 auto;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="success-message">Order Placed Successfully!</h1>
            
            <div class="order-details">
                <div class="order-info">
                    <div class="info-item">
                        <div class="info-label">Order ID</div>
                        <div class="info-value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?= date('F j, Y', strtotime($order['ordered_at'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Shipping Address</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= htmlspecialchars($order['phone']) ?></div>
                    </div>
                </div>

                <div class="items-list">
                    <h2>Order Items</h2>
                    <?php foreach ($order_items as $item): ?>
                        <div class="item">
                            <img src="../../assets/uploads/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['title']) ?>" 
                                 class="item-image">
                            
                            <div class="item-info">
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <div class="seller-info">
                                    Sold by: <?= htmlspecialchars($item['seller_name']) ?>
                                </div>
                            </div>

                            <div class="quantity">
                                Quantity: <?= $item['quantity'] ?>
                            </div>

                            <div class="price">
                                ₹<?= number_format($item['total_price'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total">
                        Total Amount: <span>₹<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="view_product.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                <a href="my_orders.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> View My Orders
                </a>
            </div>
        </div>
    </div>
</body>
</html>
