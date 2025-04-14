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
        // Check if user is blocked
        if ($user['is_blocked']) {
            $errors[] = "Your account has been blocked. Please contact the administrator.";
        } else {
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
        }
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
    <style>
        .login-container {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            margin: 40px auto;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #007B5E;
            font-size: 2.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 1rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #007B5E;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px 20px;
            background: #007B5E;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #005b46;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #6c757d;
            font-size: 1rem;
        }

        .register-link a {
            color: #007B5E;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .home-link {
            text-align: center;
            margin-top: 15px;
        }

        .home-link a {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .home-link a:hover {
            color: #007B5E;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body class="index-page">

<div class="container">
    <div class="login-container">
        <div class="login-header">
            <h1>
                <i class="fas fa-leaf"></i>
                Grih Utpaad
            </h1>
            <p>Welcome back! Please login to continue.</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo implode("<br>", $errors); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Registration successful. Please login.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" required 
                           placeholder="Enter your email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        
        <div class="home-link">
            <a href="../index.php">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>
