<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $consumer_id = $_SESSION['user']['id'];

    if ($quantity <= 0) {
        die("Invalid quantity.");
    }

    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        die("Product not found.");
    }

    $total_price = $quantity * $product['price'];
    $status = 'Pending';

    // Insert into orders table
    $stmt = $conn->prepare("INSERT INTO orders (product_id, consumer_id, quantity, total_price, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiids", $product_id, $consumer_id, $quantity, $total_price, $status);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        header("Location: order_success.php?id=" . $order_id);
        exit();
    } else {
        die("Order failed. Please try again.");
    }
} else {
    die("Invalid request.");
}
