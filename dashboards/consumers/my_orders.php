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
        .orders-container {
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
        .page-header {
            text-align: center;
            margin: 40px 0;
            animation: fadeIn 0.8s ease-out;
        }
        .page-header h1 {
            color: #007B5E;
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .page-header p {
            color: #6c757d;
            font-size: 1.2rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
            text-decoration: none;
        }
        .orders-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 30px 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .orders-table th {
            background: #007B5E;
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            text-align: left;
        }
        .orders-table td {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .orders-table tr:last-child td {
            border-bottom: none;
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
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .product-title {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: capitalize;
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
            color: #007B5E;
            font-size: 1.1rem;
        }
        .no-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 30px 0;
        }
        .no-orders i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .no-orders p {
            color: #6c757d;
            font-size: 1.2rem;
            margin: 0;
        }
        @media (max-width: 768px) {
            .orders-table {
                border-radius: 10px;
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
                display: inline-block;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <div class="content-wrapper">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
            
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Track and manage your orders</p>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="../../assets/uploads/<?php echo $row['product_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_title']); ?>"
                                             class="product-image">
                                        <h3 class="product-title"><?php echo htmlspecialchars($row['product_title']); ?></h3>
                                    </div>
                                </td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td class="price">₹<?php echo number_format($row['total_price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date("d M Y, h:i A", strtotime($row['ordered_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <p>You haven't placed any orders yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include('../../includes/footer.php'); ?>

</body>
</html>
