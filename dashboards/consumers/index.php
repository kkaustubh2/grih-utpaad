<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Consumer Dashboard - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .hero {
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
        }
        .hero h1 {
            color: #007B5E;
            font-size: 40px;
            margin-bottom: 10px;
        }
        .hero p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        .feature-card {
            width: 280px;
            text-align: center;
            padding: 30px;
        }
        .feature-card i {
            font-size: 30px;
            color: #007B5E;
            margin-bottom: 15px;
        }
        .feature-card h3 {
            margin: 15px 0;
            color: #2c3e50;
        }
        .feature-card p {
            color: #6c757d;
            line-height: 1.5;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body class="index-page">
    <div class="container">
        <div class="hero card">
            <h1>Welcome to Consumer Dashboard</h1>
            <p>Explore and purchase unique handmade products from talented women entrepreneurs.</p>
            <div class="btn-group">
                <a href="view_product.php" class="btn">
                    <i class="fas fa-store"></i> Browse Products
                </a>
                <a href="my_orders.php" class="btn" style="background-color: #28a745;">
                    <i class="fas fa-shopping-bag"></i> My Orders
                </a>
            </div>
        </div>

        <div class="features">
            <div class="card feature-card">
                <i class="fas fa-search"></i>
                <h3>Discover Products</h3>
                <p>Browse through a wide range of handmade products and services.</p>
            </div>
            <div class="card feature-card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Easy Ordering</h3>
                <p>Simple and secure ordering process with multiple payment options.</p>
            </div>
            <div class="card feature-card">
                <i class="fas fa-truck"></i>
                <h3>Track Orders</h3>
                <p>Monitor your order status and delivery updates in real-time.</p>
            </div>
        </div>
    </div>
</body>
</html>
