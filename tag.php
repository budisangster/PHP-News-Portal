<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$tag_id = $_GET['id'];

// Fetch tag information
$stmt = $pdo->prepare("SELECT name FROM tags WHERE id = ?");
$stmt->execute([$tag_id]);
$tag = $stmt->fetch();

if (!$tag) {
    header("Location: index.php");
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

// Fetch articles with the given tag
$stmt = $pdo->prepare("SELECT a.id, a.title, a.content, a.created_at, u.username as author, c.name as category 
                       FROM articles a 
                       JOIN users u ON a.author_id = u.id 
                       JOIN categories c ON a.category_id = c.id 
                       JOIN article_tags at ON a.id = at.article_id 
                       WHERE at.tag_id = ? 
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->execute([$tag_id, $per_page, $offset]);
$articles = $stmt->fetchAll();

// Get total number of articles with this tag
$stmt = $pdo->prepare("SELECT COUNT(*) FROM article_tags WHERE tag_id = ?");
$stmt->execute([$tag_id]);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

include 'includes/header.php';
?>

<h1>Articles tagged with "<?php echo htmlspecialchars($tag['name']); ?>"</h1>

<?php foreach ($articles as $article): ?>
    <article>
        <h2><a href="article.php?id=<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['title']); ?></a></h2>
        <p>By <?php echo htmlspecialchars($article['author']); ?> in <?php echo htmlspecialchars($article['category']); ?></p>
        <p><?php echo substr(htmlspecialchars($article['content']), 0, 200) . '...'; ?></p>
        <p><a href="article.php?id=<?php echo $article['id']; ?>">Read more</a></p>
    </article>
<?php endforeach; ?>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?id=<?php echo $tag_id; ?>&page=<?php echo $page - 1; ?>">Previous</a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
            <span><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?id=<?php echo $tag_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $total_pages): ?>
        <a href="?id=<?php echo $tag_id; ?>&page=<?php echo $page + 1; ?>">Next</a>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
