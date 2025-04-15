<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

// Get action type and product ID
$action = isset($_POST['action']) ? $_POST['action'] : '';
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : 0;

// Handle different cases
if ($action === 'cart_order') {
    // For cart orders, redirect to order form with cart flag
    header("Location: order_form.php?type=cart");
    exit();
} elseif ($product_id > 0) {
    // For single product orders
    header("Location: order_form.php?id=" . $product_id);
    exit();
} else {
    // If no valid action or product ID, redirect back to cart
    header("Location: view_cart.php");
    exit();
}
?>
