<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_picture'];

    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
        redirect('profile.php');
    }

    if ($file['size'] > $max_size) {
        $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
        redirect('profile.php');
    }

    // Generate a unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $upload_dir = 'uploads/profile_pictures/';
    $upload_path = $upload_dir . $filename;

    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update user's profile picture in the database
        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$upload_path, $user_id]);

        $_SESSION['success'] = "Profile picture updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to upload profile picture. Please try again.";
    }

    redirect('profile.php');
} else {
    redirect('profile.php');
}
