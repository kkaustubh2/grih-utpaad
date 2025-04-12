<?php
session_start();
require_once('../../config/db.php');

// Check if already logged in as admin
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
  header('Location: dashboard.php');
  exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Check for admin credentials with join to admins table
  $stmt = $conn->prepare("
        SELECT u.*, a.is_superadmin, a.permissions 
        FROM users u 
        JOIN admins a ON u.id = a.user_id 
        WHERE u.email = ? AND u.role = 'admin' 
        LIMIT 1
    ");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      // Update last login time
      $admin_update = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
      $admin_update->bind_param("i", $user['id']);
      $admin_update->execute();

      // Log the login action
      $admin_id = $conn->query("SELECT id FROM admins WHERE user_id = {$user['id']}")->fetch_assoc()['id'];
      $log_stmt = $conn->prepare("
                INSERT INTO admin_logs (admin_id, action, table_affected, new_values) 
                VALUES (?, 'LOGIN', 'users', ?)
            ");
      $log_data = json_encode(['login_time' => date('Y-m-d H:i:s')]);
      $log_stmt->bind_param("is", $admin_id, $log_data);
      $log_stmt->execute();

      $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => 'admin',
        'is_superadmin' => $user['is_superadmin'],
        'permissions' => json_decode($user['permissions'], true)
      ];

      header('Location: dashboard.php');
      exit();
    } else {
      $error = 'Invalid password';
    }
  } else {
    $error = 'Invalid admin credentials';
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>Admin Login - Grih Utpaad</title>
  <link rel="stylesheet" href="../../assets/uploads/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Main Page Styles */
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      background: linear-gradient(135deg, rgba(248, 249, 250, 0.9) 0%, rgba(233, 245, 241, 0.9) 100%),
        url('../../assets/images/background.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: column;
    }

    .page-content {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    /* Login Card Styles */
    .login-container {
      max-width: 400px;
      width: 90%;
      padding: 20px;
      animation: fadeIn 0.5s ease-out;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 123, 94, 0.15);
      padding: 40px;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      position: relative;
      z-index: 10;
    }

    /* Footer Isolation */
    .grih-footer {
      background-color: #007B5E !important;
      color: white !important;
      padding: 30px 0 !important;
      margin-top: auto !important;
      position: relative !important;
      z-index: 5 !important;
      opacity: 1 !important;
    }

    /* Rest of your existing styles... */
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .login-header i {
      font-size: 3rem;
      color: #007B5E;
      margin-bottom: 15px;
      animation: bounceIn 0.6s ease-out;
    }

    .login-header h1 {
      color: #2c3e50;
      font-size: 2rem;
      margin: 0;
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
      border: 2px solid rgba(233, 236, 239, 0.8);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.9);
    }

    .form-control:focus {
      border-color: #007B5E;
      outline: none;
      box-shadow: 0 0 0 3px rgba(0, 123, 94, 0.1);
    }

    .btn-login {
      width: 100%;
      padding: 12px;
      background: #007B5E;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-login:hover {
      background: #005b46;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #007B5E;
      text-decoration: none;
      margin-top: 20px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .back-link:hover {
      color: #005b46;
      transform: translateX(-5px);
    }

    .error-message {
      background: rgba(248, 215, 218, 0.9);
      color: #721c24;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      backdrop-filter: blur(2px);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes bounceIn {
      0% {
        transform: scale(0);
      }

      50% {
        transform: scale(1.2);
      }

      100% {
        transform: scale(1);
      }
    }
  </style>
</head>

<body>
  <div class="page-content">
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <i class="fas fa-user-shield"></i>
          <h1>Admin Login</h1>
        </div>

        <?php if ($error): ?>
          <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group">
            <label for="username">Email Address</label>
            <input type="email" id="username" name="username" class="form-control" required
              placeholder="Enter your email">
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required
              placeholder="Enter your password">
          </div>

          <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login to Dashboard
          </button>
        </form>

        <a href="../../index.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Home
        </a>
      </div>
    </div>
  </div>

  <?php
  // Include footer with isolated styling
  $footer_path = __DIR__ . '/../../includes/footer.php';
  if (file_exists($footer_path)) {
    echo '<div style="position: relative; z-index: 5;">';
    include $footer_path;
    echo '</div>';
  }
  ?>
</body>

</html>

</html>
