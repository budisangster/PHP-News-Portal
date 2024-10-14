<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Track page view
track_page_view($pdo, 'popular_articles.php');

$popular_articles = get_popular_articles($pdo, 10);

include 'includes/header.php';
?>

<h1>Popular Articles</h1>

<ul class="article-list">
    <?php foreach ($popular_articles as $article): ?>
        <li>
            <a href="article.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a>
            <span class="view-count">(<?php echo $article['view_count']; ?> views)</span>
        </li>
    <?php endforeach; ?>
</ul>

<?php include 'includes/footer.php'; ?>
