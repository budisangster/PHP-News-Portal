<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Both username and password are required.";
        redirect('login.php');
    }

    // Check user credentials
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on user role
        if ($user['role'] == 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('index.php');
        }
    } else {
        // Login failed
        $_SESSION['error'] = "Invalid username or password.";
        redirect('login.php');
    }
} else {
    redirect('login.php');
}
