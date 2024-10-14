<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Categories - Gaming News Portal";

// Fetch categories with article count
$stmt = $pdo->query("
    SELECT c.id, c.name, COUNT(a.id) as article_count
    FROM categories c
    LEFT JOIN articles a ON c.id = a.category_id
    GROUP BY c.id
    ORDER BY c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <h1 class="display-4 text-center mb-5">Categories</h1>

    <div class="row justify-content-center">
        <?php foreach ($categories as $category): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="category.php?id=<?php echo $category['id']; ?>" class="category-item">
                    <div class="category-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="category-details">
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="category-count">
                            <?php echo $category['article_count']; ?> article<?php echo $category['article_count'] != 1 ? 's' : ''; ?>
                        </p>
                    </div>
                    <div class="category-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
