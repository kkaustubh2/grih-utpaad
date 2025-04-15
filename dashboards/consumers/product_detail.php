<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

// Get cart count
$cart_count_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE consumer_id = ?");
$cart_count_query->bind_param("i", $_SESSION['user']['id']);
$cart_count_query->execute();
$cart_count = $cart_count_query->get_result()->fetch_assoc()['count'];

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

// Check if user has purchased this product
$has_purchased_query = $conn->prepare("
    SELECT COUNT(*) as purchased 
    FROM orders 
    WHERE consumer_id = ? AND product_id = ? AND status = 'fulfilled'
");
$has_purchased_query->bind_param("ii", $_SESSION['user']['id'], $product_id);
$has_purchased_query->execute();
$has_purchased = $has_purchased_query->get_result()->fetch_assoc()['purchased'] > 0;

// Check if user has already reviewed
$has_reviewed_query = $conn->prepare("
    SELECT COUNT(*) as reviewed 
    FROM reviews 
    WHERE consumer_id = ? AND product_id = ?
");
$has_reviewed_query->bind_param("ii", $_SESSION['user']['id'], $product_id);
$has_reviewed_query->execute();
$has_reviewed = $has_reviewed_query->get_result()->fetch_assoc()['reviewed'] > 0;
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
            background-image: url('../../assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            position: relative;
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

        .container {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
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
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .product-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .product-image-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .product-image {
            width: 100%;
            max-width: 900px;
            height: auto;
            border-radius: 8px;
        }

        .product-details {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid rgba(0, 123, 94, 0.1);
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
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .rating {
            font-size: 1.8rem;
            color: #ffc107;
        }

        .review-count {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .reviews-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .review-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 123, 94, 0.1);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-2px);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 1.2rem;
        }

        .review-content {
            color: #6c757d;
            line-height: 1.6;
        }

        .add-to-cart-form {
            margin-top: 30px;
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .quantity-input label {
            color: #2c3e50;
            font-weight: 500;
        }

        .quantity-input input {
            width: 80px;
            padding: 8px 12px;
            border: 1px solid rgba(0, 123, 94, 0.2);
            border-radius: 8px;
            font-size: 1rem;
            color: #2c3e50;
        }

        .quantity-input input:focus {
            outline: none;
            border-color: #007B5E;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }

        .add-to-cart-btn {
            background: #007B5E;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }

        .add-to-cart-btn:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .cart-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #007B5E;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 0.9rem;
            margin-left: 8px;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #1e2b37;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .cart-btn {
            background: #007B5E;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-right: 15px;
        }

        .cart-btn:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
            }

            .product-image {
                max-width: 100%;
            }

            .product-title {
                font-size: 2rem;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .rating-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['cart']) && $_GET['cart'] === 'added'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Product added to cart successfully!
            </div>
        <?php endif; ?>

        <div class="header">
            <div class="user-info">
                <i class="fas fa-user-circle fa-2x" style="color: #007B5E;"></i>
                <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
            </div>
            <div style="display: flex; align-items: center;">
                <a href="view_cart.php" class="cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <a href="view_product.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <div class="product-container">
            <div class="product-image-section">
                <?php if (!empty($product['image'])): ?>
                    <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         class="product-image"
                         loading="lazy">
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-store" style="font-size: 48px; color: #007B5E;"></i>
                        <p style="color: #666;">No image available</p>
                    </div>
                <?php endif; ?>
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
                    <form action="add_to_cart.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>

                    <form action="place_order.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="action" value="direct_order">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-bolt"></i> Buy Now
                        </button>
                    </form>

                    <?php if ($has_purchased): ?>
                        <a href="review_product.php?product_id=<?php echo $product_id; ?>" class="btn <?php echo $has_reviewed ? 'btn-secondary' : 'btn-primary'; ?>" style="flex: 1;">
                            <i class="fas <?php echo $has_reviewed ? 'fa-edit' : 'fa-star'; ?>"></i>
                            <?php echo $has_reviewed ? 'Edit Review' : 'Write Review'; ?>
                        </a>
                    <?php endif; ?>
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
