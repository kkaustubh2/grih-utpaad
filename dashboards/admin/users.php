<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied.");
}

$users = $conn->query("SELECT * FROM users WHERE role != 'admin'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - All Users</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f5f1 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .content-wrapper {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 94, 0.1);
            padding: 30px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header h2 {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .transparency-box i {
            color: #007B5E;
        }
        .role-guide {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .role-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: 6px;
            background: white;
            font-size: 0.9rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #007B5E;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #005b46;
            transform: translateX(-5px);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .role-consumer {
            background: #e3f2fd;
            color: #0d47a1;
        }
        .role-female_householder {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="content-wrapper">
            <div class="header">
                <h2><i class="fas fa-users"></i> All Users</h2>
            </div>

            <!-- Add transparency info box -->
            <div class="transparency-box">
                <h4>User Management Overview:</h4>
                <div class="role-guide">
                    <div class="role-item">
                        <i class="fas fa-shopping-bag" style="color: #0d47a1;"></i>
                        <span>Consumers: Regular buyers</span>
                    </div>
                    <div class="role-item">
                        <i class="fas fa-store" style="color: #7b1fa2;"></i>
                        <span>Female Householders: Sellers</span>
                    </div>
                </div>
                <p><i class="fas fa-info-circle"></i> Monitor user activities and manage accounts</p>
                <p><i class="fas fa-exclamation-triangle"></i> Account deletion is permanent</p>
                <p><i class="fas fa-shield-alt"></i> Regular review helps prevent misuse</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <span class="role-badge role-<?= strtolower($row['role']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $row['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="delete_user.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include('../../includes/footer.php'); ?>
</body>
</html>
