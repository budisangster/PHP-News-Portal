<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$articles = [];
$total_articles = 0;
$total_pages = 0;

if (!empty($search_query)) {
    try {
        $stmt = $pdo->prepare("SELECT a.id, a.title, a.content, a.created_at, a.thumbnail, u.username as author, c.name as category 
                               FROM articles a 
                               JOIN users u ON a.author_id = u.id 
                               JOIN categories c ON a.category_id = c.id 
                               WHERE a.title LIKE :search OR a.content LIKE :search
                               ORDER BY a.created_at DESC
                               LIMIT :limit OFFSET :offset");
        
        $search_param = "%$search_query%";
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles a WHERE a.title LIKE :search OR a.content LIKE :search");
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $stmt->execute();
        $total_articles = $stmt->fetchColumn();
        $total_pages = ceil($total_articles / $per_page);
    } catch (PDOException $e) {
        // Log the error and show a user-friendly message
        error_log("Database error: " . $e->getMessage());
        $error_message = "An error occurred while searching. Please try again later.";
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/search-categories.css">

<div class="container mt-4">
    <h1 class="text-center mb-4">Search Results</h1>

    <form action="search.php" method="GET" class="search-form mb-4">
        <div class="search-wrapper">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" class="search-input" placeholder="Search articles..." required>
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php elseif (!empty($search_query)): ?>
        <?php if (count($articles) > 0): ?>
            <h2 class="mb-3">Found <?php echo $total_articles; ?> result(s) for "<?php echo htmlspecialchars($search_query); ?>"</h2>
            
            <div class="row">
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="article-card h-100">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" class="article-image" alt="Article thumbnail">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title">
                                    <a href="article.php?id=<?php echo $article['id']; ?>">
                                        <?php echo highlight_search_term(htmlspecialchars($article['title']), $search_query); ?>
                                    </a>
                                </h3>
                                <p class="card-text text-muted mb-2">
                                    By <?php echo htmlspecialchars($article['author']); ?> in <?php echo htmlspecialchars($article['category']); ?><br>
                                    <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                                </p>
                                <p class="card-text flex-grow-1">
                                    <?php echo highlight_search_term(substr(strip_tags($article['content']), 0, 150) . '...', $search_query); ?>
                                </p>
                                <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-outline-primary mt-auto">Read more</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Search results pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?q=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info">No results found for "<?php echo htmlspecialchars($search_query); ?>"</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
