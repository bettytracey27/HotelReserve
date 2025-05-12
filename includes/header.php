<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelReserve Ethiopia</title>
    <link href="https://fonts.googleapis.com/css2?family=Abyssinica+SIL&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/home.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/destinations.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/hotels.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/contact.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/login.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/about.css">
    <link rel="stylesheet" href="/Hotel-Reserve/assets/css/header.css">
</head>
<body>

<nav>
  <div class="container">
    <a href="index.php" class="logo">
      <div class="logo-img">HR</div>
      <span class="logo-text">HotelReserve</span>
    </a>

    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="destinations.php">Destinations</a></li>
      <li><a href="about.php">About</a></li>
     
      
      <?php if (isset($_SESSION['user'])): ?>
        <!-- Logged-in User Menu -->
        <li class="user-dropdown">
          <a href="#" class="user-profile" id="userDropdownBtn">
            <i class="fas fa-user-circle"></i> 
            <?= htmlspecialchars($_SESSION['user']['username']) ?>
          </a>
          <div class="dropdown-menu" id="userDropdown">
            <div class="dropdown-header">
              <h6><?= htmlspecialchars($_SESSION['user']['username']) ?></h6>
              <small><?= htmlspecialchars($_SESSION['user']['email']) ?></small>
            </div>
            <div class="dropdown-divider"></div>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
              <a class="dropdown-item" href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
              <a class="dropdown-item" href="manage-hotels.php"><i class="fas fa-hotel"></i> Manage Hotels</a>
              <a class="dropdown-item" href="manage-users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
            <?php elseif ($_SESSION['user']['role'] === 'manager'): ?>
              <a class="dropdown-item" href="manager-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
              <a class="dropdown-item" href="manage-bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
            <?php endif; ?>
            <a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </li>
      <?php else: ?>
        <!-- Guest Menu -->
        <li><a href="login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
        <li><a href="register.php" class="register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<!-- Flash Messages -->
<?php if (isset($_SESSION['error'])): ?>
  <div class="flash-message error">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
    <span class="close-btn" onclick="this.parentElement.remove()">&times;</span>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
  <div class="flash-message success">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
    <span class="close-btn" onclick="this.parentElement.remove()">&times;</span>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('userDropdownBtn');
    const dropdownMenu = document.getElementById('userDropdown');
    
    if (dropdownBtn && dropdownMenu) {
        // Toggle dropdown when clicking the username
        dropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Close all other dropdowns first
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                if (menu !== dropdownMenu) menu.classList.remove('show');
            });
            // Toggle current dropdown
            dropdownMenu.classList.toggle('show');
        });
        
        // Close when clicking anywhere else
        document.addEventListener('click', function(e) {
            if (!dropdownMenu.contains(e.target) && e.target !== dropdownBtn) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});
</script>