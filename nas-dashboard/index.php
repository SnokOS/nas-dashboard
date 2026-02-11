<?php
/**
 * NAS Dashboard - Index Page
 * Redirects to login or dashboard based on authentication
 */

session_start();

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.html');
} else {
    // User is not logged in, redirect to login page
    header('Location: login.html');
}

exit;
?>
