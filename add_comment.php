<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $article_id = (int)$_POST['article_id'];
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $content = trim($_POST['content']);
    $user_id = (int)$_SESSION['user_id'];

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment content cannot be empty.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check for duplicate comment
        if ($parent_id === null) {
            $stmt = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND article_id = ? AND parent_id IS NULL AND content = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->execute([$user_id, $article_id, $content]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND article_id = ? AND parent_id = ? AND content = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->execute([$user_id, $article_id, $parent_id, $content]);
        }

        if ($stmt->fetch()) {
            throw new Exception('Duplicate comment detected.');
        }

        $stmt = $pdo->prepare("INSERT INTO comments (user_id, article_id, parent_id, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $article_id, $parent_id, $content]);

        $comment_id = $pdo->lastInsertId();

        // Fetch the newly created comment
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.avatar_url,
                   (SELECT COUNT(*) FROM comment_votes WHERE comment_id = c.id AND vote_type = 1) as upvotes,
                   (SELECT COUNT(*) FROM comment_votes WHERE comment_id = c.id AND vote_type = -1) as downvotes
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        $comment['user_vote'] = 0; // The user hasn't voted on their own comment yet

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Comment added successfully.', 'comment' => $comment]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
