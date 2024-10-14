<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$category_id = (int)$_GET['id'];

// Fetch category information
$stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: index.php");
    exit();
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Fetch articles in the category
$stmt = $pdo->prepare("SELECT a.id, a.title, a.content, a.created_at, a.thumbnail, u.username as author 
                       FROM articles a 
                       JOIN users u ON a.author_id = u.id 
                       WHERE a.category_id = ? 
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->bindParam(1, $category_id, PDO::PARAM_INT);
$stmt->bindParam(2, $per_page, PDO::PARAM_INT);
$stmt->bindParam(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of articles in this category
$stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
$stmt->execute([$category_id]);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

include 'includes/header.php';
?>

<div class="container mt-4">
    <h1 class="display-4 text-center mb-5"><?php echo htmlspecialchars($category['name']); ?></h1>

    <div class="category-grid">
        <?php foreach ($articles as $article): ?>
            <div class="category-item">
                <div class="category-card">
                    <div class="category-card-image" style="background-image: url('<?php echo !empty($article['thumbnail']) ? htmlspecialchars($article['thumbnail']) : '/assets/images/placeholder.jpg'; ?>')">
                        <div class="category-card-overlay"></div>
                    </div>
                    <div class="category-card-content">
                        <h3 class="category-card-title">
                            <a href="article.php?id=<?php echo $article['id']; ?>">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h3>
                        <p class="category-card-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author']); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($article['created_at'])); ?></span>
                        </p>
                        <p class="category-card-excerpt">
                            <?php echo substr(strip_tags($article['content']), 0, 100) . '...'; ?>
                        </p>
                        <a href="article.php?id=<?php echo $article['id']; ?>" class="category-card-link">Read more</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Category pagination" class="mt-5">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
