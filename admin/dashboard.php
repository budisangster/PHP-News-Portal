<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header('Location: /login.php');
    exit();
}

// Fetch some basic statistics for the dashboard
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_articles = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$total_comments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

// Fetch recent articles
$stmt = $pdo->query("SELECT id, title, created_at FROM articles ORDER BY created_at DESC LIMIT 5");
$recent_articles = $stmt->fetchAll();

// Fetch recent comments
$stmt = $pdo->query("
    SELECT c.id, c.content, c.created_at, u.username, a.title as article_title 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    JOIN articles a ON c.article_id = a.id 
    ORDER BY c.created_at DESC LIMIT 5
");
$recent_comments = $stmt->fetchAll();

$page_title = "Admin Dashboard";
include '../includes/admin_header.php';
?>

<h1>Admin Dashboard</h1>

<div class="dashboard-stats">
    <div class="stat-box">
        <h3>Total Users</h3>
        <p><?php echo $total_users; ?></p>
    </div>
    <div class="stat-box">
        <h3>Total Articles</h3>
        <p><?php echo $total_articles; ?></p>
    </div>
    <div class="stat-box">
        <h3>Total Comments</h3>
        <p><?php echo $total_comments; ?></p>
    </div>
</div>

<h2>Recent Articles</h2>
<ul>
    <?php foreach ($recent_articles as $article): ?>
        <li>
            <a href="/article.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a>
            <span class="date">(<?php echo date('Y-m-d', strtotime($article['created_at'])); ?>)</span>
        </li>
    <?php endforeach; ?>
</ul>

<h2>Recent Comments</h2>
<ul>
    <?php foreach ($recent_comments as $comment): ?>
        <li>
            <strong><?php echo htmlspecialchars($comment['username']); ?></strong> on 
            <a href="/article.php?id=<?php echo $comment['id']; ?>"><?php echo htmlspecialchars($comment['article_title']); ?></a>:
            <p><?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . '...'; ?></p>
            <span class="date">(<?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?>)</span>
        </li>
    <?php endforeach; ?>
</ul>

<?php include '../includes/admin_footer.php'; ?>
