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
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-image: url('assets/images/background.jpg');
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
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            z-index: 1;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            width: calc(100% - 40px);
            margin: 20px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
            box-sizing: border-box;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 123, 94, 0.1);
        }

        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0, 123, 94, 0.2);
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 20px;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(248, 249, 250, 0.5);
            border-radius: 15px;
            padding: 20px;
            min-height: 400px;
        }

        .product-image img {
            max-width: 100%;
            max-height: 500px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
        }

        .product-info {
            padding: 20px;
            background: white;
            border-radius: 15px;
        }

        .product-info h1 {
            color: #2c3e50;
            margin: 0 0 20px 0;
            font-size: 2rem;
            line-height: 1.3;
        }

        .price {
            font-size: 2.2rem;
            color: #007B5E;
            font-weight: 700;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .seller-info {
            background: rgba(0, 123, 94, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid rgba(0, 123, 94, 0.1);
        }

        .seller-info h3 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .seller-info p {
            margin: 10px 0;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reviews-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .reviews-section h3 {
            color: #2c3e50;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-card {
            background: rgba(248, 249, 250, 0.5);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 123, 94, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 123, 94, 0.15);
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-action {
            width: 100%;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-cart {
            background: #007B5E;
            color: white;
        }

        .btn-cart:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-buy {
            background: #2c3e50;
            color: white;
        }

        .btn-buy:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 10px 0;
        }

        .quantity-input label {
            color: #2c3e50;
            font-weight: 500;
            min-width: 80px;
        }

        .quantity-input input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(0, 123, 94, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            color: #2c3e50;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-image {
                min-height: 300px;
            }

            .product-image img {
                max-height: 400px;
            }

            .container {
                margin: 10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
            <a href="dashboards/admin/manage_reviews.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Manage Reviews
            </a>
        <?php else: ?>
            <a href="products.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        <?php endif; ?>

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
                <div class="price">
                    <i class="fas fa-rupee-sign"></i>
                    <?php echo number_format($product['price'], 2); ?>
                </div>
                <div class="seller-info">
                    <h3><i class="fas fa-store"></i> Seller Information</h3>
                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?></p>
                </div>
                <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'consumer'): ?>
                    <div class="quantity-input">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    </div>

                    <div class="action-buttons">
                        <form method="POST" action="add_to_cart.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" id="cart_quantity" value="1">
                            <button type="submit" class="btn-action btn-cart">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </form>

                        <a href="checkout.php?product_id=<?php echo $product['id']; ?>" class="btn-action btn-buy">
                            <i class="fas fa-bolt"></i> Buy Now
                        </a>
                    </div>
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
                    No reviews yet!
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