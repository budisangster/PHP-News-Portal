<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = "Login";
include 'includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/auth.css">

<div class="auth-container">
    <h1>Login</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <p class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php endif; ?>

    <form class="auth-form" method="POST" action="login_process.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <div class="auth-links">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
        <p><a href="forgot_password.php">Forgot your password?</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
