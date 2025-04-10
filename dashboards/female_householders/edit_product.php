<?php
session_start();

// Check if user is logged in and is a female householder
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'female_householder') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

$success = '';
$errors = [];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $_SESSION['user']['id']);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: my_products.php');
    exit();
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM product_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category'];
    $price = (float)$_POST['price'];
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0.";
    }
    
    // Handle image upload if new image is provided
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
        } else {
            $imageName = time() . '_' . $_FILES['image']['name'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], "../../assets/uploads/" . $imageName)) {
                // Delete old image if exists
                if ($product['image'] && file_exists("../../assets/uploads/" . $product['image'])) {
                    unlink("../../assets/uploads/" . $product['image']);
                }
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } else {
        $imageName = $product['image'];
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE products SET title = ?, description = ?, category_id = ?, price = ?, image = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssidsii", $title, $description, $category_id, $price, $imageName, $product_id, $_SESSION['user']['id']);
        if ($stmt->execute()) {
            $success = "Product updated successfully!";
            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $product_id, $_SESSION['user']['id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update product.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <a href="my_products.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to My Products
        </a>

        <div class="card">
            <h2><i class="fas fa-edit"></i> Edit Product</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($product['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" id="price" name="price" step="0.01" required 
                           value="<?php echo $product['price']; ?>">
                </div>

                <div class="form-group">
                    <label for="image">Image</label>
                    <?php if ($product['image']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../../assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="Current product image" style="max-width: 200px;">
                            <p style="color: #6c757d; font-size: 0.9rem;">
                                Current image. Upload a new one to change it.
                            </p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </form>
        </div>
    </div>
</body>
</html>
