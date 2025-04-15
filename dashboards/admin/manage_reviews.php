<?php
require_once('../../includes/auth.php');

// Additional role check for admin
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

// Handle review deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['review_id'])) {
    $review_id = (int)$_POST['review_id'];
    $delete_stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $delete_stmt->bind_param("i", $review_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Review has been deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete review.";
    }
    
    header("Location: manage_reviews.php");
    exit();
}

// Fetch all reviews with product and user details
$reviews_query = "
    SELECT r.*, 
           p.title as product_title, p.id as product_id,
           u.name as reviewer_name, u.email as reviewer_email
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.consumer_id = u.id
    ORDER BY r.created_at DESC";
$reviews = $conn->query($reviews_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        body {
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.9) 0%, rgba(233, 245, 241, 0.9) 100%),
                url('../../assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            flex: 1 0 auto;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            width: 100%;
            box-sizing: border-box;
        }
        footer {
            flex-shrink: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px 0;
            margin-top: auto;
            box-shadow: 0 -4px 16px rgba(0, 123, 94, 0.1);
        }
        .reviews-container {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .reviews-header h2 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .review-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 123, 94, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 20px;
        }
        .review-info {
            flex: 1;
        }
        .review-info h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .review-info p {
            margin: 5px 0;
            color: #6c757d;
        }
        .review-actions {
            display: flex;
            gap: 15px;
        }
        .product-link {
            color: #007B5E;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .product-link:hover {
            color: #005b46;
        }
        .rating {
            color: #ffc107;
            margin: 15px 0;
            font-size: 1.2rem;
        }
        .review-text {
            color: #2c3e50;
            line-height: 1.8;
            background: rgba(248, 249, 250, 0.5);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .view-btn {
            background: #007B5E;
            color: white;
        }
        .view-btn:hover {
            background: #005b46;
            transform: translateY(-2px);
        }
        .delete-btn {
            background: #dc3545;
            color: white;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #007B5E;
            opacity: 0.5;
        }
        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }
        @media (max-width: 768px) {
            .review-header {
                flex-direction: column;
            }
            .review-actions {
                width: 100%;
                justify-content: stretch;
            }
            .review-actions .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reviews-container">
            <div class="reviews-header">
                <h2><i class="fas fa-comments"></i> Manage Reviews</h2>
                <a href="dashboard.php" class="btn view-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-info">
                                <h3>
                                    <a href="../consumers/product_detail.php?id=<?php echo $review['product_id']; ?>" class="product-link">
                                        <i class="fas fa-box"></i> <?php echo htmlspecialchars($review['product_title']); ?>
                                    </a>
                                </h3>
                                <p>
                                    <i class="fas fa-user"></i> Reviewed by: <?php echo htmlspecialchars($review['reviewer_name']); ?> 
                                    (<?php echo htmlspecialchars($review['reviewer_email']); ?>)
                                </p>
                                <p>
                                    <i class="fas fa-calendar"></i> Posted on: <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </p>
                            </div>
                            <div class="review-actions">
                                <a href="../consumers/product_detail.php?id=<?php echo $review['product_id']; ?>" class="btn view-btn">
                                    <i class="fas fa-eye"></i> View Product
                                </a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" class="btn delete-btn">
                                        <i class="fas fa-trash"></i> Delete Review
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="rating">
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
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>No reviews found. Reviews will appear here once customers start reviewing products.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html> 