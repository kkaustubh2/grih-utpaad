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

// Fetch all products with their categories
$query = "SELECT p.*, u.name AS seller_name, pc.name AS category_name 
          FROM products p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN product_categories pc ON p.category_id = pc.id 
          WHERE p.is_approved = 1";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse Products - Grih Utpaad</title>
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
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: #c82333;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 30px 0;
        }

        .product-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .product-image-container {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
        }

        .product-image {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
        }

        .product-title {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-category {
            color: #007B5E;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 123, 94, 0.1);
            border-radius: 15px;
        }

        .product-price {
            font-size: 1.6rem;
            color: #007B5E;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .product-seller {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-seller i {
            color: #007B5E;
        }

        .product-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            flex: 1;
            text-align: center;
            padding: 12px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #007B5E;
            color: white;
        }

        .btn-primary:hover {
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

        .page-header {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            color: #007B5E;
            margin: 0;
            font-size: 2rem;
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

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            .product-title {
                font-size: 1.2rem;
            }
            .product-price {
                font-size: 1.4rem;
            }
        }
        .cart-btn {
            background: #007B5E;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
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

        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="page-header">
            <h1>Browse Products</h1>
            <p>Discover unique handmade products from talented women entrepreneurs</p>
        </div>

        <div class="product-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php if (!empty($product['image'])): ?>
                                <img src="../../assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['title'] ?? 'Product Image') ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div style="text-align: center; padding: 20px;">
                                    <i class="fas fa-store" style="font-size: 48px; color: #007B5E;"></i>
                                    <p style="color: #666;">No image available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <?php if ($product['category_name']): ?>
                                <span class="product-category">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <h2 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h2>
                            <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-seller">
                                <i class="fas fa-store"></i>
                                <?php echo htmlspecialchars($product['seller_name']); ?>
                            </div>
                            <div class="product-actions">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-box-open fa-3x" style="color: #6c757d; margin-bottom: 15px;"></i>
                    <h3>No Products Available</h3>
                    <p>Check back later for new products!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>