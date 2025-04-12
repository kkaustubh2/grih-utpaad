<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once('../../config/db.php');

// First, let's update the products table to use category_id
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
if ($check_column->num_rows === 0) {
    // Rename the column from category to category_id and change its type
    $conn->query("ALTER TABLE products CHANGE category category_id INT NULL");
}

// Handle category actions (add/edit/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $conn->query("SELECT id FROM admins WHERE user_id = {$_SESSION['user']['id']}")->fetch_assoc()['id'];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $conn->prepare("INSERT INTO product_categories (name, description, created_by) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $_POST['name'], $_POST['description'], $admin_id);
                $stmt->execute();
                
                // Log the action
                $log_stmt = $conn->prepare("
                    INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
                    VALUES (?, 'ADD_CATEGORY', 'product_categories', ?, ?)
                ");
                $category_id = $conn->insert_id;
                $log_data = json_encode(['name' => $_POST['name'], 'description' => $_POST['description']]);
                $log_stmt->bind_param("iis", $admin_id, $category_id, $log_data);
                $log_stmt->execute();
                break;

            case 'edit':
                $stmt = $conn->prepare("UPDATE product_categories SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $_POST['name'], $_POST['description'], $_POST['category_id']);
                $stmt->execute();
                
                // Log the action
                $log_stmt = $conn->prepare("
                    INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
                    VALUES (?, 'EDIT_CATEGORY', 'product_categories', ?, ?)
                ");
                $log_data = json_encode(['name' => $_POST['name'], 'description' => $_POST['description']]);
                $log_stmt->bind_param("iis", $admin_id, $_POST['category_id'], $log_data);
                $log_stmt->execute();
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
                $stmt->bind_param("i", $_POST['category_id']);
                $stmt->execute();
                
                // Log the action
                $log_stmt = $conn->prepare("
                    INSERT INTO admin_logs (admin_id, action, table_affected, record_id) 
                    VALUES (?, 'DELETE_CATEGORY', 'product_categories', ?)
                ");
                $log_stmt->bind_param("ii", $admin_id, $_POST['category_id']);
                $log_stmt->execute();
                break;
        }
    }
}

// Fetch all categories
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count, u.name as created_by_name
    FROM product_categories c
    LEFT JOIN products p ON p.category_id = c.id
    LEFT JOIN admins a ON c.created_by = a.id
    LEFT JOIN users u ON a.user_id = u.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-info {
            flex: 1;
        }
        .category-name {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .category-description {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .category-meta {
            color: #007B5E;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .category-actions {
            display: flex;
            gap: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        .btn-add-category {
            margin-bottom: 20px;
        }
        .product-count {
            display: inline-block;
            padding: 2px 8px;
            background: #e9f5f1;
            color: #007B5E;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-left: 10px;
        }
        .transparency-box {
            background: #f8f9fa;
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
        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card {
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <div class="header">
                <h2><i class="fas fa-tags"></i> Manage Categories</h2>
            </div>

            <!-- Add transparency info box -->
            <div class="transparency-box">
                <h4>Category Management Guidelines:</h4>
                <p><i class="fas fa-info-circle"></i> Categories help organize products for easier browsing</p>
                <p><i class="fas fa-exclamation-triangle"></i> Deleting a category will not delete its products</p>
                <p><i class="fas fa-sync"></i> Products in deleted categories will need to be reassigned</p>
                <p><i class="fas fa-bell"></i> Sellers will be notified of any changes affecting their products</p>
            </div>

            <button class="btn btn-add-category" onclick="showModal('add')">
                <i class="fas fa-plus"></i> Add New Category
            </button>

            <div id="categories-container">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-info">
                            <div class="category-name">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="product-count">
                                    <?php echo $category['product_count']; ?> Products
                                </span>
                            </div>
                            <div class="category-description">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </div>
                            <div class="category-meta">
                                Created by <?php echo htmlspecialchars($category['created_by_name']); ?> on 
                                <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button class="btn" onclick="showModal('edit', <?php 
                                echo htmlspecialchars(json_encode([
                                    'id' => $category['id'],
                                    'name' => $category['name'],
                                    'description' => $category['description']
                                ])); 
                            ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($category['product_count'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" class="btn btn-block">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="hideModal()">&times;</span>
            <h2 id="modalTitle">Add New Category</h2>
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="category_id" id="categoryId">
                
                <div class="form-group">
                    <label for="categoryName">Category Name</label>
                    <input type="text" id="categoryName" name="name" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="categoryDescription">Description</label>
                    <textarea id="categoryDescription" name="description" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Category
                </button>
            </form>
        </div>
    </div>

    <script>
        function showModal(action, data = null) {
            const modal = document.getElementById('categoryModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalAction = document.getElementById('modalAction');
            const categoryId = document.getElementById('categoryId');
            const categoryName = document.getElementById('categoryName');
            const categoryDescription = document.getElementById('categoryDescription');

            modalAction.value = action;
            modalTitle.textContent = action === 'add' ? 'Add New Category' : 'Edit Category';

            if (data) {
                categoryId.value = data.id;
                categoryName.value = data.name;
                categoryDescription.value = data.description;
            } else {
                categoryId.value = '';
                categoryName.value = '';
                categoryDescription.value = '';
            }

            modal.style.display = 'flex';
        }

        function hideModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                hideModal();
            }
        }
    </script>
    <?php include('../../includes/footer.php'); ?>
</body>
</html> 