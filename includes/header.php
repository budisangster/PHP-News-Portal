<?php
// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the functions file
require_once __DIR__ . '/../includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Budi Sangster' : 'Budi Sangster'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-content">
            <a href="/" class="logo">
                <img src="/assets/images/logo.png" alt="Budi Sangster Logo" class="logo-image">
            </a>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li><a href="/admin/dashboard.php" class="admin-link">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="/profile.php">Profile</a></li>
                        <li><a href="/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/login.php">Login</a></li>
                        <li><a href="/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="search-container">
                <button class="search-toggle"><i class="fas fa-search"></i></button>
                <form action="/search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Search..." required>
                    <button type="submit"><i class="fas fa-arrow-right"></i></button>
                </form>
            </div>
        </div>
    </header>
    <main>

