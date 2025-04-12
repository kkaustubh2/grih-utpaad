<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if (!isset($_GET['id'])) {
    header('Location: view_product.php');
    exit();
}

$product_id = $_GET['id'];
$consumer_id = $_SESSION['user']['id'];

// Fetch product details
$stmt = $conn->prepare("
    SELECT p.*, u.name AS seller_name, u.email AS seller_email 
    FROM products p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: view_product.php');
    exit();
}

$product = $result->fetch_assoc();

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantity'])) {
    $quantity = (int)$_POST['quantity'];
    $total_price = $quantity * $product['price'];
    
    $stmt = $conn->prepare("INSERT INTO orders (consumer_id, product_id, quantity, total_price, status, ordered_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iiid", $consumer_id, $product_id, $quantity, $total_price);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Order placed successfully!";
        header('Location: my_orders.php');
        exit();
    } else {
        $error_message = "Failed to place order. Please try again.";
    }
}
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
            padding: 40px 20px;
        }
        .content-wrapper {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.1);
            padding: 30px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
            text-decoration: none;
        }
        .transparency-box {
            background: rgba(248, 249, 250, 0.9);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007B5E;
        }
        .transparency-box h4 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .transparency-box p {
            margin: 5px 0;
            color: #666;
        }
        .transparency-box .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        .info-item i {
            color: #007B5E;
        }
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 20px;
        }
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .product-image:hover {
            transform: scale(1.02);
        }
        .product-info h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin: 0 0 20px;
            line-height: 1.3;
        }
        .product-price {
            font-size: 2rem;
            color: #007B5E;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .seller-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .seller-info h3 {
            color: #2c3e50;
            margin: 0 0 10px;
            font-size: 1.2rem;
        }
        .seller-info p {
            margin: 5px 0;
            color: #6c757d;
        }
        .product-description {
            color: #2c3e50;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .order-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007B5E;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }
        .btn-order {
            background: #007B5E;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-order:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .product-image {
                height: 300px;
            }
            .product-info h1 {
                font-size: 2rem;
            }
            .product-price {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body class="index-page">
    <div class="container">
        <a href="view_product.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        
        <div class="content-wrapper">
            <!-- Add transparency info box -->
            <div class="transparency-box">
                <h4>Product Information & Ordering:</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Quality-verified product</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-truck"></i>
                        <span>Delivery within 3-5 days</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure payment</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-undo"></i>
                        <span>7-day return policy</span>
                    </div>
                </div>
                <p style="margin-top: 15px;"><i class="fas fa-info-circle"></i> Your order will directly support women entrepreneurs in your community</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="product-detail">
                <div class="product-image-container">
                    <img src="../../assets/uploads/<?php echo $product['image']; ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         class="product-image">
                </div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['title']); ?></h1>
                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="seller-info">
                        <h3><i class="fas fa-store"></i> Seller Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
                    </div>

                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>

                    <form method="POST" class="order-form">
                        <div class="form-group">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" 
                                   min="1" value="1" required>
                        </div>
                        <button type="submit" class="btn-order">
                            <i class="fas fa-shopping-cart"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include('../../includes/footer.php'); ?>

</body>
</html> 