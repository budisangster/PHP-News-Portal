<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Gaming News Portal";

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

include 'includes/header.php';
?>

<main class="home-page">
    <div class="container">
        <?php if (!empty($featured_articles)): ?>
            <section class="featured-articles">
                <h2 class="section-title">Featured Articles</h2>
                <div class="featured-grid">
                    <?php foreach ($featured_articles as $index => $article): ?>
                        <article class="featured-article <?php echo $index === 0 ? 'main-feature' : ''; ?>">
                            <a href="article.php?id=<?php echo $article['id']; ?>">
                                <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-thumbnail">
                                <div class="article-info">
                                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                                    <p class="article-meta">
                                        <span class="author"><?php echo htmlspecialchars($article['author']); ?></span>
                                        <span class="date"><?php echo date('M j, Y', strtotime($article['created_at'])); ?></span>
                                    </p>
                                    <?php if ($index === 0): ?>
                                        <p class="article-excerpt"><?php echo substr(strip_tags($article['content']), 0, 150) . '...'; ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($regular_articles)): ?>
            <section class="latest-articles">
                <h2 class="section-title">Latest Articles</h2>
                <div class="articles-grid">
                    <?php foreach ($regular_articles as $article): ?>
                        <article class="article-card">
                            <a href="article.php?id=<?php echo $article['id']; ?>">
                                <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="article-thumbnail">
                                <div class="article-info">
                                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                                    <p class="article-meta">
                                        <span class="author"><?php echo htmlspecialchars($article['author']); ?></span>
                                        <span class="date"><?php echo date('M j, Y', strtotime($article['created_at'])); ?></span>
                                    </p>
                                    <p class="article-excerpt"><?php echo substr(strip_tags($article['content']), 0, 100) . '...'; ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (empty($featured_articles) && empty($regular_articles)): ?>
            <p>No articles found. Check back soon for new content!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
