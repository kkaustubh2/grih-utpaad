<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied.");
}

$orders = $conn->query("
    SELECT o.*, p.title AS product_title, u.name AS buyer
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.consumer_id = u.id
    ORDER BY o.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - All Orders</title>
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
        .header h2 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .transparency-box {
            background: #f8f9fa;
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
            margin: 5px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .transparency-box i {
            color: #007B5E;
        }
        .status-guide {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: 6px;
            background: white;
            font-size: 0.9rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-fulfilled {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="content-wrapper">
            <div class="header">
                <h2><i class="fas fa-shopping-cart"></i> All Orders</h2>
            </div>

            <!-- Add transparency info box -->
            <div class="transparency-box">
                <h4>Order Management Overview:</h4>
                <div class="status-guide">
                    <div class="status-item">
                        <i class="fas fa-clock" style="color: #856404;"></i>
                        <span>Pending Orders</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-check" style="color: #155724;"></i>
                        <span>Fulfilled Orders</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-times" style="color: #721c24;"></i>
                        <span>Cancelled Orders</span>
                    </div>
                </div>
                <p><i class="fas fa-info-circle"></i> Track all orders and their current status</p>
                <p><i class="fas fa-chart-line"></i> Monitor sales performance and trends</p>
                <p><i class="fas fa-history"></i> View complete order history and details</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['product_title']) ?></td>
                            <td><?= htmlspecialchars($row['buyer']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td>₹<?= number_format($row['total_price'], 2) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>
