<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to vote.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = (int)$_POST['comment_id'];
    $vote_type = (int)$_POST['vote_type'];
    $user_id = (int)$_SESSION['user_id'];

    if ($vote_type !== 1 && $vote_type !== -1) {
        echo json_encode(['success' => false, 'message' => 'Invalid vote type.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if user has already voted on this comment
        $stmt = $pdo->prepare("SELECT * FROM comment_votes WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$user_id, $comment_id]);
        $existing_vote = $stmt->fetch();

        if ($existing_vote) {
            if ($existing_vote['vote_type'] == $vote_type) {
                // Remove the vote if it's the same type
                $stmt = $pdo->prepare("DELETE FROM comment_votes WHERE user_id = ? AND comment_id = ?");
                $stmt->execute([$user_id, $comment_id]);
                $new_vote_type = 0;
            } else {
                // Update the vote if it's a different type
                $stmt = $pdo->prepare("UPDATE comment_votes SET vote_type = ? WHERE user_id = ? AND comment_id = ?");
                $stmt->execute([$vote_type, $user_id, $comment_id]);
                $new_vote_type = $vote_type;
            }
        } else {
            // Insert a new vote
            $stmt = $pdo->prepare("INSERT INTO comment_votes (user_id, comment_id, vote_type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $comment_id, $vote_type]);
            $new_vote_type = $vote_type;
        }

        // Get the new vote count
        $stmt = $pdo->prepare("
            SELECT (SELECT COUNT(*) FROM comment_votes WHERE comment_id = ? AND vote_type = 1) -
                   (SELECT COUNT(*) FROM comment_votes WHERE comment_id = ? AND vote_type = -1) as vote_count
        ");
        $stmt->execute([$comment_id, $comment_id]);
        $new_vote_count = $stmt->fetchColumn();

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'new_vote_count' => $new_vote_count,
            'user_vote' => $new_vote_type
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your vote.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);