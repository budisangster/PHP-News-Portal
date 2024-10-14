<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = (int)$_POST['comment_id'];

    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?");
    if ($stmt->execute([$comment_id, $comment_id])) {
        echo json_encode(['success' => true, 'message' => 'Comment removed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove comment.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
