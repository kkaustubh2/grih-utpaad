<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = intval($_POST['product_id']);
    $consumer_id = $_SESSION['user']['id'];
    $action = $_POST['action'];

    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        die("Product not found.");
    }

    if ($action === 'add_to_cart') {
        // Check if cart table exists, if not create it
        $conn->query("CREATE TABLE IF NOT EXISTS cart (
            id INT PRIMARY KEY AUTO_INCREMENT,
            consumer_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (consumer_id) REFERENCES users(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");

        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE consumer_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $consumer_id, $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows > 0) {
            // Update quantity if product already in cart
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + 1;
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            $stmt->execute();
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart (consumer_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $consumer_id, $product_id);
            $stmt->execute();
        }

        // Redirect back to product page with success message
        header("Location: product_detail.php?id=" . $product_id . "&cart=added");
        exit();

    } else if ($action === 'direct_order') {
        // Show order confirmation form
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
                    max-width: 800px;
                    margin: 40px auto;
                    padding: 30px;
                }
                .order-form {
                    background: rgba(255, 255, 255, 0.9);
                    padding: 30px;
                    border-radius: 15px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    backdrop-filter: blur(10px);
                }
                .product-summary {
                    margin-bottom: 30px;
                    padding: 20px;
                    background: rgba(248, 249, 250, 0.8);
                    border-radius: 10px;
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
                    margin-bottom: 20px;
                    font-weight: 500;
                }
                .back-link:hover {
                    color: #005b46;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <a href="product_detail.php?id=<?php echo $product_id; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Product
                </a>

                <div class="order-form">
                    <h2><i class="fas fa-shopping-bag"></i> Place Order</h2>

                    <div class="product-summary">
                        <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                        <p>Price: ₹<?php echo number_format($product['price'], 2); ?></p>
                    </div>

                    <form method="POST" action="process_order.php">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <textarea name="shipping_address" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Confirm Order
                        </button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
} else {
    header("Location: view_product.php");
    exit();
}
?>
