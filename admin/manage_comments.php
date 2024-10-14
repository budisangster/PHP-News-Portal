<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// At the top of manage_comments.php, after starting the session
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']);
}
?>

<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = $_POST['comment_id'];
    $action = $_POST['action'];

    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $comment_id]);
    } elseif ($action == 'delete') {
        try {
            // Start a transaction
            $pdo->beginTransaction();

            // First, delete related records in the comment_votes table
            $stmt = $pdo->prepare("DELETE FROM comment_votes WHERE comment_id = ?");
            $stmt->execute([$comment_id]);

            // Then, delete the comment
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);

            // Commit the transaction
            $pdo->commit();

            $_SESSION['success_message'] = "Comment deleted successfully.";
        } catch (PDOException $e) {
            // Rollback the transaction if an error occurred
            $pdo->rollBack();
            $_SESSION['error_message'] = "Failed to delete the comment: " . $e->getMessage();
        }
    }

    // Redirect to refresh the page and prevent form resubmission
    header("Location: manage_comments.php");
    exit();
}

// Fetch all comments
$stmt = $pdo->query("SELECT c.*, a.title as article_title, u.username 
                     FROM comments c 
                     JOIN articles a ON c.article_id = a.id 
                     JOIN users u ON c.user_id = u.id 
                     ORDER BY c.created_at DESC");
$comments = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<h1>Manage Comments</h1>

<?php if ($error): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?php echo $success; ?></p>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>User</th>
            <th>Article</th>
            <th>Comment</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($comments as $comment): ?>
            <tr>
                <td><?php echo htmlspecialchars($comment['username']); ?></td>
                <td><?php echo htmlspecialchars($comment['article_title']); ?></td>
                <td><?php echo htmlspecialchars(substr($comment['content'], 0, 50)) . '...'; ?></td>
                <td><?php echo htmlspecialchars($comment['status']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <select name="new_status">
                            <option value="approved" <?php echo $comment['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="pending" <?php echo $comment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="rejected" <?php echo $comment['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status" class="button small">Update Status</button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <button type="submit" name="delete_comment" class="button small delete" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/admin_footer.php'; ?>
