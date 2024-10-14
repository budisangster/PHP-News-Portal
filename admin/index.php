<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// Fetch some basic statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_articles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$total_comments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

// Fetch featured articles
$stmt = $pdo->query("SELECT a.id, a.title, a.content, a.created_at, a.thumbnail, u.username as author, c.name as category 
                     FROM articles a 
                     JOIN users u ON a.author_id = u.id 
                     JOIN categories c ON a.category_id = c.id 
                     WHERE a.is_featured = 1
                     ORDER BY a.created_at DESC 
                     LIMIT 5");
$featured_articles = $stmt->fetchAll();

// Fetch regular articles (excluding featured ones)
$stmt = $pdo->query("SELECT a.id, a.title, a.content, a.created_at, a.thumbnail, u.username as author, c.name as category 
                     FROM articles a 
                     JOIN users u ON a.author_id = u.id 
                     JOIN categories c ON a.category_id = c.id 
                     WHERE a.is_featured = 0
                     ORDER BY a.created_at DESC 
                     LIMIT 10");
$regular_articles = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<section class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p><?php echo $total_users; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Articles</h3>
        <p><?php echo $total_articles; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Comments</h3>
        <p><?php echo $total_comments; ?></p>
    </div>
</section>

<section class="admin-section">
    <h2>Recent Articles</h2>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($featured_articles as $article): ?>
                <tr>
                    <td><?php echo htmlspecialchars($article['title']); ?></td>
                    <td><?php echo htmlspecialchars($article['author']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></td>
                    <td>
                        <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="button">Edit</a>
                        <a href="delete_article.php?id=<?php echo $article['id']; ?>" class="button delete" onclick="return confirm('Are you sure you want to delete this article?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-section">
    <h2>Quick Actions</h2>
    <a href="add_article.php" class="button">Add New Article</a>
    <a href="manage_categories.php" class="button">Manage Categories</a>
    <a href="moderate_comments.php" class="button">Moderate Comments</a>
</section>

<?php include '../includes/admin_footer.php'; ?>
