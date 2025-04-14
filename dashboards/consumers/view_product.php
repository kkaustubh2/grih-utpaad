<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

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
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 30px 0;
        }
        .product-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 500px;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.5s ease;
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
            border-radius: 10px;
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
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .page-header h1 {
            color: #007B5E;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .page-header p {
            color: #6c757d;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
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
                        <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>" 
                             class="product-image">
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