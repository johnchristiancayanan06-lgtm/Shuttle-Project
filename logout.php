<?php
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// 3. Completely destroy the session
session_destroy();

// 4. Redirect to login page
header("Location: login.php");
exit();
?>