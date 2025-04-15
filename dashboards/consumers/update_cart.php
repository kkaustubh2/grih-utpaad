<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $consumer_id = $_SESSION['user']['id'];

    // Verify cart item belongs to current user
    $check_cart = $conn->prepare("SELECT id, quantity FROM cart WHERE id = ? AND consumer_id = ?");
    $check_cart->bind_param("ii", $cart_id, $consumer_id);
    $check_cart->execute();
    $cart_result = $check_cart->get_result();

    if ($cart_result->num_rows === 0) {
        header('Location: view_cart.php');
        exit();
    }

    $cart_item = $cart_result->fetch_assoc();

    switch ($action) {
        case 'increase':
            $new_quantity = $cart_item['quantity'] + 1;
            $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_cart->bind_param("ii", $new_quantity, $cart_id);
            $update_cart->execute();
            break;

        case 'decrease':
            if ($cart_item['quantity'] > 1) {
                $new_quantity = $cart_item['quantity'] - 1;
                $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_cart->bind_param("ii", $new_quantity, $cart_id);
                $update_cart->execute();
            } else {
                // If quantity would become 0, remove the item
                $delete_cart = $conn->prepare("DELETE FROM cart WHERE id = ?");
                $delete_cart->bind_param("i", $cart_id);
                $delete_cart->execute();
            }
            break;

        case 'remove':
            $delete_cart = $conn->prepare("DELETE FROM cart WHERE id = ?");
            $delete_cart->bind_param("i", $cart_id);
            $delete_cart->execute();
            break;
    }
}

header('Location: view_cart.php');
exit(); 