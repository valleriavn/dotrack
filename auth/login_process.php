<?php
ob_start();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Corrected path to db.php
require_once __DIR__ . '/../includes/db.php';

// Validate inputs
if (empty($_POST['email']) || empty($_POST['password'])) {
    $_SESSION['error'] = 'Please fill in all fields';
    header('Location: loginandsignup.php');
    exit;
}

$email = $conn->real_escape_string($_POST['email']);

try {
    $stmt = $conn->prepare("SELECT user_id, username, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($_POST['password'], $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'last_active' => time(),
                'logged_in' => true  // This flag was missing but checked in mode.php
            ];

            // Update last_active in database
            $update = $conn->prepare("UPDATE users SET last_active = NOW() WHERE user_id = ?");
            $update->bind_param("i", $user['user_id']);
            $update->execute();

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/admin_dashboard.php");
            } else {
                header("Location: ../users/mode.php");
            }
            exit;
        } else {
            // Invalid password
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: loginandsignup.php');
            exit;
        }
    } else {
        // Account not found
        $_SESSION['error'] = 'Account not found. Please check your email or sign up';
        header('Location: loginandsignup.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = 'Login failed. Please try again.';
    header('Location: loginandsignup.php');
    exit;
}