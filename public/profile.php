<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$db = new Database();
$db->query("SELECT * FROM users WHERE id = :user_id");
$db->bind(':user_id', $_SESSION['user']['id']);
$user = $db->single();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HotelReserve</title>
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
        
        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3a7d55;
        }
        
        .info-card h3 {
            margin-top: 0;
            color: #1a3e2f;
            font-size: 18px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-item strong {
            display: inline-block;
            width: 120px;
            color: #555;
        }
        
        .btn {
            display: inline-block;
            background-color: #3a7d55;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        
        .btn:hover {
            background-color: #2d5b45;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            
            .profile-sidebar {
                width: 100%;
            }
            
            .user-info {
                grid-template-columns: 1fr;
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
                    <a href="profile.php" class="active">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="booking-history.php">
                        <i class="fas fa-history"></i> Booking History
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </nav>
            </div>
            
            <div class="profile-content">
                <h2>My Profile</h2>
                
                <div class="user-info">
                    <div class="info-card">
                        <h3>Personal Information</h3>
                        <div class="info-item">
                            <strong>Full Name:</strong>
                            <span><?= htmlspecialchars(($user->first_name ?? 'Not provided') . ' ' . ($user->last_name ?? '')) ?></span>

                        </div>
                        <div class="info-item">
                            <strong>Username:</strong>
                            <span><?= htmlspecialchars($user->username) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Email:</strong>
                            <span><?= htmlspecialchars($user->email) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Phone:</strong>
                            <span><?= htmlspecialchars($user->phone ?? 'Not provided') ?></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3>Account Details</h3>
                        <div class="info-item">
                            <strong>Member Since:</strong>
                            <span><?= date('M j, Y', strtotime($user->created_at)) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Last Login:</strong>
                            <span><?= date('M j, Y H:i', strtotime($user->last_login ?? $user->created_at)) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Status:</strong>
                            <span>Active</span>
                        </div>
                    </div>
                </div>
                
                <a href="settings.php" class="btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </main>

    <?php include('../includes/footer.php'); ?>
</body>
</html>