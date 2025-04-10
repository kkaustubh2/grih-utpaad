<?php
session_start();

// Check if user is logged in and is a female householder
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'female_householder') {
    header('Location: ../../login.php');
    exit();
}

require_once('../../config/db.php');

// Check and add missing columns if they don't exist
$required_columns = [
    'phone' => 'VARCHAR(20)',
    'address' => 'TEXT',
    'about' => 'TEXT'
];

foreach ($required_columns as $column => $type) {
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check_column->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN $column $type");
    }
}

$user_id = $_SESSION['user']['id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $about = trim($_POST['about']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $error_message = "All fields except 'About Me' are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if email already exists for other users
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This email is already registered with another account.";
        } else {
            // Update profile
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone = ?, address = ?, about = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("sssssi", $name, $email, $phone, $address, $about, $user_id);
            
            if ($update_stmt->execute()) {
                // Update session data
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Set default values for undefined fields
$user['phone'] = isset($user['phone']) ? $user['phone'] : '';
$user['address'] = isset($user['address']) ? $user['address'] : '';
$user['about'] = isset($user['about']) ? $user['about'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Grih Utpaad</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
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
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007B5E;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn-save {
            background-color: #007B5E;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save:hover {
            background-color: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                <a href="dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php
            // Get user statistics with a simpler query that doesn't depend on status
            $stats_query = "SELECT 
                (SELECT COUNT(*) FROM products WHERE user_id = ?) as total_products,
                (SELECT COUNT(*) FROM orders o 
                 JOIN products p ON o.product_id = p.id 
                 WHERE p.user_id = ?) as total_orders,
                (SELECT COUNT(*) FROM orders o 
                 JOIN products p ON o.product_id = p.id 
                 WHERE p.user_id = ? AND o.status = 'fulfilled') as fulfilled_orders
            FROM dual";
            
            $stats_stmt = $conn->prepare($stats_query);
            $stats_stmt->bind_param("iii", $user_id, $user_id, $user_id);
            $stats_stmt->execute();
            $stats = $stats_stmt->get_result()->fetch_assoc();

            // Set active products to total products for now
            $stats['active_products'] = $stats['total_products'];
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['active_products']; ?></div>
                    <div class="stat-label">Listed Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['fulfilled_orders']; ?></div>
                    <div class="stat-label">Fulfilled Orders</div>
                </div>
            </div>

            <div class="profile-section">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['address']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="about">About Me</label>
                        <textarea id="about" name="about" class="form-control" 
                                placeholder="Tell us about yourself and your products..."><?php echo htmlspecialchars($user['about']); ?></textarea>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 