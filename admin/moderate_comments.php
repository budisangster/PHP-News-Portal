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

// Handle comment moderation actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_id = $_POST['comment_id'];
    $action = $_POST['action'];

    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $comment_id]);
    } elseif ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
    }

    // Redirect to refresh the page and prevent form resubmission
    header("Location: moderate_comments.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch comments
$stmt = $pdo->prepare("SELECT c.id, c.content, c.created_at, c.status, u.username, a.title as article_title
                       FROM comments c
                       JOIN users u ON c.user_id = u.id
                       JOIN articles a ON c.article_id = a.id
                       ORDER BY c.created_at DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();

// Get total number of comments
$stmt = $pdo->query("SELECT COUNT(*) FROM comments");
$total_comments = $stmt->fetchColumn();
$total_pages = ceil($total_comments / $per_page);

include '../includes/admin_header.php';
?>

<h1>Moderate Comments</h1>

<table>
    <thead>
        <tr>
            <th>User</th>
            <th>Article</th>
            <th>Comment</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($comments as $comment): ?>
            <tr>
                <td><?php echo htmlspecialchars($comment['username']); ?></td>
                <td><?php echo htmlspecialchars($comment['article_title']); ?></td>
                <td><?php echo htmlspecialchars($comment['content']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                <td><?php echo ucfirst($comment['status']); ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <?php if ($comment['status'] != 'approved'): ?>
                            <button type="submit" name="action" value="approve">Approve</button>
                        <?php endif; ?>
                        <?php if ($comment['status'] != 'rejected'): ?>
                            <button type="submit" name="action" value="reject">Reject</button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>">Previous</a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
            <span><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>">Next</a>
    <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>
