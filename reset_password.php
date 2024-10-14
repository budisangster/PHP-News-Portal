<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    redirect('login.php');
}

$token = $_GET['token'];

$stmt = $pdo->prepare("SELECT user_id, expires FROM password_resets WHERE token = ? AND expires > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "Invalid or expired token";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $reset['user_id']]);

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$reset['user_id']]);

        $success = "Password has been reset successfully. You can now login with your new password.";
    }
}

include 'includes/header.php';
?>

<h1>Reset Password</h1>

<?php if ($error): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?php echo $success; ?></p>
<?php else: ?>
    <form method="POST">
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Reset Password</button>
    </form>
<?php endif; ?>

<p><a href="login.php">Back to Login</a></p>

<?php include 'includes/footer.php'; ?>
