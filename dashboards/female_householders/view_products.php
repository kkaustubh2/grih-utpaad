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
  <link rel="stylesheet" href="../../assets/uploads/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
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
      background-color: #28a745;
      color: white;
    }

    .badge-warning {
      background-color: #ffc107;
      color: #000;
    }

    .btn-group {
      display: flex;
      gap: 8px;
    }

    .btn-sm {
      padding: 6px 12px;
      font-size: 0.875rem;
    }

    .btn-danger {
      background-color: #dc3545;
    }

    .btn-danger:hover {
      background-color: #c82333;
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
      margin-bottom: 20px;
    }

    .card-header h2 {
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-buttons {
      display: flex;
      gap: 10px;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="card">
      <div class="card-header">
        <h2>
          <i class="fas fa-box" style="color: #007B5E;"></i>
          My Products
        </h2>
        <div class="header-buttons">
          <a href="add_product.php" class="btn">
            <i class="fas fa-plus"></i> Add New Product
          </a>
          <a href="dashboard.php" class="btn" style="background-color: #6c757d;">
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
                    <span class="badge" style="background-color: #007B5E; color: white;">
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
                        class="btn btn-sm">
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
  <?php include('../../includes/footer.php'); ?>
</body>

</html>
