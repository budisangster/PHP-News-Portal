<?php
$page_title = "Contact Us";
include 'includes/header.php';

// Add form processing logic here if you want to handle form submissions
?>

<div class="container">
    <h1>Contact Us</h1>
    
    <p>We'd love to hear from you. Please fill out the form below or use our contact information.</p>
    
    <div class="contact-info">
        <h2>Contact Information</h2>
        <p>Email: contact@budisangster.com</p>
        <p>Phone: +1 (123) 456-7890</p>
        <p>Address: 123 Main St, City, Country, ZIP</p>
    </div>
    
    <form action="process_contact.php" method="POST" class="contact-form">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea>
        </div>
        
        <button type="submit">Send Message</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
