<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

$order_id = intval($_GET['id']);
$new_status = $_GET['status'];

// Validate status
$allowed = ['pending', 'fulfilled', 'cancelled'];
if (!in_array($new_status, $allowed)) {
    die("Invalid status.");
}

// Ensure the order belongs to the householder
$householder_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT o.*, p.user_id 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.id = ? AND p.user_id = ?
");
$stmt->bind_param("ii", $order_id, $householder_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Access denied.");
}

// Update the status
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $order_id);
$stmt->execute();

header("Location: received_orders.php");
exit();
