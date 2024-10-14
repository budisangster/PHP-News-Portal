<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$error = '';
$success = '';

// Handle category addition
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        if ($stmt->execute([$name])) {
            $success = "Category added successfully.";
        } else {
            $error = "Error adding category.";
        }
    } else {
        $error = "Category name cannot be empty.";
    }
}

// Handle category deletion
if (isset($_POST['delete_category'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$_POST['delete_category']])) {
        $success = "Category deleted successfully.";
    } else {
        $error = "Error deleting category.";
    }
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<h1>Manage Categories</h1>

<?php if ($error): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?php echo $success; ?></p>
<?php endif; ?>

<h2>Add New Category</h2>
<form method="POST" class="admin-form">
    <input type="text" name="category_name" required>
    <button type="submit" name="add_category">Add Category</button>
</form>

<h2>Existing Categories</h2>
<table class="admin-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_category" value="<?php echo $category['id']; ?>">
                        <button type="submit" class="button small delete" onclick="return confirm('Are you sure you want to delete this category?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/admin_footer.php'; ?>
