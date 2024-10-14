<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $article_id = $_GET['id'];
    
    // Delete the article
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    if ($stmt->execute([$article_id])) {
        $_SESSION['success_message'] = "Article deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete the article.";
    }
}

header("Location: manage_articles.php");
exit();
