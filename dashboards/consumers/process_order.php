<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $shipping_address = trim($_POST['shipping_address']);
    $phone = trim($_POST['phone']);
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
    $status = 'pending'; // Changed to lowercase to match enum

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (product_id, consumer_id, quantity, total_price, shipping_address, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidsss", $product_id, $consumer_id, $quantity, $total_price, $shipping_address, $phone, $status);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order.");
        }
        
        $order_id = $stmt->insert_id;

        // Commit transaction
        $conn->commit();

        // Redirect to success page
        header("Location: order_success.php?id=" . $order_id);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        die("Order failed: " . $e->getMessage());
    }
} else {
    header("Location: view_product.php");
    exit();
}
?> 