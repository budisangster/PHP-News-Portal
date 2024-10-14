<?php
session_start();
require_once 'includes/functions.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page with a success message
$_SESSION['success'] = "You have been successfully logged out.";
redirect('login.php');
