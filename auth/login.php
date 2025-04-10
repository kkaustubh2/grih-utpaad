<?php
require_once('../config/db.php');
session_start();

$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;

        // Redirect based on role
        if ($user['role'] === 'webmaster') {
            header("Location: ../dashboards/webmaster/index.php");
        } elseif ($user['role'] === 'female_householder') {
            header("Location: ../dashboards/female_householders/index.php");
        } else {
            header("Location: ../dashboards/consumers/index.php");
        }
        exit;
    } else {
        $errors[] = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Grih Utpaad</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="index-page">

<div class="container">
    <div class="card" style="max-width: 500px; margin: 40px auto;">
        <h2 style="text-align: center; color: #007B5E;">Login to Grih Utpaad</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="card" style="background: #ffe6e6; color: #dc3545; margin-bottom: 20px;">
                <?php echo implode("<br>", $errors); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
            <div class="card" style="background: #e6ffe6; color: #28a745; margin-bottom: 20px;">
                Registration successful. Please login.
            </div>
        <?php endif; ?>

        <form method="POST" action="" style="margin-top: 20px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-envelope" style="color: #007B5E;"></i> Email:
                </label>
                <input type="email" name="email" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-lock" style="color: #007B5E;"></i> Password:
                </label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <a href="../index.php" style="display: inline-block; margin-top: 10px;">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</div>

</body>
</html>
