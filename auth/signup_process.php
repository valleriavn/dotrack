<?php
ob_start();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Corrected path to db.php
require_once __DIR__ . '/../includes/db.php';

// Verify db.php location
$dbFile = __DIR__ . '/../includes/db.php';
if (!file_exists($dbFile)) {
    die("Database configuration file missing!");
}

require_once $dbFile;

if (empty($_POST['signUpUsername']) || empty($_POST['signUpEmail']) || empty($_POST['signUpPassword']) || empty($_POST['signUpConfirmPassword'])) {
    header('Location: loginandsignup.php?signup=1&error=' . urlencode('Please fill in all fields'));
    ob_end_flush();
    exit();
}

$username = $_POST['signUpUsername'];
$email = $_POST['signUpEmail'];
$password = $_POST['signUpPassword'];
$confirm_password = $_POST['signUpConfirmPassword'];

if ($password !== $confirm_password) {
    header('Location: loginandsignup.php?signup=1&error=' . urlencode('Passwords do not match'));
    ob_end_flush();
    exit();
} 

// Validate password length
if (strlen($password) < 8) {
    header('Location: loginandsignup.php?signup=1&error=' . urlencode('Password must be at least 8 characters long'));
    ob_end_flush();
    exit();
}

try {
    // Check if email already exists
    $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();

    if ($result->num_rows > 0) {
        header('Location: loginandsignup.php?signup=1&error=' . urlencode('Email already exists'));
        ob_end_flush();
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user (default role is 'user')
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    $stmt->execute();

    // Get the new user ID
    $user_id = $stmt->insert_id;

    // Start session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'user';

    // Redirect to user dashboard
header("Location: ../users/mode.php");
ob_end_flush();
exit();

} catch (Exception $e) {
    error_log("Signup error: " . $e->getMessage());
    header('Location: loginandsignup.php?signup=1&error=' . urlencode('Registration failed. Please try again.'));
    ob_end_flush();
    exit();
}