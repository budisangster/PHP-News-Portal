<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle tag addition
if (isset($_POST['add_tag'])) {
    $name = trim($_POST['tag_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);
    }
}

// Handle tag deletion
if (isset($_POST['delete_tag'])) {
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$_POST['delete_tag']]);
}

// Fetch all tags
$stmt = $pdo->query("SELECT * FROM tags ORDER BY name");
$tags = $stmt->fetchAll();

include '../includes/header.php';
?>

<h1>Manage Tags</h1>

<h2>Add New Tag</h2>
<form method="POST">
    <input type="text" name="tag_name" required>
    <button type="submit" name="add_tag">Add Tag</button>
</form>

<h2>Existing Tags</h2>
<ul>
    <?php foreach ($tags as $tag): ?>
        <li>
            <?php echo htmlspecialchars($tag['name']); ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="delete_tag" value="<?php echo $tag['id']; ?>">
                <button type="submit" onclick="return confirm('Are you sure you want to delete this tag?')">Delete</button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>

<?php include '../includes/footer.php'; ?>
