<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$comment_id = $_GET['id'];

// Fetch the comment
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
$stmt->execute([$comment_id, $_SESSION['user_id']]);
$comment = $stmt->fetch();

if (!$comment) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update'])) {
        $content = $_POST['content'];
        if (update_comment($pdo, $comment_id, $_SESSION['user_id'], $content)) {
            $success_message = "Comment updated successfully!";
            $comment['content'] = $content; // Update the comment content
        } else {
            $error_message = "Failed to update comment.";
        }
    } elseif (isset($_POST['delete'])) {
        if (delete_comment($pdo, $comment_id, $_SESSION['user_id'])) {
            header("Location: profile.php");
            exit();
        } else {
            $error_message = "Failed to delete comment.";
        }
    }
}

include 'includes/header.php';
?>

<h1>Edit Comment</h1>

<?php if (isset($success_message)): ?>
    <p class="success"><?php echo $success_message; ?></p>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <p class="error"><?php echo $error_message; ?></p>
<?php endif; ?>

<form method="POST">
    <label for="content">Comment:</label>
    <textarea id="content" name="content" required><?php echo htmlspecialchars($comment['content']); ?></textarea>

    <button type="submit" name="update">Update Comment</button>
    <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this comment?')">Delete Comment</button>
</form>

<p><a href="profile.php">Back to Profile</a></p>

<?php include 'includes/footer.php'; ?>
