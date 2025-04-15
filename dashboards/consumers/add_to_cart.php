<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $consumer_id = $_SESSION['user']['id'];

    // Check if product exists and is approved
    $check_product = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_approved = 1");
    $check_product->bind_param("i", $product_id);
    $check_product->execute();
    $product_result = $check_product->get_result();

    if ($product_result->num_rows === 0) {
        header('Location: view_product.php');
        exit();
    }

    // Check if product is already in cart
    $check_cart = $conn->prepare("SELECT id, quantity FROM cart WHERE consumer_id = ? AND product_id = ?");
    $check_cart->bind_param("ii", $consumer_id, $product_id);
    $check_cart->execute();
    $cart_result = $check_cart->get_result();

    if ($cart_result->num_rows > 0) {
        // Update quantity if product already in cart
        $cart_item = $cart_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + 1;
        
        $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update_cart->bind_param("ii", $new_quantity, $cart_item['id']);
        $update_cart->execute();
    } else {
        // Add new item to cart
        $add_to_cart = $conn->prepare("INSERT INTO cart (consumer_id, product_id, quantity) VALUES (?, ?, 1)");
        $add_to_cart->bind_param("ii", $consumer_id, $product_id);
        $add_to_cart->execute();
    }

    // Redirect back with success message
    header('Location: product_detail.php?id=' . $product_id . '&cart=added');
    exit();
} else {
    header('Location: view_product.php');
    exit();
} 