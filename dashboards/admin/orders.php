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

<h2>📦 All Orders</h2>
<table border="1" cellpadding="10">
    <tr><th>ID</th><th>Product</th><th>Buyer</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr>
    <?php while ($row = $orders->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['product_title']) ?></td>
            <td><?= htmlspecialchars($row['buyer']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td>₹<?= number_format($row['total_price'], 2) ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
        </tr>
    <?php endwhile; ?>
</table>
