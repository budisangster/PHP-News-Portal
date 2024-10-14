<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found");
}

$page_title = "Profile - " . htmlspecialchars($user['username']);
include 'includes/header.php';

// Fetch user's recent comments
$recent_comments = get_user_comments($pdo, $user_id, 5);

// Check if the user has permission to write articles
$can_write_articles = ($user['role'] == 'author' || $user['role'] == 'admin');

if ($can_write_articles) {
    $recent_articles = get_user_articles($pdo, $user_id, 5);
}
?>

<link rel="stylesheet" href="/assets/css/profile.css">

<div class="profile-container">
    <?php
    if (isset($_SESSION['error'])) {
        echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="success-message">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    ?>

    <div class="profile-header">
        <div class="profile-avatar-container">
            <img src="<?php echo $user['avatar_url'] ?? '/assets/images/default-avatar.png'; ?>" alt="Profile Picture" class="profile-avatar">
        </div>
        <div class="profile-info">
            <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </div>

    <div class="profile-section">
        <h2>Edit Profile</h2>
        <form class="profile-form" method="POST" action="update_profile.php" enctype="multipart/form-data">
            <label for="new_avatar">Profile Picture:</label>
            <input type="file" id="new_avatar" name="new_avatar" accept="image/*">

            <label for="new_username">Username:</label>
            <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label for="new_email">Email:</label>
            <input type="email" id="new_email" name="new_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <label for="new_password">New Password (leave blank to keep current):</label>
            <input type="password" id="new_password" name="new_password">

            <button type="submit">Update Profile</button>
        </form>
    </div>

    <?php if (!empty($user_articles)): ?>
    <div class="profile-section">
        <h2>Your Articles</h2>
        <ul class="user-articles">
            <?php foreach ($user_articles as $article): ?>
            <li>
                <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                <p class="article-date">Published on: <?php echo date('F j, Y', strtotime($article['created_at'])); ?></p>
                <a href="edit_article.php?id=<?php echo $article['id']; ?>">Edit</a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($user_comments)): ?>
    <div class="profile-section">
        <h2>Your Comments</h2>
        <ul class="user-comments">
            <?php foreach ($user_comments as $comment): ?>
            <li>
                <h3 class="comment-article">On article: <?php echo htmlspecialchars($comment['article_title']); ?></h3>
                <p class="comment-date">Posted on: <?php echo date('F j, Y', strtotime($comment['created_at'])); ?></p>
                <p class="comment-content"><?php echo htmlspecialchars($comment['content']); ?></p>
                <a href="edit_comment.php?id=<?php echo $comment['id']; ?>" class="edit-comment">Edit</a>
                <a href="delete_comment.php?id=<?php echo $comment['id']; ?>" class="delete-comment" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
