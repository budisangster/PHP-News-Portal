<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Sitemap";
include 'includes/header.php';
?>

<h1>Sitemap</h1>

<h2>Static Pages</h2>
<ul>
    <li><a href="index.php">Home</a></li>
    <li><a href="login.php">Login</a></li>
    <li><a href="register.php">Register</a></li>
    <li><a href="categories.php">Categories</a></li>
</ul>

<h2>Categories</h2>
<ul>
    <?php
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    while ($row = $stmt->fetch()) {
        echo "<li><a href='category.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</a></li>";
    }
    ?>
</ul>

<h2>Recent Articles</h2>
<ul>
    <?php
    $stmt = $pdo->query("SELECT id, title FROM articles ORDER BY created_at DESC LIMIT 20");
    while ($row = $stmt->fetch()) {
        echo "<li><a href='article.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['title']) . "</a></li>";
    }
    ?>
</ul>

<?php include 'includes/footer.php'; ?>
