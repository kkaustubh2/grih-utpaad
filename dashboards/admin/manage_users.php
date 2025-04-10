<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once('../../config/db.php');

// Handle user actions (block/unblock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $admin_id = $conn->query("SELECT id FROM admins WHERE user_id = {$_SESSION['user']['id']}")->fetch_assoc()['id'];

    if ($action === 'block' || $action === 'unblock') {
        $is_blocked = ($action === 'block') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("ii", $is_blocked, $user_id);
        $stmt->execute();

        // Log the action
        $log_stmt = $conn->prepare("
            INSERT INTO admin_logs (admin_id, action, table_affected, record_id, new_values) 
            VALUES (?, ?, 'users', ?, ?)
        ");
        $action_type = $action === 'block' ? 'BLOCK_USER' : 'UNBLOCK_USER';
        $log_data = json_encode(['is_blocked' => $is_blocked]);
        $log_stmt->bind_param("isis", $admin_id, $action_type, $user_id, $log_data);
        $log_stmt->execute();
    }
}

// First, let's add the is_blocked column if it doesn't exist
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_blocked'");
if ($check_column->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0");
}

// Fetch users with role counts
$users = $conn->query("
    SELECT u.*, 
           COALESCE(u.is_blocked, 0) as is_blocked,
           COUNT(DISTINCT p.id) as total_products,
           COUNT(DISTINCT CASE 
               WHEN u.role = 'consumer' THEN o.id 
               WHEN u.role = 'female_householder' THEN o2.id 
           END) as total_orders,
           COALESCE(SUM(CASE 
               WHEN u.role = 'consumer' THEN o.total_price 
               WHEN u.role = 'female_householder' THEN o2.total_price 
           END), 0) as total_sales
    FROM users u
    LEFT JOIN products p ON u.id = p.user_id
    LEFT JOIN orders o ON u.id = o.consumer_id
    LEFT JOIN products p2 ON p2.user_id = u.id
    LEFT JOIN orders o2 ON o2.product_id = p2.id
    WHERE u.role NOT IN ('admin', 'webmaster')
    GROUP BY u.id
    ORDER BY u.registered_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/uploads/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .stats-badge {
            background: #e9f5f1;
            color: #007B5E;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-left: 8px;
        }
        .user-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }
        .action-btn {
            padding: 6px 12px;
            font-size: 0.9rem;
            margin: 0 4px;
        }
        .btn-block {
            background-color: #dc3545;
        }
        .btn-block:hover {
            background-color: #c82333;
        }
        .btn-unblock {
            background-color: #28a745;
        }
        .btn-unblock:hover {
            background-color: #218838;
        }
        .user-role {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            background: #e9ecef;
            color: #495057;
            margin-right: 8px;
        }
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-input {
            flex: 1;
            max-width: 300px;
        }
        .filter-select {
            width: auto;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <div class="header">
                <h2><i class="fas fa-users"></i> Manage Users</h2>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search users...">
                <select id="roleFilter" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="female_householder">Female Householder</option>
                    <option value="consumer">Consumer</option>
                </select>
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Statistics</th>
                        <th>Joined On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($user['email']); ?></small>
                            </td>
                            <td>
                                <span class="user-role">
                                    <i class="fas <?php echo $user['role'] === 'female_householder' ? 'fa-store' : 'fa-user'; ?>"></i>
                                    <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="user-status <?php echo $user['is_blocked'] ? 'status-blocked' : 'status-active'; ?>">
                                    <?php echo $user['is_blocked'] ? 'Blocked' : 'Active'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'female_householder'): ?>
                                    <span class="stats-badge">
                                        <i class="fas fa-box"></i> <?php echo $user['total_products']; ?> Products
                                    </span>
                                <?php endif; ?>
                                <span class="stats-badge">
                                    <i class="fas fa-shopping-cart"></i> <?php echo $user['total_orders']; ?> Orders
                                </span>
                                <span class="stats-badge">
                                    <i class="fas fa-rupee-sign"></i> <?php echo number_format($user['total_sales'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($user['registered_at'])); ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php if ($user['is_blocked']): ?>
                                        <input type="hidden" name="action" value="unblock">
                                        <button type="submit" class="btn action-btn btn-unblock">
                                            <i class="fas fa-unlock"></i> Unblock
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="block">
                                        <button type="submit" class="btn action-btn btn-block">
                                            <i class="fas fa-ban"></i> Block
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const roleValue = roleFilter.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const name = row.querySelector('td:first-child').textContent.toLowerCase();
                const role = row.querySelector('.user-role').textContent.toLowerCase();
                const status = row.querySelector('.user-status').textContent.toLowerCase();

                const matchesSearch = name.includes(searchTerm);
                const matchesRole = !roleValue || role.includes(roleValue);
                const matchesStatus = !statusValue || status.includes(statusValue);

                row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterTable);
        roleFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html> 