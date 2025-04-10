<?php
require_once('../../includes/auth.php');
require_once('../../config/db.php');

if ($_SESSION['user']['role'] !== 'admin') {
    die("Access Denied.");
}

$users = $conn->query("SELECT * FROM users WHERE role != 'admin'");
?>

<h2>👥 All Users</h2>
<table border="1" cellpadding="10">
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>
    <?php while ($row = $users->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= $row['role'] ?></td>
            <td>
                <a href="delete_user.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">❌ Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
