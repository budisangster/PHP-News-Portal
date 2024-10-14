<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['new_username']);
    $new_email = trim($_POST['new_email']);
    $new_password = $_POST['new_password'];

    // Validate input
    if (empty($new_username) || empty($new_email)) {
        $_SESSION['error'] = "Username and email are required.";
        redirect('profile.php');
    }

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        redirect('profile.php');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update username and email
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$new_username, $new_email, $user_id]);

        // Update password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
        }

        // Handle profile picture upload
        if (isset($_FILES['new_avatar']) && $_FILES['new_avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['new_avatar']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = uniqid() . '.' . $filetype;
                $upload_dir = 'uploads/avatars/';
                $upload_path = $upload_dir . $new_filename;

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['new_avatar']['tmp_name'], $upload_path)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                        $stmt->execute([$upload_path, $user_id]);
                    } catch (PDOException $e) {
                        // If the column doesn't exist, create it and try again
                        if ($e->getCode() == '42S22') {
                            $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
                            $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                            $stmt->execute([$upload_path, $user_id]);
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    $upload_error = error_get_last();
                    throw new Exception("Failed to upload file. Error: " . $upload_error['message']);
                }
            } else {
                throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed));
            }
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = "Profile updated successfully.";
        redirect('profile.php');
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        redirect('profile.php');
    }
} else {
    redirect('profile.php');
}
