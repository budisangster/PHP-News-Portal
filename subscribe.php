<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "This email is already subscribed to our newsletter.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            if ($stmt->execute([$email])) {
                $subject = "Welcome to Our Newsletter";
                $body = "Thank you for subscribing to our newsletter. We'll keep you updated with the latest news and articles.";
                
                if (send_email($email, $subject, $body)) {
                    $success = "You have successfully subscribed to our newsletter!";
                } else {
                    $error = "Subscription successful, but there was a problem sending the confirmation email.";
                }
            } else {
                $error = "There was a problem with your subscription. Please try again later.";
            }
        }
    }
}

include 'includes/header.php';
?>

<h1>Subscribe to Our Newsletter</h1>

<?php if ($error): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?php echo $success; ?></p>
<?php endif; ?>

<form method="POST">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <button type="submit">Subscribe</button>
</form>

<?php include 'includes/footer.php'; ?>
