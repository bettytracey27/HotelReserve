<?php 
include('../includes/header.php'); 


$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<main class="register-page">
    <div class="auth-container">
        <div class="auth-form">
            <h2>Create an Account</h2>
            
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="include/authenticate.php" method="POST">
                <input type="hidden" name="register" value="1">
                
                <!-- Personal Information -->
                <div class="form-group">
                    <label for="first_name">First Name*</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name*</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                </div>
                
                <!-- Contact Information -->
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="primary_phone">Primary Phone*</label>
                    <input type="tel" id="primary_phone" name="primary_phone" required
                           value="<?= htmlspecialchars($_POST['primary_phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="secondary_phone">Secondary Phone</label>
                    <input type="tel" id="secondary_phone" name="secondary_phone"
                           value="<?= htmlspecialchars($_POST['secondary_phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone (Optional)</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                
                <!-- Account Credentials -->
                <div class="form-group">
                    <label for="username">Username*</label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password*</label>
                    <input type="password" id="password" name="password" required
                           minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                           title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                    <small class="password-hint">Minimum 8 characters with at least one uppercase, one lowercase, and one number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password*</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <!-- Hidden role field - all registrations are users -->
                <input type="hidden" name="role" value="user">
                
                <div class="form-group terms">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="/terms.php">Terms of Service</a> and <a href="/privacy.php">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
        
        <div class="auth-image">
            <div class="auth-decoration decoration-1"></div>
            <div class="auth-decoration decoration-2"></div>
        </div>
    </div>
</main>

<script>
// Simple client-side password match validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
    }
});
</script>

<?php include('../includes/footer.php'); ?>