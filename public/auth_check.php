<?php


if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/Hotel-Reserve/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}


if (!isset($_SESSION['user']['id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /Hotel-Reserve/public/login.php');
    exit();
}


$_SESSION['user_id'] = $_SESSION['user']['id'];

// Optional database verification
require_once __DIR__ . '/../includes/db.php';
try {
    $db = new Database();
    $db->query("SELECT id FROM users WHERE id = :id");
    $db->bind(':id', $_SESSION['user']['id']);
    $user = $db->single();

    if (!$user) {
        session_destroy();
        header('Location: /Hotel-Reserve/public/login.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Database error in auth_check: " . $e->getMessage());
}