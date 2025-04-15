<?php
session_start();

// Check if user is logged in and is a female householder
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'female_householder') {
  header('Location: ../../login.php');
  exit();
}

require_once('../../config/db.php');

// Fetch user's statistics
$user_id = $_SESSION['user']['id'];

// Get total products
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_products = $stmt->get_result()->fetch_assoc()['total_products'];

// Get approved products
$stmt = $conn->prepare("SELECT COUNT(*) as approved_products FROM products WHERE user_id = ? AND is_approved = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approved_products = $stmt->get_result()->fetch_assoc()['approved_products'];

// Get total orders
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders o JOIN products p ON o.product_id = p.id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total_orders'];

// Get recent orders
$stmt = $conn->prepare("
    SELECT 
        o.*,
        p.title as product_title,
        u.name as buyer_name
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users u ON o.consumer_id = u.id
    WHERE p.user_id = ? 
    ORDER BY o.ordered_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
  <title>Dashboard - Grih Utpaad</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      background-image: url('../../assets/images/background.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      font-family: 'Segoe UI', sans-serif;
      position: relative;
      display: flex;
      flex-direction: column;
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.85);
      z-index: 1;
    }

    .main-wrapper {
      flex: 1;
      position: relative;
      z-index: 2;
    }

    .container {
      position: relative;
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-icon {
      font-size: 2.5rem;
      color: #007B5E;
      margin-bottom: 15px;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: bold;
      color: #2c3e50;
      margin: 10px 0;
    }

    .stat-label {
      color: #6c757d;
      font-size: 1rem;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .action-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 15px;
      background-color: #007B5E;
      color: white;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .action-btn:hover {
      background-color: #005b46;
      transform: translateY(-2px);
      text-decoration: none;
      color: white;
    }

    .recent-orders {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
    }

    .recent-orders h3 {
      color: #2c3e50;
      margin-top: 0;
      margin-bottom: 20px;
      font-size: 1.5rem;
    }

    .order-status {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
      display: inline-block;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-fulfilled {
      background-color: #d4edda;
      color: #155724;
    }

    .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
    }

    .welcome-header {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .welcome-text {
      font-size: 1.5rem;
      color: #2c3e50;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .welcome-text i {
      color: #007B5E;
    }

    .btn-group {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-home {
      background-color: #6c757d;
      color: white;
    }

    .btn-home:hover {
      background-color: #5a6268;
      color: white;
      text-decoration: none;
    }

    .btn-logout {
      background-color: #dc3545;
      color: white;
    }

    .btn-logout:hover {
      background-color: #c82333;
      color: white;
      text-decoration: none;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      background-color: #e3f2fd;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #e9ecef;
    }

    th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #2c3e50;
    }

    tr:hover {
      background-color: #f8f9fa;
    }

  </style>
</head>

<body>
  <div class="main-wrapper">
    <div class="container">
      <div class="welcome-header">
        <div class="welcome-text">
          <i class="fas fa-home"></i>
          Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!
        </div>
        <div class="btn-group">
          <a href="../../index.php" class="btn btn-home">
            <i class="fas fa-home"></i> Home
          </a>
          <a href="../../auth/logout.php" class="btn btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <i class="fas fa-box-open stat-icon"></i>
          <div class="stat-value"><?php echo $total_products; ?></div>
          <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card">
          <i class="fas fa-check-circle stat-icon"></i>
          <div class="stat-value"><?php echo $approved_products; ?></div>
          <div class="stat-label">Approved Products</div>
        </div>
        <div class="stat-card">
          <i class="fas fa-shopping-cart stat-icon"></i>
          <div class="stat-value"><?php echo $total_orders; ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
      </div>

      <div class="quick-actions">
        <a href="add_product.php" class="action-btn">
          <i class="fas fa-plus"></i> Add New Product
        </a>
        <a href="view_products.php" class="action-btn">
          <i class="fas fa-list"></i> View Products
        </a>
        <a href="received_orders.php" class="action-btn">
          <i class="fas fa-shopping-bag"></i> View Orders
        </a>
      </div>

      <div class="recent-orders">
        <h3><i class="fas fa-clock"></i> Recent Orders</h3>
        <?php if (empty($recent_orders)): ?>
          <div class="alert">
            <i class="fas fa-info-circle"></i> No orders received yet.
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Buyer</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_orders as $order): ?>
                <tr>
                  <td><?php echo htmlspecialchars($order['product_title']); ?></td>
                  <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                  <td><?php echo $order['quantity']; ?></td>
                  <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                  <td>
                    <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                      <?php echo ucfirst($order['status']); ?>
                    </span>
                  </td>
                  <td><?php echo date('M j, Y', strtotime($order['ordered_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include('../../includes/footer.php'); ?>
</body>

</html>
