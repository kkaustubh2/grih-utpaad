<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

$householder_id = $_SESSION['user']['id'];

// Fetch all orders for the current householder's products
$stmt = $conn->prepare("
    SELECT o.*, 
           p.title AS product_title, 
           p.image AS product_image,
           u.name AS consumer_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.consumer_id = u.id
    WHERE p.user_id = ?
    ORDER BY o.ordered_at DESC
");
$stmt->bind_param("i", $householder_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Received Orders - Grih Utpaad</title>
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

        .container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
        }

        .card-header h2 i {
            color: #007B5E;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #fff;
            color: #007B5E;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 123, 94, 0.2);
        }

        .back-btn:hover {
            background: rgba(0, 123, 94, 0.1);
            transform: translateX(-5px);
        }

        .table-responsive {
            overflow-x: auto;
            margin: 0 -20px;
            padding: 0 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }

        th {
            background: rgba(0, 123, 94, 0.1);
            color: #2c3e50;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            font-size: 0.95rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 123, 94, 0.1);
            color: #2c3e50;
        }

        tr:hover {
            background: rgba(0, 123, 94, 0.02);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
        }

        .status-fulfilled {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }

        .action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-fulfill {
            background: #28a745;
            color: white;
        }

        .btn-fulfill:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-title {
            font-weight: 500;
            color: #2c3e50;
        }

        .buyer-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .buyer-cell i {
            color: #007B5E;
        }

        .date-cell {
            display: flex;
            flex-direction: column;
        }

        .date-primary {
            font-weight: 500;
            color: #2c3e50;
        }

        .date-secondary {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .alert-info {
            background: rgba(23, 162, 184, 0.1);
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.2);
        }

        @media (max-width: 768px) {
            .card {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .table-responsive {
                margin: 0 -15px;
                padding: 0 15px;
            }

            td {
                padding: 10px;
            }

            .action-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }

            .product-cell {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .date-cell {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-shopping-bag"></i>
                        Received Orders
                    </h2>
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Buyer</th>
                                    <th>Qty</th>
                                    <th>Total (₹)</th>
                                    <th>Status</th>
                                    <th>Ordered On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <img src="../../assets/uploads/<?php echo $row['product_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($row['product_title']); ?>"
                                                     class="product-image">
                                                <span class="product-title"><?php echo htmlspecialchars($row['product_title']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="buyer-cell">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($row['consumer_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo $row['quantity']; ?></td>
                                        <td>₹<?php echo number_format($row['total_price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                                <i class="fas fa-<?php echo $row['status'] === 'fulfilled' ? 'check-circle' : ($row['status'] === 'cancelled' ? 'times-circle' : 'clock'); ?>"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <span class="date-primary"><?php echo date("d M Y", strtotime($row['ordered_at'])); ?></span>
                                                <span class="date-secondary"><?php echo date("h:i A", strtotime($row['ordered_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <div style="display: flex; gap: 8px;">
                                                    <a href="update_order_status.php?id=<?php echo $row['id']; ?>&status=fulfilled" 
                                                       class="action-btn btn-fulfill"
                                                       onclick="return confirm('Are you sure you want to mark this order as fulfilled?');">
                                                        <i class="fas fa-check"></i> Fulfill
                                                    </a>
                                                    <a href="update_order_status.php?id=<?php echo $row['id']; ?>&status=cancelled" 
                                                       class="action-btn btn-cancel"
                                                       onclick="return confirm('Are you sure you want to cancel this order?');">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No orders found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>
