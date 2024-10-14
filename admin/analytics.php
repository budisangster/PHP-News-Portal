<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

// Get total page views
$total_views = $pdo->query("SELECT SUM(view_count) FROM page_views")->fetchColumn();

// Get top 5 most viewed pages
$top_pages = $pdo->query("SELECT page_url, view_count FROM page_views ORDER BY view_count DESC LIMIT 5")->fetchAll();

// Get total article views
$total_article_views = $pdo->query("SELECT SUM(view_count) FROM article_views")->fetchColumn();

// Get top 5 most viewed articles
$top_articles = $pdo->query("
    SELECT a.title, av.view_count 
    FROM article_views av 
    JOIN articles a ON av.article_id = a.id 
    ORDER BY av.view_count DESC 
    LIMIT 5
")->fetchAll();

// Get user registration stats (last 7 days)
$user_stats = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/admin_header.php';
?>

<h1>Analytics</h1>

<div class="analytics-summary">
    <div class="analytics-card">
        <h3>Total Page Views</h3>
        <p><?php echo number_format($total_views); ?></p>
    </div>
    <div class="analytics-card">
        <h3>Total Article Views</h3>
        <p><?php echo number_format($total_article_views); ?></p>
    </div>
</div>

<div class="analytics-section">
    <h2>Top 5 Most Viewed Pages</h2>
    <table>
        <thead>
            <tr>
                <th>Page URL</th>
                <th>Views</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_pages as $page): ?>
                <tr>
                    <td><?php echo htmlspecialchars($page['page_url']); ?></td>
                    <td><?php echo number_format($page['view_count']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="analytics-section">
    <h2>Top 5 Most Viewed Articles</h2>
    <table>
        <thead>
            <tr>
                <th>Article Title</th>
                <th>Views</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_articles as $article): ?>
                <tr>
                    <td><?php echo htmlspecialchars($article['title']); ?></td>
                    <td><?php echo number_format($article['view_count']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="analytics-section">
    <h2>User Registrations (Last 7 Days)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>New Users</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $count = isset($user_stats[$date]) ? $user_stats[$date] : 0;
                echo "<tr><td>$date</td><td>$count</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include '../includes/admin_footer.php'; ?>
