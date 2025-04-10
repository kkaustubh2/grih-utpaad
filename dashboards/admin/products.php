<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

// Check admin access
if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied.");
}

// Fetch all products along with seller info
$query = "
    SELECT p.*, u.name AS seller 
    FROM products p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Products</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<h2>🛍️ All Product Listings</h2>

<a href="dashboard.php" class="btn">🏠 Back to Dashboard</a>
<br><br>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Title</th>
            <th>Category</th>
            <th>Seller</th>
            <th>Price (₹)</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><img src="../../uploads/<?= $row['image'] ?>" width="80" /></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['seller']) ?></td>
            <td><?= number_format($row['price'], 2) ?></td>
            <td>
                <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn" style="background-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this product?');">🗑️ Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">No products found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
