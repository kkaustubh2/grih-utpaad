<?php
require_once('../../includes/auth.php'); // session check
require_once('../../config/db.php');

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $price = $_POST['price'];

    $user_id = $_SESSION['user']['id'];

    // Upload image
    $imageName = '';
    if ($_FILES['image']['name']) {
        $targetDir = "../../assets/uploads/";
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($imageFileType, $allowedTypes)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            // Successfully uploaded
        } else {
            $errors[] = "Failed to upload image.";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (user_id, title, description, category_id, price, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issids", $user_id, $title, $description, $category, $price, $imageName);
        if ($stmt->execute()) {
            $success = "Product/Skill added successfully!";
        } else {
            $errors[] = "Failed to add product.";
        }
    }
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM product_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product/Skill - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="index-page">

<div class="container">
    <div class="card" style="max-width: 800px; margin: 40px auto;">
        <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-plus-circle" style="color: #007B5E;"></i>
            Add New Product / Skill
        </h2>

        <?php if (!empty($errors)): ?>
            <div class="card" style="background: #ffe6e6; color: #dc3545; margin-bottom: 20px;">
                <?php echo implode("<br>", $errors); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="card" style="background: #e6ffe6; color: #28a745; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-tag" style="color: #007B5E;"></i> Title:
                </label>
                <input type="text" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-align-left" style="color: #007B5E;"></i> Description:
                </label>
                <textarea name="description" rows="4" required style="width: 100%; resize: vertical;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select name="category" id="category" class="form-control" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-rupee-sign" style="color: #007B5E;"></i> Price (₹):
                </label>
                <input type="number" step="0.01" name="price" required value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-image" style="color: #007B5E;"></i> Upload Image:
                </label>
                <input type="file" name="image" accept="image/*" required style="padding: 10px; border: 2px dashed #e9ecef; border-radius: 8px; width: 100%;">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="index.php" class="btn" style="background-color: #6c757d;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn">
                    <i class="fas fa-plus"></i> Add Product / Skill
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
