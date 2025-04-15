<?php
session_start();
require_once('config/db.php');

// Check if categories table exists and create if it doesn't
$check_categories_table = $conn->query("SHOW TABLES LIKE 'categories'");
if ($check_categories_table->num_rows == 0) {
    $create_categories_table = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($create_categories_table);

    // Insert some default categories
    $default_categories = [
        ['name' => 'Food & Beverages', 'description' => 'Homemade food items and beverages'],
        ['name' => 'Handicrafts', 'description' => 'Handmade craft items'],
        ['name' => 'Clothing', 'description' => 'Handmade clothing and accessories'],
        ['name' => 'Home Decor', 'description' => 'Decorative items for home'],
        ['name' => 'Jewelry', 'description' => 'Handmade jewelry items']
    ];

    $insert_category = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    foreach ($default_categories as $category) {
        $insert_category->bind_param("ss", $category['name'], $category['description']);
        $insert_category->execute();
    }
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details with seller info and category
$query = "SELECT p.*, u.name AS seller_name, pc.name AS category_name 
          FROM products p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN product_categories pc ON p.category_id = pc.id 
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// If product not found, redirect to products page
if (!$product) {
    header('Location: products.php');
    exit();
}

// Fetch reviews for this product
$reviews = $conn->query("
    SELECT r.*, u.name as reviewer_name, u.id as reviewer_id
    FROM reviews r
    JOIN users u ON r.consumer_id = u.id
    WHERE r.product_id = {$product['id']}
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($total_rating / count($reviews), 1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['title']); ?> - Product Detail</title>
    <link rel="stylesheet" href="assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 20px;
        }
        .product-image {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-image img {
            max-width: 100%;
            max-height: 800px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }
        .product-info {
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
        }
        .price {
            font-size: 2em;
            color: #007B5E;
            margin: 15px 0;
        }
        .seller-info {
            background: rgba(0, 123, 94, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .reviews-section {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 15px;
            padding: 20px;
            margin-top: 40px;
        }
        .review-card {
            background: rgba(255, 255, 255, 0.9) !important;
            transition: transform 0.2s;
        }
        .review-card:hover {
            transform: translateY(-2px);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007B5E;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="products.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <div class="product-grid">
            <div class="product-image">
                <?php if (!empty($product['image'])): ?>
                    <img src="assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>">
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-store" style="font-size: 48px; color: #007B5E;"></i>
                        <p style="color: #666;">No image available</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['title']); ?></h1>
                <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
                <div class="seller-info">
                    <h3><i class="fas fa-store"></i> Seller Information</h3>
                    <p>Sold by: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <p>Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                </div>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'consumer'): ?>
                    <form action="add_to_cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="reviews-section">
            <h3>
                <i class="fas fa-star" style="color: #ffc107;"></i> 
                Reviews (<?php echo count($reviews); ?>)
                <?php if ($avg_rating > 0): ?>
                    <span style="font-size: 0.9em; color: #6c757d;">
                        • Average Rating: <?php echo $avg_rating; ?> / 5
                    </span>
                <?php endif; ?>
            </h3>

            <?php if (empty($reviews)): ?>
                <p style="color: #6c757d; text-align: center; padding: 20px;">
                    No reviews yet. Be the first to review this product!
                </p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                <div style="color: #ffc107; margin: 5px 0;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffc107' : '#e9ecef'; ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <small style="color: #6c757d;">
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </small>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>

<?php 