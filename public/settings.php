<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$db = new Database();
$success_message = '';
$error_message = '';

// Fetch current user data
$db->query("SELECT * FROM users WHERE id = :user_id");
$db->bind(':user_id', $_SESSION['user']['id']);
$user = $db->single();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim ($_POST['first_name']) . ' ' . trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Check if email is already taken by another user
        $db->query("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $db->bind(':email', $email);
        $db->bind(':user_id', $_SESSION['user']['id']);
        $existing_user = $db->single();
        
        if ($existing_user) {
            $error_message = "Email is already in use by another account";
        } else {
            // Update basic info
            $db->query("UPDATE users SET full_name = :full_name, email = :email, phone = :phone WHERE id = :user_id");
            $db->bind(':full_name', $first_name . ' ' . $last_name);
            
            $db->bind(':email', $email);
            $db->bind(':phone', $phone);
            $db->bind(':user_id', $_SESSION['user']['id']);
            
            if ($db->execute()) {
                // Update session email if changed
                if ($_SESSION['user']['email'] !== $email) {
                    $_SESSION['user']['email'] = $email;
                }
                
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile";
            }

          
            if (!empty($current_password)) {
                if (empty($new_password) || empty($confirm_password)) {
                    $error_message = "Please fill all password fields to change password";
                } else {
                    // Verify current password
                    if (password_verify($current_password, $user->password)) {
                        if ($new_password === $confirm_password) {
                            if (strlen($new_password) >= 8) {
                                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                $db->query("UPDATE users SET password = :password WHERE id = :user_id");
                                $db->bind(':password', $hashed_password);
                                $db->bind(':user_id', $_SESSION['user']['id']);
                                
                                if ($db->execute()) {
                                    $success_message = "Profile and password updated successfully!";
                                } else {
                                    $error_message = "Profile updated but failed to update password";
                                }
                            } else {
                                $error_message = "New password must be at least 8 characters long";
                            }
                        } else {
                            $error_message = "New passwords do not match";
                        }
                    } else {
                        $error_message = "Current password is incorrect";
                    }
                }
            }
        }
    }
    
    // Refresh user data after update
    $db->query("SELECT * FROM users WHERE id = :user_id");
    $db->bind(':user_id', $_SESSION['user']['id']);
    $user = $db->single();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - HotelReserve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .profile-page {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .profile-container {
            display: flex;
            gap: 20px;
        }
        
        .profile-sidebar {
            width: 250px;
            background: #1a3e2f;
            border-radius: 8px;
            padding: 20px;
            color: white;
        }
        
        .user-card {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            font-size: 60px;
            color: #d4edda;
            margin-bottom: 10px;
        }
        
        .user-card h3 {
            margin: 10px 0 5px;
            color: white;
        }
        
        .user-card p {
            color: #d4edda;
            font-size: 14px;
        }
        
        .profile-nav {
            display: flex;
            flex-direction: column;
        }
        
        .profile-nav a {
            color: white;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .profile-nav a:hover {
            background-color: #2d5b45;
            text-decoration: none;
        }
        
        .profile-nav a.active {
            background-color: #3a7d55;
            font-weight: bold;
        }
        
        .profile-nav i {
            width: 20px;
            text-align: center;
        }
        
        .profile-content {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .profile-content h2 {
            color: #1a3e2f;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .settings-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a3e2f;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #3a7d55;
            outline: none;
            box-shadow: 0 0 0 2px rgba(58, 125, 85, 0.2);
        }
        
        .btn {
            display: inline-block;
            background-color: #3a7d55;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2d5b45;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            
            .profile-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="profile-page">
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3><?= htmlspecialchars($_SESSION['user']['username']) ?></h3>
                    <p><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
                </div>
                
                <nav class="profile-nav">
                    <a href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="booking-history.php">
                        <i class="fas fa-history"></i> Booking History
                    </a>
                    <a href="settings.php" class="active">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </nav>
            </div>
            
            <div class="profile-content">
                <h2>Account Settings</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <form class="settings-form" method="POST" action="settings.php">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user->username) ?>" readonly>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user->full_name ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>">
                    </div>
                    
                    <h3 style="margin: 30px 0 20px; color: #1a3e2f;">Change Password</h3>
                    
                    <div class="form-group password-toggle">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('current_password')"></i>
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('new_password')"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include('../includes/footer.php'); ?>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>