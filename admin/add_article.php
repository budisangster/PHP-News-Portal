<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch categories for the dropdown
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process the form data
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];

    // Handle thumbnail upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['thumbnail']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_dir = '../uploads/thumbnails/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_dir . $new_filename)) {
                $thumbnail = 'uploads/thumbnails/' . $new_filename;
            }
        }
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, author_id, category_id, is_featured, thumbnail) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $_SESSION['user_id'], $category_id, $is_featured, $thumbnail]);
        $article_id = $pdo->lastInsertId();

        foreach ($tags as $tag_name) {
            $tag_name = trim($tag_name);
            if (!empty($tag_name)) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                $stmt->execute([$tag_name]);
                $tag_id = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM tags WHERE name = " . $pdo->quote($tag_name))->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$article_id, $tag_id]);
            }
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Article added successfully!";
        header("Location: manage_articles.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding article: " . $e->getMessage();
    }

    // If you want to return a response
    echo json_encode(['success' => true, 'message' => 'Article added successfully']);
    exit;
}

include '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Add New Article</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form id="articleForm" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label">Title:</label>
            <input type="text" id="title" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Content:</label>
            <div id="editor" style="height: 300px;"></div>
            <textarea id="content" name="content" style="display:none;"></textarea>
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Category:</label>
            <select id="category_id" name="category_id" class="form-select" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="thumbnail" class="form-label">Thumbnail:</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="tags" class="form-label">Tags (comma-separated):</label>
            <input type="text" id="tags" name="tags" class="form-control">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" id="is_featured" name="is_featured" class="form-check-input" value="1">
            <label for="is_featured" class="form-check-label">Featured Article</label>
        </div>

        <button type="button" id="submitButton" class="btn btn-primary">Add Article</button>
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
        window.location.href = 'manage_articles.php';
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>
