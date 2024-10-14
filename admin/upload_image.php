<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $upload_dir = '../uploads/';
    $file_name = uniqid() . '_' . $_FILES['image']['name'];
    $upload_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        $relative_path = '/uploads/' . $file_name;
        echo json_encode(['success' => true, 'url' => $relative_path]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No image file received']);
}
