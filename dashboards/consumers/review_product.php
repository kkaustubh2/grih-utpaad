<?php
session_start();

// Check if user is logged in and is a consumer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

$success = '';
$error = '';
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Check if the product exists and the user has ordered it
$order_check = $conn->prepare("
    SELECT o.*, p.title, p.image, u.name as seller_name 
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE o.product_id = ? AND o.consumer_id = ?
    LIMIT 1
");
$order_check->bind_param("ii", $product_id, $_SESSION['user']['id']);
$order_check->execute();
$order = $order_check->get_result()->fetch_assoc();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Check if user has already reviewed this product
$existing_review = $conn->prepare("
    SELECT * FROM reviews 
    WHERE product_id = ? AND consumer_id = ?
    LIMIT 1
");
$existing_review->bind_param("ii", $product_id, $_SESSION['user']['id']);
$existing_review->execute();
$review = $existing_review->get_result()->fetch_assoc();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5 stars.";
    } elseif (empty($review_text)) {
        $error = "Please write a review.";
    } else {
        if ($review) {
            // Update existing review
            $stmt = $conn->prepare("
                UPDATE reviews 
                SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->bind_param("isi", $rating, $review_text, $review['id']);
        } else {
            // Insert new review
            $stmt = $conn->prepare("
                INSERT INTO reviews (product_id, consumer_id, rating, review_text) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiis", $product_id, $_SESSION['user']['id'], $rating, $review_text);
        }
        
        if ($stmt->execute()) {
            $success = "Your review has been " . ($review ? "updated" : "submitted") . " successfully!";
            // Refresh the review data
            $existing_review->execute();
            $review = $existing_review->get_result()->fetch_assoc();
        } else {
            $error = "Failed to submit review. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Product - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .product-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-details {
            flex: 1;
        }
        .product-title {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .seller-name {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .star-rating {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #e9ecef;
            transition: color 0.2s ease;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        .review-text {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
            resize: vertical;
        }
        .review-text:focus {
            border-color: #007B5E;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="my_orders.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to My Orders
        </a>

        <div class="card">
            <h2><i class="fas fa-star"></i> Review Product</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="product-info">
                <img src="../../assets/uploads/<?php echo htmlspecialchars($order['image']); ?>" 
                     alt="<?php echo htmlspecialchars($order['title']); ?>"
                     class="product-image">
                <div class="product-details">
                    <h3 class="product-title"><?php echo htmlspecialchars($order['title']); ?></h3>
                    <div class="seller-name">
                        <i class="fas fa-store"></i> 
                        Sold by <?php echo htmlspecialchars($order['seller_name']); ?>
                    </div>
                    <div>
                        <i class="fas fa-shopping-cart"></i>
                        Ordered on <?php echo date('M d, Y', strtotime($order['ordered_at'])); ?>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="star-rating">
                    <input type="radio" name="rating" value="5" id="star5" <?php echo ($review && $review['rating'] == 5) ? 'checked' : ''; ?>>
                    <label for="star5" class="fas fa-star"></label>
                    <input type="radio" name="rating" value="4" id="star4" <?php echo ($review && $review['rating'] == 4) ? 'checked' : ''; ?>>
                    <label for="star4" class="fas fa-star"></label>
                    <input type="radio" name="rating" value="3" id="star3" <?php echo ($review && $review['rating'] == 3) ? 'checked' : ''; ?>>
                    <label for="star3" class="fas fa-star"></label>
                    <input type="radio" name="rating" value="2" id="star2" <?php echo ($review && $review['rating'] == 2) ? 'checked' : ''; ?>>
                    <label for="star2" class="fas fa-star"></label>
                    <input type="radio" name="rating" value="1" id="star1" <?php echo ($review && $review['rating'] == 1) ? 'checked' : ''; ?>>
                    <label for="star1" class="fas fa-star"></label>
                </div>

                <textarea name="review" class="review-text" placeholder="Write your review here..."><?php echo $review ? htmlspecialchars($review['review_text']) : ''; ?></textarea>

                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i>
                    <?php echo $review ? 'Update Review' : 'Submit Review'; ?>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Highlight stars on load if there's an existing review
        document.addEventListener('DOMContentLoaded', function() {
            const checkedStar = document.querySelector('input[type="radio"]:checked');
            if (checkedStar) {
                const rating = checkedStar.value;
                const stars = document.querySelectorAll('.star-rating label');
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.style.color = '#ffc107';
                    }
                });
            }
        });
    </script>

<?php include('../../includes/footer.php'); ?>

</body>
</html> 