<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

$consumer_id = $_SESSION['user']['id'];

// Fetch consumer's orders
$stmt = $conn->prepare("
    SELECT o.*, p.title AS product_title, p.image AS product_image
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.consumer_id = ?
    ORDER BY o.ordered_at DESC
");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
            display: flex;
            flex-direction: column;
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

        .main-wrapper {
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .content-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .page-header {
            text-align: center;
            margin: 20px 0 40px;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .page-header p {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #6c757d;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 8px 16px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-link:hover {
            color: #007B5E;
            transform: translateX(-5px);
            text-decoration: none;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }

        .orders-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .orders-table tr:hover {
            background-color: #f8f9fa;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .product-title {
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .price {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 30px 0;
        }

        .no-orders i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .no-orders p {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 10px 0;
        }

        .no-orders .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #007B5E;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .no-orders .btn:hover {
            background: #005b46;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: 20px;
            }

            .content-wrapper {
                padding: 20px;
            }

            .product-info {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .product-image {
                margin: 0 auto;
            }

            .status-badge {
                display: inline-flex;
                margin: 5px 0;
            }

            .orders-table {
                display: block;
                overflow-x: auto;
            }

            .page-header h1 {
                font-size: 1.75rem;
            }

            .page-header p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="orders-container">
            <div class="content-wrapper">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
                
                <div class="page-header">
                    <h1>
                        <i class="fas fa-shopping-bag" style="color: #007B5E;"></i>
                        My Orders
                    </h1>
                    <p>Track and manage your orders</p>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Order Date</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <img src="../../assets/uploads/<?php echo htmlspecialchars($order['product_image']); ?>"
                                                    alt="<?php echo htmlspecialchars($order['product_title']); ?>"
                                                    class="product-image">
                                                <h3 class="product-title"><?php echo htmlspecialchars($order['product_title']); ?></h3>
                                            </div>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['ordered_at'])); ?></td>
                                        <td><?php echo $order['quantity']; ?></td>
                                        <td class="price">₹<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <i class="fas fa-clock"></i>
                                                <?php elseif ($order['status'] === 'completed'): ?>
                                                    <i class="fas fa-check-circle"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle"></i>
                                                <?php endif; ?>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-cart"></i>
                        <p>You haven't placed any orders yet.</p>
                        <a href="index.php" class="btn">
                            <i class="fas fa-shopping-bag"></i>
                            Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>
