<?php
// /includes/auth.php

function isLoggedIn() {
    return isset($_SESSION['user']['id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function isManager() {
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'manager';
}

function getManagerHotelId($userId) {
    global $db;
    $db->query("SELECT assigned_location_id FROM users WHERE id = :id");
    $db->bind(':id', $userId);
    $result = $db->single();
    return $result->assigned_location_id ?? null;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: /Hotel-Reserve/public/login.php");
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header("Location: /Hotel-Reserve/public/index.php");
        exit();
    }
}

function requireManager() {
    requireAuth();
    if (!isManager()) {
        header("Location: /Hotel-Reserve/public/index.php");
        exit();
    }
}