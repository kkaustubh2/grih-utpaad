<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit();
}

// Check if user is blocked
if (isset($_SESSION['user']['is_blocked']) && $_SESSION['user']['is_blocked']) {
    // Clear the session
    session_destroy();
    // Redirect to login with message
    $_SESSION['error'] = "Your account has been blocked. Please contact the administrator.";
    header('Location: /auth/login.php');
    exit();
}

// Update session data from database to ensure it's current
require_once(__DIR__ . '/../config/db.php');
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT is_blocked FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If user is blocked, end their session
if ($user && $user['is_blocked']) {
    session_destroy();
    $_SESSION['error'] = "Your account has been blocked. Please contact the administrator.";
    header('Location: /auth/login.php');
    exit();
}
?>
