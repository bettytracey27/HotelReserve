<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']['id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'admin';
}

function isManager() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'manager';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        // Optionally, you can set an error message before redirect
        $_SESSION['error'] = 'You must be logged in to access this page.';
        header("Location: ../login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // Debugging: print session or message here
        echo 'Access Denied: You are not an admin';
        // Redirect if not admin
        header("Location: ../index.php");
        exit();
    }
}

// Redirect if not manager or admin
function requireManagerOrAdmin() {
    requireLogin();
    if (!isManager() && !isAdmin()) {
        // Optionally, you can set an error message before redirect
        $_SESSION['error'] = 'Access denied. Managers and Admins only.';
        header("Location: ../index.php");  // Or login.php if you prefer
        exit();
    }
}
