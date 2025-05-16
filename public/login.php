<?php 

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../includes/header.php'); 
?>
<main class="login-page">
    <div class="auth-container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="auth-form">
            <h2>Login to Your Account</h2>
            
            <form method="POST" action="include/authenticate.php">
                <input type="hidden" name="login" value="1">
                
                <div class="form-group email-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group password-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="auth-btn">Login</button>
            </form>
            
            <div class="auth-links">
                <a href="forgot-password.php">Forgot Password?</a>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
        
        <div class="auth-image">
            <div class="auth-decoration decoration-1"></div>
            <div class="auth-decoration decoration-2"></div>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>