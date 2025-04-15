<?php
session_start();
require_once('config/db.php');

// Create categories table if it doesn't exist
$create_categories = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_categories)) {
    die("Error creating categories table: " . $conn->error);
}

// Insert default categories if none exist
$check_categories = $conn->query("SELECT COUNT(*) as count FROM categories");
$category_count = $check_categories->fetch_assoc()['count'];

if ($category_count == 0) {
    $default_categories = [
        'Food & Beverages',
        'Handicrafts',
        'Clothing',
        'Home Decor',
        'Jewelry',
        'Art & Paintings',
        'Organic Products',
        'Beauty & Health'
    ];
    
    foreach ($default_categories as $category) {
        $category = mysqli_real_escape_string($conn, $category);
        $conn->query("INSERT INTO categories (name) VALUES ('$category')");
    }
}

// Create products table if it doesn't exist
$create_products = "CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_approved TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES product_categories(id)
)";

if (!$conn->query($create_products)) {
    die("Error creating products table: " . $conn->error);
}

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000000;

// Check if products table exists and has the required columns
$check_table = $conn->query("SHOW TABLES LIKE 'products'");
if ($check_table->num_rows == 0) {
    die("Products table does not exist");
}

// Base query with simpler WHERE clause initially
$query = "SELECT p.*, pc.name as category_name, u.name as seller_name, u.id as seller_id 
          FROM products p 
          LEFT JOIN product_categories pc ON p.category_id = pc.id
          LEFT JOIN users u ON p.user_id = u.id
          WHERE 1=1";

// Apply filters
if (!empty($category)) {
    $category = mysqli_real_escape_string($conn, $category);
    $query .= " AND pc.name = '$category'";
}

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
}

if (isset($min_price) && isset($max_price)) {
    $query .= " AND p.price BETWEEN $min_price AND $max_price";
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $query .= " ORDER BY p.views DESC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

// Execute query with error handling
$result = $conn->query($query);
if (!$result) {
    die("Error executing query: " . $conn->error);
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT DISTINCT name FROM product_categories ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products - Grih Utpaad</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            background-image: url('assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            position: relative;
            z-index: 2;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image-container {
            width: 100%;
            height: 300px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            color: #007B5E;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .product-name {
            color: #2c3e50;
            font-size: 18px;
            margin: 0 0 10px;
        }

        .product-price {
            color: #007B5E;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 15px;
        }

        .product-seller {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .view-btn {
            background: #007B5E;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .view-btn:hover {
            background: #005b46;
        }

        .no-products {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 15px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1 style="color: #007B5E; text-align: center; margin-bottom: 30px;">Our Products</h1>

    <form class="filters" method="GET">
        <div class="filter-group">
            <label for="search">Search</label>
            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products...">
        </div>

        <div class="filter-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">All Categories</option>
                <?php
                while ($cat = $categories->fetch_assoc()) {
                    $selected = $category === $cat['name'] ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($cat['name']) . "' $selected>" . 
                         htmlspecialchars($cat['name']) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="sort">Sort By</label>
            <select id="sort" name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="min_price">Min Price</label>
            <input type="number" id="min_price" name="min_price" value="<?= $min_price ?>" min="0">
        </div>

        <div class="filter-group">
            <label for="max_price">Max Price</label>
            <input type="number" id="max_price" name="max_price" value="<?= $max_price ?>" min="0">
        </div>

        <div class="filter-group" style="flex: 0;">
            <label>&nbsp;</label>
            <button type="submit" class="view-btn">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </div>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="products-grid">
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image-container">
                        <?php if (!empty($product['image'])): ?>
                            <img src="assets/uploads/<?= htmlspecialchars($product['image']) ?>" 
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
                        <div class="product-category">
                            <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                        </div>
                        <h3 class="product-name">
                            <?= htmlspecialchars($product['title'] ?? 'Unnamed Product') ?>
                        </h3>
                        <div class="product-price">
                            ₹<?= number_format($product['price'] ?? 0, 2) ?>
                        </div>
                        <div class="seller-info">
                            <h3><i class="fas fa-store"></i> Seller Information</h3>
                            <p>Sold by: <?= htmlspecialchars($product['seller_name'] ?? 'Unknown Seller') ?></p>
                        </div>
                        <?php if (isset($product['id'])): ?>
                            <a href="product_detail.php?id=<?= $product['id'] ?>" class="view-btn">
                                View Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open" style="font-size: 48px; color: #007B5E; margin-bottom: 20px;"></i>
            <h2>No Products Found</h2>
            <p>Try adjusting your filters or search terms.</p>
        </div>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html> 