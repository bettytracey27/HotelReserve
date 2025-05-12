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
    session_regenerate_id(true);
}

require_once __DIR__ . '/../../includes/db.php';

try {
    $db = new Database();

    // Login handling
    if (isset($_POST['login']) && $_POST['login'] == '1') {
        // Validate presence
        if (empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Email and password are required");
        }

        // Fetch user by email
        $db->query("SELECT * FROM users WHERE email = :email");
        $db->bind(':email', $_POST['email']);
        $user = $db->single();

        if (!$user) {
            throw new Exception("No user found with this email.");
        }

        // Password check
        if (!password_verify($_POST['password'], $user->password)) {
            throw new Exception("Invalid credentials.");
        }

        // Set session variables
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user'] = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'last_login' => time()
        ];

       
        $redirect = match($user->role) {
            'admin' => '/Hotel-Reserve/public/admin/index.php',
            'manager' => '/Hotel-Reserve/public/manag/index.php',
            default => isset($_SESSION['redirect_url']) 
                     ? $_SESSION['redirect_url'] 
                     : '/Hotel-Reserve/public/index.php'
        };

        // Clear redirect URL if it exists
        if (isset($_SESSION['redirect_url'])) {
            unset($_SESSION['redirect_url']);
        }

        header("Location: $redirect");
        exit();
    }

    // Registration handling
    if (isset($_POST['register']) && $_POST['register'] == '1') {
        // Validate required fields
        $required = ['username', 'email', 'password', 'confirm_password', 
                    'first_name', 'last_name', 'primary_phone'];
        
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All required fields must be filled");
            }
        }
        
        // Password confirmation
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match");
        }
        
        // Password strength
        if (strlen($_POST['password']) < 8 || 
            !preg_match("/[A-Z]/", $_POST['password']) || 
            !preg_match("/[a-z]/", $_POST['password']) || 
            !preg_match("/[0-9]/", $_POST['password'])) {
            throw new Exception("Password must be at least 8 characters with uppercase, lowercase and numbers");
        }
        
        // Check if username or email already exists
        $db->query("SELECT id FROM users WHERE username = :username OR email = :email");
        $db->bind(':username', $_POST['username']);
        $db->bind(':email', $_POST['email']);
        $existing = $db->single();
        
        if ($existing) {
            throw new Exception("Username or email already exists");
        }
        
        // Hash password
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Insert new user - FORCE role to 'user' for registration
        $db->query("INSERT INTO users (
            username, 
            email, 
            phone, 
            password, 
            first_name, 
            last_name, 
            primary_phone, 
            secondary_phone,
            role
        ) VALUES (
            :username, 
            :email, 
            :phone, 
            :password, 
            :first_name, 
            :last_name, 
            :primary_phone, 
            :secondary_phone,
            'user'
        )");
        
        $db->bind(':username', $_POST['username']);
        $db->bind(':email', $_POST['email']);
        $db->bind(':phone', $_POST['phone'] ?? null);
        $db->bind(':password', $hashedPassword);
        $db->bind(':first_name', $_POST['first_name']);
        $db->bind(':last_name', $_POST['last_name']);
        $db->bind(':primary_phone', $_POST['primary_phone']);
        $db->bind(':secondary_phone', $_POST['secondary_phone'] ?? null);
        
        if (!$db->execute()) {
            throw new Exception("Registration failed. Please try again.");
        }
        
        // Get the new user ID
        $userId = $db->lastInsertId();
        
        // Set session variables for auto-login after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['user'] = [
            'id' => $userId,
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'role' => 'user',
            'last_login' => time()
        ];
        
        // Redirect to home page
        header("Location: /Hotel-Reserve/public/index.php");
        exit();
    }

    // Fallback if no valid action
    throw new Exception("Invalid access method.");

} catch (Exception $e) {
    // Preserve form data on error and redirect appropriately
    $_SESSION['form_data'] = $_POST;
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = isset($_POST['login']) ? 'login.php' : 'register.php';
    header("Location: /Hotel-Reserve/public/$redirect");
    exit();
}