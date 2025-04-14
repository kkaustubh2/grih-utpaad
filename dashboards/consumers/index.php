<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
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
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9f5f1 100%);
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      background: rgba(255, 255, 255, 0.9);
      padding: 15px 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .user-name {
      font-weight: 500;
      color: #2c3e50;
    }
    .logout-btn {
      background: #dc3545;
      color: white;
      padding: 8px 20px;
      border-radius: 5px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }
    .logout-btn:hover {
      background: #c82333;
      transform: translateY(-2px);
    }
    .hero {
      background: rgba(255, 255, 255, 0.9);
      padding: 40px;
      margin-bottom: 30px;
      text-align: center;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
    }
    .hero h1 {
      color: #007B5E;
      font-size: 40px;
      margin-bottom: 15px;
    }
    .hero p {
      font-size: 18px;
      margin-bottom: 25px;
      color: #6c757d;
    }
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 25px;
      margin-top: 30px;
    }
    .feature-card {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 30px;
      text-align: center;
      transition: transform 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
    }
    .feature-card:hover {
      transform: translateY(-5px);
    }
    .feature-card i {
      font-size: 35px;
      color: #007B5E;
      margin-bottom: 20px;
    }
    .feature-card h3 {
      margin: 15px 0;
      color: #2c3e50;
      font-size: 22px;
    }
    .feature-card p {
      color: #6c757d;
      line-height: 1.6;
    }
    .btn-group {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 25px;
    }
    .btn {
      padding: 12px 25px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
    }
    .btn:hover {
      transform: translateY(-2px);
    }
    .btn-primary {
      background: #007B5E;
      color: white;
    }
    .btn-primary:hover {
      background: #005b46;
    }
    .btn-success {
      background: #28a745;
      color: white;
    }
    .btn-success:hover {
      background: #218838;
    }
    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      .btn-group {
        flex-direction: column;
      }
      .hero h1 {
        font-size: 32px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="header">
      <div class="user-info">
        <i class="fas fa-user-circle fa-2x" style="color: #007B5E;"></i>
        <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
      </div>
      <a href="../../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>

    <div class="hero">
      <h1>Welcome to Your Dashboard</h1>
      <p>Explore and purchase unique handmade products from talented women entrepreneurs.</p>
      <div class="btn-group">
        <a href="view_product.php" class="btn btn-primary">
          <i class="fas fa-store"></i> Browse Products
        </a>
        <a href="my_orders.php" class="btn btn-success">
          <i class="fas fa-shopping-bag"></i> My Orders
        </a>
      </div>
    </div>

    <div class="features">
      <div class="feature-card">
        <i class="fas fa-search"></i>
        <h3>Discover Products</h3>
        <p>Browse through a wide range of handmade products and services from local artisans.</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-shopping-cart"></i>
        <h3>Easy Ordering</h3>
        <p>Simple and secure ordering process with multiple payment options for your convenience.</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-truck"></i>
        <h3>Track Orders</h3>
        <p>Monitor your order status and get real-time delivery updates for your purchases.</p>
      </div>
    </div>
  </div>

  <?php include('../../includes/footer.php'); ?>
</body>

</html>
