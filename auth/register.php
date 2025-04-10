<?php
require_once('../config/db.php');

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $errors[] = "All fields are required.";
    } else {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
            if ($stmt->execute()) {
                header("Location: login.php?register=success");
                exit;
            } else {
                $errors[] = "Registration failed. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Grih Utpaad</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="index-page">

<div class="container">
    <div class="card" style="max-width: 500px; margin: 40px auto;">
        <h2 style="text-align: center; color: #007B5E;">Register on Grih Utpaad</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="card" style="background: #ffe6e6; color: #dc3545; margin-bottom: 20px;">
                <?php echo implode("<br>", $errors); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" style="margin-top: 20px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-user" style="color: #007B5E;"></i> Name:
                </label>
                <input type="text" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-envelope" style="color: #007B5E;"></i> Email:
                </label>
                <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-lock" style="color: #007B5E;"></i> Password:
                </label>
                <input type="password" name="password" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-user-tag" style="color: #007B5E;"></i> Role:
                </label>
                <select name="role" required style="background-color: white;">
                    <option value="">Select your role</option>
                    <option value="female_householder" <?php echo (isset($_POST['role']) && $_POST['role'] === 'female_householder') ? 'selected' : ''; ?>>Female Householder</option>
                    <option value="consumer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'consumer') ? 'selected' : ''; ?>>Consumer</option>
                </select>
            </div>

            <button type="submit" class="btn" style="width: 100%;">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p>Already registered? <a href="login.php">Login here</a></p>
            <a href="../index.php" style="display: inline-block; margin-top: 10px;">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</div>

</body>
</html>
