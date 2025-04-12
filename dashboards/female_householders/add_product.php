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
    <style>
        .form-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            margin: 0 0 30px 0;
            color: #333;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #007B5E;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 94, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .file-input {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            width: 100%;
            cursor: pointer;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #007B5E;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #ffe6e6;
            color: #dc3545;
            border: 1px solid #ffcccc;
        }
        
        .alert-success {
            background: #e6ffe6;
            color: #28a745;
            border: 1px solid #ccffcc;
        }
    </style>
</head>
<body class="index-page">

<div class="container">
    <div class="form-container">
        <h2 class="form-title">
            <i class="fas fa-plus-circle" style="color: #007B5E;"></i>
            Add New Product / Skill
        </h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php echo implode("<br>", $errors); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-tag" style="color: #007B5E;"></i> Title
                </label>
                <input type="text" name="title" class="form-control" required 
                    value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-align-left" style="color: #007B5E;"></i> Description
                </label>
                <textarea name="description" class="form-control" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-list" style="color: #007B5E;"></i> Category
                </label>
                <select name="category" class="form-control" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-rupee-sign" style="color: #007B5E;"></i> Price (₹)
                </label>
                <input type="number" step="0.01" name="price" class="form-control" required 
                    value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-image" style="color: #007B5E;"></i> Upload Image
                </label>
                <input type="file" name="image" accept="image/*" required class="file-input">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Product / Skill
                </button>
            </div>
        </form>
    </div>
</div>

<?php include('../../includes/footer.php'); ?>

</body>
</html>
