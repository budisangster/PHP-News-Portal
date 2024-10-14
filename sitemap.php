<?php
require_once 'config/database.php';

// Set content type to XML
header("Content-Type: application/xml; charset=utf-8");

// Start XML file
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Add static pages
$static_pages = ['index.php', 'login.php', 'register.php', 'categories.php'];
foreach ($static_pages as $page) {
    echo "\t<url>\n";
    echo "\t\t<loc>http://" . $_SERVER['HTTP_HOST'] . "/$page</loc>\n";
    echo "\t\t<changefreq>weekly</changefreq>\n";
    echo "\t\t<priority>0.8</priority>\n";
    echo "\t</url>\n";
}

// Add articles
$stmt = $pdo->query("SELECT id, updated_at FROM articles ORDER BY created_at DESC");
while ($row = $stmt->fetch()) {
    echo "\t<url>\n";
    echo "\t\t<loc>http://" . $_SERVER['HTTP_HOST'] . "/article.php?id=" . $row['id'] . "</loc>\n";
    echo "\t\t<lastmod>" . date('Y-m-d', strtotime($row['updated_at'])) . "</lastmod>\n";
    echo "\t\t<changefreq>monthly</changefreq>\n";
    echo "\t\t<priority>0.6</priority>\n";
    echo "\t</url>\n";
}

// Add categories
$stmt = $pdo->query("SELECT id, name FROM categories");
while ($row = $stmt->fetch()) {
    echo "\t<url>\n";
    echo "\t\t<loc>http://" . $_SERVER['HTTP_HOST'] . "/category.php?id=" . $row['id'] . "</loc>\n";
    echo "\t\t<changefreq>weekly</changefreq>\n";
    echo "\t\t<priority>0.7</priority>\n";
    echo "\t</url>\n";
}

// Close XML file
echo '</urlset>';
