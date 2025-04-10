<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'consumer') {
    header('Location: ../../index.php');
    exit();
}

require_once('../../config/db.php');

// Fetch all products
$result = $conn->query("SELECT p.*, u.name AS seller_name FROM products p JOIN users u ON p.user_id = u.id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Browse Products - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 30px 0;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            height: 500px; /* Fixed height for consistency */
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        .product-image {
            width: 100%;
            height: 300px; /* Fixed height for the image */
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
        }
        .btn-primary {
            background: #007B5E;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #005b46;
            transform: translateY(-2px);
        }
        .page-header {
            margin: 40px 0;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        .page-header h1 {
            color: #007B5E;
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .page-header p {
            color: #6c757d;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
        }
        .back-link i {
            font-size: 1.2rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .product-card {
            animation: fadeIn 0.5s ease-out;
            animation-fill-mode: both;
        }
        .product-grid > * {
            animation-delay: calc(var(--animation-order) * 0.1s);
        }
        .no-products {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 30px 0;
            color: #6c757d;
            font-size: 1.2rem;
        }
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
                padding: 20px 0;
            }
            .page-header h1 {
                font-size: 2rem;
            }
            .page-header p {
                font-size: 1.1rem;
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
<body class="index-page">
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="page-header">
            <h1>Browse Products</h1>
            <p>Discover unique handmade products from talented women entrepreneurs</p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="product-grid">
                <?php 
                $i = 0;
                while ($row = $result->fetch_assoc()): 
                    $i++;
                ?>
                    <div class="product-card" style="--animation-order: <?php echo $i; ?>">
                        <img src="../../assets/uploads/<?php echo $row['image']; ?>" 
                             alt="<?php echo htmlspecialchars($row['title']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="product-price">₹<?php echo number_format($row['price'], 2); ?></div>
                            <div class="product-seller">
                                <i class="fas fa-store"></i>
                                <?php echo htmlspecialchars($row['seller_name']); ?>
                            </div>
                            <div class="product-actions">
                                <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-box-open fa-3x mb-3"></i>
                <p>No products found at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>