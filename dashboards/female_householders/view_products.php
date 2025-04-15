<?php
session_start();

// Check if user is logged in and is a female householder
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'female_householder') {
  header('Location: ../../login.php');
  exit();
}

require_once('../../config/db.php');

// Fetch products with category names
$stmt = $conn->prepare("
    SELECT 
        p.*,
        pc.name as category_name
    FROM products p 
    LEFT JOIN product_categories pc ON p.category_id = pc.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
  <title>My Products - Grih Utpaad</title>
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

    .card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }

    .badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .badge-success {
      background-color: #d4edda !important;
      color: #155724 !important;
    }

    .badge-warning {
      background-color: #fff3cd !important;
      color: #856404 !important;
    }

    .btn-group {
      display: flex;
      gap: 8px;
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
      font-size: 0.95rem;
    }

    .btn-sm {
      padding: 6px 12px;
      font-size: 0.875rem;
    }

    .btn-primary {
      background-color: #007B5E;
      color: white;
    }

    .btn-primary:hover {
      background-color: #005b46;
      color: white;
      text-decoration: none;
    }

    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
      color: white;
      text-decoration: none;
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background-color: #c82333;
      color: white;
      text-decoration: none;
    }

    .product-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e9ecef;
    }

    .card-header h2 {
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
      color: #2c3e50;
      font-size: 1.5rem;
    }

    .header-buttons {
      display: flex;
      gap: 10px;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }

    .alert-info {
      background-color: #e3f2fd;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      background: white;
    }

    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #e9ecef;
      vertical-align: middle;
    }

    th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #2c3e50;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    .category-badge {
      background-color: #e9ecef;
      color: #2c3e50;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    @media (max-width: 768px) {
      .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }

      .header-buttons {
        width: 100%;
      }

      .btn {
        flex: 1;
        justify-content: center;
      }

      .table-responsive {
        overflow-x: auto;
      }

      th, td {
        padding: 12px 10px;
      }

      .btn-group {
        flex-direction: column;
        width: 100%;
      }

      .btn-group .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>

<body>
  <div class="main-wrapper">
    <div class="container">
      <div class="card">
        <div class="card-header">
          <h2>
            <i class="fas fa-box" style="color: #007B5E;"></i>
            My Products
          </h2>
          <div class="header-buttons">
            <a href="add_product.php" class="btn btn-primary">
              <i class="fas fa-plus"></i> Add New Product
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
          </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success'];
                                                unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            You haven't added any products yet.
            <a href="add_product.php" style="color: inherit; text-decoration: underline;">Add your first product</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Price (₹)</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $product): ?>
                  <tr>
                    <td>
                      <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>"
                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                        class="product-image">
                    </td>
                    <td><?php echo htmlspecialchars($product['title']); ?></td>
                    <td>
                      <span class="category-badge">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                      </span>
                    </td>
                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                    <td>
                      <?php if ($product['is_approved'] == 1): ?>
                        <span class="badge badge-success">
                          <i class="fas fa-check-circle"></i> Approved
                        </span>
                      <?php else: ?>
                        <span class="badge badge-warning">
                          <i class="fas fa-clock"></i> Pending Approval
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="btn-group">
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>"
                          class="btn btn-sm btn-primary">
                          <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_product.php?id=<?php echo $product['id']; ?>"
                          class="btn btn-sm btn-danger"
                          onclick="return confirm('Are you sure you want to delete this product?');">
                          <i class="fas fa-trash"></i> Delete
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include('../../includes/footer.php'); ?>
</body>

</html>
