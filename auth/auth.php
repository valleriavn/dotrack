<?php
date_default_timezone_set('Asia/Manila');
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login to access this page';
    header('Location: loginandsignup.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Verify session validity against database
$check_session = $conn->prepare("SELECT user_id, username, role, last_active FROM users WHERE user_id = ?");
$check_session->bind_param("i", $user_id);
$check_session->execute();
$result = $check_session->get_result();

if ($result->num_rows === 0) {
    // User ID in session doesn't exist in database
    session_unset();
    session_destroy();
    $_SESSION['error'] = 'Your session is invalid. Please login again.';
    header('Location: loginandsignup.php');
    exit;
}

$user = $result->fetch_assoc();

// Check session timeout (30 minutes inactivity)
$last_active = strtotime($user['last_active']);
if (time() - $last_active > 1800) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = 'Your session has expired due to inactivity';
    header('Location: loginandsignup.php');
    exit;
}

// Update last active time
$update_time = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
$update_time->bind_param("i", $user_id);
$update_time->execute();

// Set user data in session for easy access
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['last_active'] = $user['last_active'];

// CSRF token generation for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// For admin pages, verify admin role
$current_page = basename($_SERVER['PHP_SELF']);
$admin_pages = ['admin_dashboard.php', 'manage_users.php', 'manage_groups.php', 'manage_projects.php', 'database_backup.php', 'system_logs.php'];

if (in_array($current_page, $admin_pages) && $_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}
?>