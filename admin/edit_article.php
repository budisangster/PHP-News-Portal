<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_articles.php");
    exit();
}

$article_id = $_GET['id'];

// Fetch the article
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();

if (!$article) {
    header("Location: manage_articles.php");
    exit();
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Fetch tags for this article
$tags = get_article_tags($pdo, $article_id);
$tags_string = implode(', ', array_column($tags, 'name'));

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $tags = explode(',', $_POST['tags']);

    // Validate input
    if (empty($title) || empty($content) || empty($category_id)) {
        $error = "Please fill in all required fields.";
    } else {
        // Update article
        $stmt = $pdo->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, is_featured = ? WHERE id = ?");
        if ($stmt->execute([$title, $content, $category_id, $is_featured, $article_id])) {
            // Handle thumbnail upload if a new file is provided
            if (!empty($_FILES['thumbnail']['name'])) {
                $thumbnail = upload_image($_FILES['thumbnail'], '../uploads/');
                if ($thumbnail) {
                    $stmt = $pdo->prepare("UPDATE articles SET thumbnail = ? WHERE id = ?");
                    $stmt->execute([$thumbnail, $article_id]);
                }
            }

            // Update tags
            update_article_tags($pdo, $article_id, $tags);

            $success = "Article updated successfully.";
            
            // Fetch the updated article
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$article_id]);
            $article = $stmt->fetch();
            
            // Fetch updated tags
            $tags = get_article_tags($pdo, $article_id);
            $tags_string = implode(', ', array_column($tags, 'name'));
        } else {
            $error = "Failed to update the article.";
        }
    }
}

include '../includes/admin_header.php';
?>

<div class="container">
    <h1>Edit Article</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form id="articleForm" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label">Title:</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($article['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Content:</label>
            <div id="editor" style="height: 300px;"></div>
            <textarea id="content" name="content" style="display:none;"><?php echo htmlspecialchars($article['content']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Category:</label>
            <select id="category_id" name="category_id" class="form-select" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $article['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="thumbnail" class="form-label">Thumbnail:</label>
            <?php if (!empty($article['thumbnail'])): ?>
                <img src="../<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="Current thumbnail" class="img-thumbnail mb-2" style="max-width: 200px;">
            <?php endif; ?>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="tags" class="form-label">Tags (comma-separated):</label>
            <input type="text" id="tags" name="tags" class="form-control" value="<?php echo htmlspecialchars($tags_string); ?>">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" id="is_featured" name="is_featured" class="form-check-input" value="1" <?php echo $article['is_featured'] ? 'checked' : ''; ?>>
            <label for="is_featured" class="form-check-label">Featured Article</label>
        </div>

        <button type="button" id="submitButton" class="btn btn-primary">Update Article</button>
    </form>
</div>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean'],
            ['link', 'image', 'video']
        ]
    }
});

// Set initial content
quill.root.innerHTML = document.querySelector('#content').value;

// Custom image upload handler
quill.getModule('toolbar').addHandler('image', function() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch('upload_image.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', result.url);
            } else {
                console.error('Image upload failed:', result.error);
            }
        } catch (error) {
            console.error('Error uploading image:', error);
        }
    };
});

document.getElementById('submitButton').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Update the textarea with the editor content
    document.querySelector('#content').value = quill.root.innerHTML;
    
    // Create a new FormData object
    var formData = new FormData(document.getElementById('articleForm'));
    
    // Send the form data using fetch
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log(data);
        // Check if the response contains a success message
        if (data.includes("Article updated successfully")) {
            alert("Article updated successfully!");
            // Reload the page to show the updated content
            window.location.reload();
        } else {
            alert("Failed to update the article. Please try again.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("An error occurred while updating the article.");
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>
