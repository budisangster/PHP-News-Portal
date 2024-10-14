<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Fetch articles
$stmt = $pdo->prepare("SELECT a.id, a.title, a.created_at, u.username as author, c.name as category 
                       FROM articles a 
                       JOIN users u ON a.author_id = u.id 
                       JOIN categories c ON a.category_id = c.id 
                       ORDER BY a.created_at DESC 
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Get total number of articles
$total_articles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

include '../includes/admin_header.php';
?>

<h1>Manage Articles</h1>

<p><a href="add_article.php" class="button">Add New Article</a></p>

<table class="admin-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($articles as $article): ?>
            <tr>
                <td><?php echo htmlspecialchars($article['title']); ?></td>
                <td><?php echo htmlspecialchars($article['author']); ?></td>
                <td><?php echo htmlspecialchars($article['category']); ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></td>
                <td>
                    <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="button small">Edit</a>
                    <a href="delete_article.php?id=<?php echo $article['id']; ?>" class="button small delete" onclick="return confirm('Are you sure you want to delete this article?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="current-page"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>
