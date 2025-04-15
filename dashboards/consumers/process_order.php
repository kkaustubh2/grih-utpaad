<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_product.php');
    exit();
}

// Debug: Log POST data
error_log("POST data: " . print_r($_POST, true));

// Validate required fields
if (!isset($_POST['shipping_address']) || !isset($_POST['phone']) || !isset($_POST['products']) || !isset($_POST['quantities'])) {
    $_SESSION['error'] = "Missing required information.";
    error_log("Missing required fields in process_order.php");
    header('Location: view_cart.php');
    exit();
}

$shipping_address = trim($_POST['shipping_address']);
$phone = trim($_POST['phone']);
$products = $_POST['products'];
$quantities = $_POST['quantities'];

// Debug: Log processed data
error_log("Products: " . print_r($products, true));
error_log("Quantities: " . print_r($quantities, true));

// Validate shipping address
if (empty($shipping_address)) {
    $_SESSION['error'] = "Shipping address is required.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Validate phone number
if (!preg_match("/^[0-9]{10}$/", $phone)) {
    $_SESSION['error'] = "Invalid phone number. Please enter a 10-digit number.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Validate products and quantities
if (!is_array($products) || !is_array($quantities) || count($products) !== count($quantities)) {
    $_SESSION['error'] = "Invalid order data.";
    error_log("Invalid products or quantities arrays");
    header('Location: view_cart.php');
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get product prices and create orders
    $get_price = $conn->prepare("SELECT price FROM products WHERE id = ?");
    if (!$get_price) {
        throw new Exception("Failed to prepare price query: " . $conn->error);
    }

    $create_order = $conn->prepare("
        INSERT INTO orders (
            consumer_id, 
            product_id, 
            quantity, 
            total_price, 
            shipping_address, 
            phone, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    if (!$create_order) {
        throw new Exception("Failed to prepare order creation statement: " . $conn->error);
    }

    $first_order_id = null;

    for ($i = 0; $i < count($products); $i++) {
        $product_id = intval($products[$i]);
        $quantity = intval($quantities[$i]);

        error_log("Processing product ID: $product_id with quantity: $quantity");

        if ($quantity <= 0) {
            throw new Exception("Invalid quantity for product ID: $product_id");
        }

        // Get product price
        $get_price->bind_param("i", $product_id);
        if (!$get_price->execute()) {
            throw new Exception("Failed to get price for product ID: $product_id");
        }
        $price_result = $get_price->get_result();
        if ($price_result->num_rows === 0) {
            throw new Exception("Product not found: $product_id");
        }
        $price = $price_result->fetch_assoc()['price'];
        $total_price = $price * $quantity;

        // Create order
        $create_order->bind_param(
            "iiidss",
            $_SESSION['user']['id'],
            $product_id,
            $quantity,
            $total_price,
            $shipping_address,
            $phone
        );

        if (!$create_order->execute()) {
            throw new Exception("Failed to create order: " . $create_order->error);
        }

        // Store the first order ID for redirection
        if ($first_order_id === null) {
            $first_order_id = $conn->insert_id;
        }
    }

    // Clear the cart
    $clear_cart = $conn->prepare("DELETE FROM cart WHERE consumer_id = ?");
    if (!$clear_cart) {
        throw new Exception("Failed to prepare cart clear statement: " . $conn->error);
    }

    $clear_cart->bind_param("i", $_SESSION['user']['id']);
    if (!$clear_cart->execute()) {
        throw new Exception("Failed to clear cart: " . $clear_cart->error);
    }

    // Commit transaction
    $conn->commit();
    error_log("Orders placed successfully!");

    // Set success message and redirect
    $_SESSION['success'] = "Order placed successfully!";
    header('Location: order_success.php?id=' . $first_order_id);
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Order failed: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?> 