<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set the content type to XML
header("Content-Type: application/rss+xml; charset=UTF-8");

// Fetch the latest articles
$stmt = $pdo->query("SELECT a.id, a.title, a.content, a.created_at, u.username as author, c.name as category 
                     FROM articles a 
                     JOIN users u ON a.author_id = u.id 
                     JOIN categories c ON a.category_id = c.id 
                     ORDER BY a.created_at DESC 
                     LIMIT 20");
$articles = $stmt->fetchAll();

// Generate the RSS feed
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>News Portal</title>
        <link><?php echo "http://$_SERVER[HTTP_HOST]"; ?></link>
        <description>Latest news from our News Portal</description>
        <language>en-us</language>
        <lastBuildDate><?php echo date(DATE_RSS, strtotime($articles[0]['created_at'])); ?></lastBuildDate>
        <atom:link href="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" rel="self" type="application/rss+xml" />
        
        <?php foreach ($articles as $article): ?>
            <item>
                <title><?php echo htmlspecialchars($article['title']); ?></title>
                <link><?php echo "http://$_SERVER[HTTP_HOST]/article.php?id=" . $article['id']; ?></link>
                <description><?php echo htmlspecialchars(substr(strip_tags($article['content']), 0, 200)) . '...'; ?></description>
                <pubDate><?php echo date(DATE_RSS, strtotime($article['created_at'])); ?></pubDate>
                <guid><?php echo "http://$_SERVER[HTTP_HOST]/article.php?id=" . $article['id']; ?></guid>
                <category><?php echo htmlspecialchars($article['category']); ?></category>
                <author><?php echo htmlspecialchars($article['author']); ?></author>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>
