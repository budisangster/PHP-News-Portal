<?php
session_start();
// Debugging
echo "<!-- User ID: " . ($_SESSION['user_id'] ?? 'Not set') . " -->";
echo "<!-- User Role: " . ($_SESSION['user_role'] ?? 'Not set') . " -->";
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$article_id = (int)$_GET['id'];

// Fetch the article
$stmt = $pdo->prepare("SELECT a.*, u.username as author, c.name as category 
                       FROM articles a 
                       JOIN users u ON a.author_id = u.id 
                       JOIN categories c ON a.category_id = c.id 
                       WHERE a.id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: index.php");
    exit();
}

// Fetch tags for this article
$stmt = $pdo->prepare("SELECT t.id, t.name 
                       FROM tags t 
                       JOIN article_tags at ON t.id = at.tag_id 
                       WHERE at.article_id = ?");
$stmt->execute([$article_id]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch comments for this article with user avatars, vote counts, and user's vote
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.avatar_url,
           (SELECT COUNT(*) FROM comment_votes WHERE comment_id = c.id AND vote_type = 1) as upvotes,
           (SELECT COUNT(*) FROM comment_votes WHERE comment_id = c.id AND vote_type = -1) as downvotes,
           (SELECT vote_type FROM comment_votes WHERE comment_id = c.id AND user_id = ?) as user_vote
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.article_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null, $article_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the displayComments function
function displayComments($comments, $article_id, $parent_id = 0, $depth = 0) {
    $html = '';
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parent_id) {
            $html .= '<div class="comment-item" data-comment-id="' . $comment['id'] . '" style="margin-left: ' . ($depth * 20) . 'px;">
                <div class="comment-vote">
                    <button class="vote-btn upvote ' . ($comment['user_vote'] == 1 ? 'active' : '') . '" data-comment-id="' . $comment['id'] . '" data-vote-type="1">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <span class="vote-count">' . ($comment['upvotes'] - $comment['downvotes']) . '</span>
                    <button class="vote-btn downvote ' . ($comment['user_vote'] == -1 ? 'active' : '') . '" data-comment-id="' . $comment['id'] . '" data-vote-type="-1">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <img src="' . (!empty($comment['avatar_url']) ? htmlspecialchars($comment['avatar_url']) : 'assets/images/default-avatar.png') . '" 
                             alt="' . htmlspecialchars($comment['username']) . '" class="comment-avatar">
                        <span class="comment-author">' . htmlspecialchars($comment['username']) . '</span>
                        <span class="comment-date">' . time_elapsed_string($comment['created_at']) . '</span>
                    </div>
                    <div class="comment-body">
                        ' . nl2br(htmlspecialchars($comment['content'])) . '
                    </div>
                    <div class="comment-actions">
                        <button class="reply-btn" data-comment-id="' . $comment['id'] . '">Reply</button>
                    </div>
                    <div class="reply-form-container" style="display: none;">
                        <form class="reply-form" data-parent-id="' . $comment['id'] . '">
                            <textarea class="form-control" rows="3" required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Submit Reply</button>
                        </form>
                    </div>
                </div>
            </div>';
            $html .= displayComments($comments, $article_id, $comment['id'], $depth + 1);
        }
    }
    return $html;
}

// Generate a unique token for the form
if (!isset($_SESSION['comment_token'])) {
    $_SESSION['comment_token'] = bin2hex(random_bytes(32));
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    if (!is_logged_in()) {
        $_SESSION['error'] = "You must be logged in to post a comment.";
        redirect("login.php");
    }

    // Verify the token
    if (!isset($_POST['comment_token']) || $_POST['comment_token'] !== $_SESSION['comment_token']) {
        $_SESSION['error'] = "Invalid token. Please try again.";
        redirect("article.php?id=" . $article_id);
    }

    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        try {
            $pdo->beginTransaction();

            // Check if the comment already exists
            $stmt = $pdo->prepare("SELECT id FROM comments WHERE article_id = ? AND user_id = ? AND content = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->execute([$article_id, $_SESSION['user_id'], $comment]);
            if ($stmt->fetch()) {
                throw new Exception("Duplicate comment detected.");
            }

            // Insert the comment
            $stmt = $pdo->prepare("INSERT INTO comments (article_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$article_id, $_SESSION['user_id'], $comment]);

            $pdo->commit();
            $_SESSION['success'] = "Comment posted successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error posting comment: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Comment cannot be empty.";
    }

    // Generate a new token and redirect to prevent form resubmission
    $_SESSION['comment_token'] = bin2hex(random_bytes(32));
    redirect("article.php?id=" . $article_id);
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <article class="article-content">
        <header class="article-header">
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="article-meta">
                <span class="article-author">By <?php echo htmlspecialchars($article['author']); ?></span>
                <span class="article-date">on <?php echo date('F j, Y', strtotime($article['created_at'])); ?></span>
                <span class="article-category">in <?php echo htmlspecialchars($article['category']); ?></span>
            </div>
        </header>

        <?php if (!empty($article['thumbnail'])): ?>
            <div class="article-image">
                <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="Article thumbnail" class="img-fluid">
            </div>
        <?php endif; ?>

        <div class="article-body">
            <?php echo $article['content']; ?>
        </div>

        <footer class="article-footer">
            <div class="article-tags">
                <?php if (!empty($tags)): ?>
                    <?php foreach ($tags as $tag): ?>
                        <a href="tag.php?id=<?php echo $tag['id']; ?>" class="tag"><?php echo htmlspecialchars($tag['name']); ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No tags for this article.</p>
                <?php endif; ?>
            </div>
        </footer>
    </article>

    <section class="comments-section mt-5">
        <h2 class="section-title">Comments</h2>
        <?php if (is_logged_in()): ?>
            <form id="main-comment-form" class="comment-form mb-4">
                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                <div class="form-group">
                    <textarea name="comment" class="form-control comment-textarea" rows="3" required placeholder="Share your thoughts..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary comment-submit">Post Comment</button>
            </form>
        <?php else: ?>
            <div class="login-prompt">
                <p>Please <a href="login.php">log in</a> to leave a comment.</p>
            </div>
        <?php endif; ?>

        <div class="comment-list">
            <?php echo displayComments($comments, $article['id']); ?>
        </div>
    </section>

    <div class="share-buttons">
        <h3>Share this article</h3>
        <?php
        $url = urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        $title = urlencode($article['title']);
        ?>
        <div class="share-icons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>" target="_blank" rel="noopener noreferrer" class="share-icon facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>&text=<?php echo $title; ?>" target="_blank" rel="noopener noreferrer" class="share-icon twitter">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $url; ?>&title=<?php echo $title; ?>" target="_blank" rel="noopener noreferrer" class="share-icon linkedin">
                <i class="fab fa-linkedin-in"></i>
            </a>
            <a href="mailto:?subject=<?php echo $title; ?>&body=Check out this article: <?php echo $url; ?>" class="share-icon email">
                <i class="fas fa-envelope"></i>
            </a>
        </div>
    </div>
</div>

<!-- Add this right before including the main.js file or before the closing </body> tag -->
<script>
    const articleId = <?php echo json_encode($article['id']); ?>;
</script>
<script src="assets/js/main.js"></script>

<?php include 'includes/footer.php'; ?>
