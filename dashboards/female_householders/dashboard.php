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
  <link rel="stylesheet" href="../../assets/uploads/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
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
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-icon {
      font-size: 2rem;
      color: #007B5E;
      margin-bottom: 10px;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: bold;
      color: #2c3e50;
      margin: 10px 0;
    }

    .stat-label {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
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
    }

    .action-btn:hover {
      background-color: #005b46;
      transform: translateY(-2px);
      text-decoration: none;
      color: white;
    }

    .recent-orders {
      margin-top: 30px;
    }

    .order-status {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .status-pending {
      background-color: #ffc107;
      color: #000;
    }

    .status-fulfilled {
      background-color: #28a745;
      color: white;
    }

    .status-cancelled {
      background-color: #dc3545;
      color: white;
    }

    .welcome-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .welcome-text {
      font-size: 1.5rem;
      color: #2c3e50;
    }

    .logout-btn {
      background-color: #dc3545;
      color: white;
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .logout-btn:hover {
      background-color: #c82333;
      color: white;
      text-decoration: none;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .alert-info {
      background-color: #e3f2fd;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="welcome-header">
      <div class="welcome-text">
        Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!
      </div>
      <a href="../../auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-value"><?php echo $total_products; ?></div>
        <div class="stat-label">Total Products</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?php echo $approved_products; ?></div>
        <div class="stat-label">Approved Products</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value"><?php echo $total_orders; ?></div>
        <div class="stat-label">Total Orders</div>
      </div>
    </div>

    <div class="quick-actions">
      <a href="view_products.php" class="action-btn">
        <i class="fas fa-box"></i>
        Manage Products
      </a>
      <a href="orders.php" class="action-btn">
        <i class="fas fa-shopping-cart"></i>
        View Orders
      </a>
      <a href="profile.php" class="action-btn">
        <i class="fas fa-user"></i>
        My Profile
      </a>
    </div>

    <div class="card recent-orders">
      <h2><i class="fas fa-clock"></i> Recent Orders</h2>
      <?php if (empty($recent_orders)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No orders yet.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Buyer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_orders as $order): ?>
                <tr>
                  <td>#<?php echo $order['id']; ?></td>
                  <td><?php echo htmlspecialchars($order['product_title']); ?></td>
                  <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                  <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                  <td>
                    <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                      <?php echo ucfirst($order['status']); ?>
                    </span>
                  </td>
                  <td><?php echo date('d M Y', strtotime($order['ordered_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include('../../includes/footer.php'); ?>

</body>

</html>
