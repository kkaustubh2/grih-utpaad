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
</head>
<body class="index-page">

<div class="container">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">
                <i class="fas fa-shopping-bag" style="color: #007B5E;"></i>
                Received Orders
            </h2>
            <a href="index.php" class="btn" style="background-color: #6c757d;">
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="../../assets/uploads/<?php echo $row['product_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_title']); ?>"
                                             style="width: 60px; height: 60px; object-fit: cover;">
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
                                <td>
                                    <span style="font-weight: 500; color: #007B5E;">
                                        ₹<?php echo number_format($row['total_price'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusColor = '';
                                    $statusIcon = '';
                                    switch($row['status']) {
                                        case 'Pending':
                                            $statusColor = '#ffc107';
                                            $statusIcon = 'clock';
                                            break;
                                        case 'Shipped':
                                            $statusColor = '#17a2b8';
                                            $statusIcon = 'truck';
                                            break;
                                        case 'Delivered':
                                            $statusColor = '#28a745';
                                            $statusIcon = 'check-circle';
                                            break;
                                    }
                                    ?>
                                    <span class="badge" style="background: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>; padding: 5px 10px; border-radius: 15px;">
                                        <i class="fas fa-<?php echo $statusIcon; ?>"></i> 
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;"><?php echo date("d M Y", strtotime($row['ordered_at'])); ?></span>
                                        <span style="font-size: 0.85rem; color: #6c757d;"><?php echo date("h:i A", strtotime($row['ordered_at'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'Pending'): ?>
                                        <a href="update_order_status.php?id=<?php echo $row['id']; ?>&status=Shipped" 
                                           class="btn" style="background: #17a2b8; font-size: 0.9rem;">
                                            <i class="fas fa-truck"></i> Mark as Shipped
                                        </a>
                                    <?php elseif ($row['status'] == 'Shipped'): ?>
                                        <a href="update_order_status.php?id=<?php echo $row['id']; ?>&status=Delivered" 
                                           class="btn" style="background: #28a745; font-size: 0.9rem;">
                                            <i class="fas fa-check-circle"></i> Mark as Delivered
                                        </a>
                                    <?php else: ?>
                                        <span class="badge" style="background: #e9f5f1; color: #007B5E; padding: 5px 10px; border-radius: 15px;">
                                            <i class="fas fa-check-double"></i> Completed
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #6c757d; margin-bottom: 20px;"></i>
                <p style="font-size: 1.2rem; color: #6c757d;">No orders received yet.</p>
                <p style="color: #6c757d;">Orders will appear here once customers make purchases.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
