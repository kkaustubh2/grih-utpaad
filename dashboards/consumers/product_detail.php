<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details with seller info and category
$stmt = $conn->prepare("
    SELECT p.*, u.name AS seller_name, u.about AS seller_about, pc.name AS category_name,
           (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
    FROM products p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE p.id = ? AND p.is_approved = 1
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: view_product.php');
    exit();
}

// Fetch reviews
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.name as reviewer_name 
    FROM reviews r 
    JOIN users u ON r.consumer_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['title']); ?> - Grih Utpaad</title>
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
        .product-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .product-image-section {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        .product-image {
            width: 90%;
            height: 90vh;
            object-fit: contain;
            border-radius: 10px;
            transition: transform 0.3s ease;
            margin: 0 auto;
        }
        .product-image:hover {
            transform: scale(1.02);
        }
        .product-details {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .product-category {
            color: #007B5E;
            font-size: 0.9rem;
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 123, 94, 0.1);
            border-radius: 15px;
            margin-bottom: 15px;
        }
        .product-title {
            font-size: 2.4rem;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        .product-price {
            font-size: 2rem;
            color: #007B5E;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .product-description {
            color: #6c757d;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        .seller-info {
            background: rgba(248, 249, 250, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .seller-info h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .seller-info p {
            color: #6c757d;
            line-height: 1.6;
        }
        .rating-section {
            margin-bottom: 20px;
        }
        .rating {
            font-size: 1.8rem;
            color: #ffc107;
            margin-right: 10px;
        }
        .review-count {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .reviews-section {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .review-card {
            background: rgba(248, 249, 250, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .reviewer-name {
            font-weight: 500;
            color: #2c3e50;
        }
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .review-rating {
            color: #ffc107;
            margin-bottom: 10px;
        }
        .review-content {
            color: #6c757d;
            line-height: 1.6;
        }
        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }
        .btn-primary {
            background: #007B5E;
            color: white;
            flex: 1;
        }
        .btn-secondary {
            background: #2c3e50;
            color: white;
            flex: 1;
        }
        .btn-primary:hover {
            background: #005b46;
            transform: translateY(-2px);
        }
        .btn-secondary:hover {
            background: #1e2b37;
            transform: translateY(-2px);
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
        }
        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
            }
            .product-image {
                width: 100%;
                height: 60vh;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .product-title {
                font-size: 2rem;
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

        <a href="view_product.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <div class="product-container">
            <div class="product-image-section">
                <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['title']); ?>"
                     class="product-image"
                     loading="lazy">
            </div>

            <div class="product-details">
                <?php if ($product['category_name']): ?>
                    <span class="product-category">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                <?php endif; ?>

                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>

                <div class="rating-section">
                    <span class="rating">
                        <?php
                        $rating = round($product['avg_rating'], 1);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </span>
                    <span class="review-count"><?php echo $product['review_count']; ?> reviews</span>
                </div>

                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>

                <div class="seller-info">
                    <h3><i class="fas fa-store"></i> Seller Information</h3>
                    <p><strong><?php echo htmlspecialchars($product['seller_name']); ?></strong></p>
                    <?php if ($product['seller_about']): ?>
                        <p><?php echo htmlspecialchars($product['seller_about']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <div class="action-buttons">
                    <form action="place_order.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="action" value="add_to_cart">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>

                    <form action="place_order.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="action" value="direct_order">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-bolt"></i> Place Order Now
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <h2><i class="fas fa-comments"></i> Customer Reviews</h2>
            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <div class="review-rating">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $review['rating']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No reviews yet. Be the first to review this product!</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>
