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
            background-color: #ffc107;
            color: #000;
        }
        .status-fulfilled {
            background-color: #28a745;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: translateY(-1px);
        }
        .btn-fulfill {
            background-color: #28a745;
            color: white;
        }
        .btn-fulfill:hover {
            background-color: #218838;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body class="index-page">

<div class="container">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">
                <i class="fas fa-shopping-bag" style="color: #007B5E;"></i>
                Received Orders
            </h2>
            <a href="dashboard.php" class="btn" style="background-color: #6c757d;">
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
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="../../assets/uploads/<?php echo $row['product_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_title']); ?>"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($row['product_title']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-user" style="color: #007B5E;"></i>
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
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;"><?php echo date("d M Y", strtotime($row['ordered_at'])); ?></span>
                                        <span style="font-size: 0.85rem; color: #6c757d;"><?php echo date("h:i A", strtotime($row['ordered_at'])); ?></span>
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

<?php include('../../includes/footer.php'); ?>

</body>
</html>
