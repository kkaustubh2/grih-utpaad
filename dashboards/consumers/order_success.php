<?php
require_once('../../includes/auth.php');
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Placed - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <h2>🎉 Order Placed Successfully!</h2>
    <p>Your order (ID: #<?php echo $order_id; ?>) has been placed and is currently pending confirmation.</p>
    <p><a href="index.php">← Back to Products</a> | <a href="my_orders.php">📦 View My Orders</a></p>
</body>
</html>
