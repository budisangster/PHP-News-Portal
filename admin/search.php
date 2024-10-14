<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'articles';

$articles = [];
$users = [];

if (!empty($search_term)) {
    if ($search_type === 'articles' || $search_type === 'both') {
        $stmt = $pdo->prepare("SELECT a.id, a.title, a.created_at, u.username as author, c.name as category 
                               FROM articles a 
                               JOIN users u ON a.author_id = u.id 
                               JOIN categories c ON a.category_id = c.id 
                               WHERE a.title LIKE :search OR a.content LIKE :search
                               ORDER BY a.created_at DESC");
        $stmt->execute(['search' => "%$search_term%"]);
        $articles = $stmt->fetchAll();
    }

    if ($search_type === 'users' || $search_type === 'both') {
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at 
                               FROM users 
                               WHERE username LIKE :search OR email LIKE :search
                               ORDER BY username ASC");
        $stmt->execute(['search' => "%$search_term%"]);
        $users = $stmt->fetchAll();
    }
}

include '../includes/admin_header.php';
?>

<h1>Admin Search</h1>

<form method="GET" action="search.php">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter search term" required>
    <select name="type">
        <option value="both" <?php echo $search_type === 'both' ? 'selected' : ''; ?>>Both</option>
        <option value="articles" <?php echo $search_type === 'articles' ? 'selected' : ''; ?>>Articles</option>
        <option value="users" <?php echo $search_type === 'users' ? 'selected' : ''; ?>>Users</option>
    </select>
    <button type="submit">Search</button>
</form>

<?php if (!empty($search_term)): ?>
    <h2>Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h2>

    <?php if ($search_type === 'articles' || $search_type === 'both'): ?>
        <h3>Articles</h3>
        <?php if (count($articles) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($article['title']); ?></td>
                            <td><?php echo htmlspecialchars($article['author']); ?></td>
                            <td><?php echo htmlspecialchars($article['category']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></td>
                            <td>
                                <a href="edit_article.php?id=<?php echo $article['id']; ?>">Edit</a>
                                <a href="delete_article.php?id=<?php echo $article['id']; ?>" onclick="return confirm('Are you sure you want to delete this article?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No articles found.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($search_type === 'users' || $search_type === 'both'): ?>
        <h3>Users</h3>
        <?php if (count($users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include '../includes/admin_footer.php'; ?>
