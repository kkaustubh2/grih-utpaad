<?php
require_once('../../includes/auth.php');

// Additional role check for consumer
if ($_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

// Get cart count for header
$cart_count_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE consumer_id = ?");
$cart_count_query->bind_param("i", $_SESSION['user']['id']);
$cart_count_query->execute();
$cart_count = $cart_count_query->get_result()->fetch_assoc()['count'];

// Initialize variables
$products = [];
$total = 0;

// Handle different order types
if (isset($_GET['type']) && $_GET['type'] === 'cart') {
    // Fetch cart items with product details
    $query = "SELECT c.*, p.id as id, p.title, p.price, p.image, u.name as seller_name 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              JOIN users u ON p.user_id = u.id 
              WHERE c.consumer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    while ($item = $cart_items->fetch_assoc()) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $products[] = $item;
        $total += $item['subtotal'];
    }
} elseif (isset($_GET['id'])) {
    // Fetch single product details
    $query = "SELECT p.*, u.name as seller_name 
              FROM products p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.id = ? AND p.is_approved = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $product['quantity'] = 1;
        $product['subtotal'] = $product['price'];
        $products[] = $product;
        $total = $product['price'];
    } else {
        header("Location: view_product.php");
        exit();
    }
} else {
    header("Location: view_product.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Place Order - Grih Utpaad</title>
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
        .order-form {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .product-list {
            margin-bottom: 30px;
        }
        .product-item {
            display: grid;
            grid-template-columns: 100px 2fr 1fr 1fr;
            gap: 20px;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .product-info h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        .seller-info {
            color: #666;
            font-size: 0.9rem;
        }
        .quantity {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .price {
            color: #007B5E;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .order-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .total {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
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
            margin-bottom: 20px;
        }
        .back-link:hover {
            color: #005b46;
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
        .cart-count {
            background: white;
            color: #007B5E;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
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
            <a href="view_cart.php" class="cart-btn">
                <i class="fas fa-shopping-cart"></i>
                Cart
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <a href="<?php echo isset($_GET['type']) ? 'view_cart.php' : 'view_product.php'; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> 
            <?php echo isset($_GET['type']) ? 'Back to Cart' : 'Back to Products'; ?>
        </a>

        <div class="order-form">
            <h1 style="margin-top: 0; color: #2c3e50;">Place Order</h1>

            <div class="product-list">
                <?php foreach ($products as $product): ?>
                    <div class="product-item">
                        <img src="../../assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['title']) ?>" 
                             class="product-image">
                        
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['title']) ?></h3>
                            <div class="seller-info">
                                Sold by: <?= htmlspecialchars($product['seller_name']) ?>
                            </div>
                        </div>

                        <div class="quantity">
                            Quantity: <?= $product['quantity'] ?>
                        </div>

                        <div class="price">
                            ₹<?= number_format($product['subtotal'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="process_order.php">
                <?php foreach ($products as $product): ?>
                    <input type="hidden" name="products[]" value="<?= $product['id'] ?>">
                    <input type="hidden" name="quantities[]" value="<?= $product['quantity'] ?>">
                <?php endforeach; ?>
                
                <div class="form-group">
                    <label class="form-label">Shipping Address</label>
                    <textarea name="shipping_address" class="form-control" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" required>
                </div>

                <div class="order-summary">
                    <div class="total">
                        Total Amount: ₹<?= number_format($total, 2) ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Confirm Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 