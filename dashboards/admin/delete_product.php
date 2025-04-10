<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied.");
}

$id = intval($_GET['id']);

// Optional: delete image file too (if stored)
$product = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
if ($product && !empty($product['image'])) {
    @unlink("../../uploads/" . $product['image']);
}

$conn->query("DELETE FROM products WHERE id = $id");
header("Location: products.php");
