<?php
require_once('../../includes/auth.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Female Householder Dashboard - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="index-page">

<div class="container">
    <div class="card" style="margin-bottom: 30px;">
        <h2 style="margin-top: 0;">
            <i class="fas fa-home" style="color: #007B5E;"></i>
            Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> 👋
        </h2>
        
        <p style="font-size: 1.1rem; color: #666; margin-bottom: 20px;">
            Manage your products and orders from your dashboard.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="card" style="text-align: center;">
                <i class="fas fa-plus-circle" style="font-size: 2.5rem; color: #007B5E; margin-bottom: 15px;"></i>
                <h3 style="margin: 10px 0;">Add Product</h3>
                <p style="margin-bottom: 20px;">List a new product or skill for sale</p>
                <a href="add_product.php" class="btn" style="width: 100%;">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>

            <div class="card" style="text-align: center;">
                <i class="fas fa-box-open" style="font-size: 2.5rem; color: #007B5E; margin-bottom: 15px;"></i>
                <h3 style="margin: 10px 0;">My Products</h3>
                <p style="margin-bottom: 20px;">View and manage your products</p>
                <a href="view_products.php" class="btn" style="width: 100%;">
                    <i class="fas fa-list"></i> View Products
                </a>
            </div>

            <div class="card" style="text-align: center;">
                <i class="fas fa-shopping-bag" style="font-size: 2.5rem; color: #007B5E; margin-bottom: 15px;"></i>
                <h3 style="margin: 10px 0;">Orders</h3>
                <p style="margin-bottom: 20px;">View and manage received orders</p>
                <a href="received_orders.php" class="btn" style="width: 100%;">
                    <i class="fas fa-shopping-cart"></i> View Orders
                </a>
            </div>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="../../index.php" class="btn" style="background-color: #6c757d; margin-right: 10px;">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="../../auth/logout.php" class="btn" style="background-color: #dc3545;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

</body>
</html>
