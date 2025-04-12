<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Grih Utpaad - Empowering Women Entrepreneurs</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="index-page">

<div class="container">
    <div class="hero card" style="padding: 40px; margin-bottom: 30px;">
        <h1 style="color: #007B5E; font-size: 40px; margin-bottom: 10px;">Welcome to Grih Utpaad</h1>
        <p style="font-size: 18px; margin-bottom: 20px;">
            Empowering women to showcase and sell homemade products & skills online.
        </p>

        <?php if (isset($_SESSION['user'])): ?>
            <p>Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</p>
            <?php if ($_SESSION['user']['role'] === 'householder'): ?>
                <a href="dashboards/householder/dashboard.php" class="btn">🏠 Householder Dashboard</a>
            <?php elseif ($_SESSION['user']['role'] === 'consumer'): ?>
                <a href="dashboards/consumers/index.php" class="btn">🛒 Consumer Dashboard</a>
            <?php elseif ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="dashboards/admin/dashboard.php" class="btn">👩‍💼 Admin Dashboard</a>
            <?php endif; ?>
            <a href="auth/logout.php" class="btn" style="background: #d9534f;">Logout</a>
        <?php else: ?>
            <div style="display: flex; gap: 10px; align-items: center; justify-content: center; flex-wrap: wrap;">
                <a href="auth/login.php" class="btn">🔐 Login</a>
                <a href="auth/register.php" class="btn" style="background-color: #28a745;">📝 Register</a>
                <a href="dashboards/admin/login.php" class="btn" style="background-color: #6c757d;">
                    <i class="fas fa-user-shield"></i> Login as Admin
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="features" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
        <div class="card" style="width: 280px;">
            <i class="fas fa-store" style="font-size: 30px; color: #007B5E;"></i>
            <h3>Sell Handmade Products</h3>
            <p>List crafts, food items, services, and more.</p>
        </div>
        <div class="card" style="width: 280px;">
            <i class="fas fa-shopping-cart" style="font-size: 30px; color: #007B5E;"></i>
            <h3>Shop with Purpose</h3>
            <p>Explore & buy unique goods directly from creators.</p>
        </div>
        <div class="card" style="width: 280px;">
            <i class="fas fa-user-shield" style="font-size: 30px; color: #007B5E;"></i>
            <h3>Admin Monitoring</h3>
            <p>Ensuring safe, authentic experiences.</p>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html>
