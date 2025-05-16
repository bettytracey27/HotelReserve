<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure session
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/Hotel-Reserve/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth_check.php';

// Add basic styles for error pages
$pageCSS = "
<style>
    .processing-page {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    .processing-icon {
        font-size: 60px;
        color: #3498db;
        margin-bottom: 20px;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 20px;
    }
    .error-message {
        color: #e74c3c;
        margin: 20px 0;
    }
</style>
";

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Validate required fields
    $requiredFields = [
        'booking_ref', 'room_id', 'hotel_id', 
        'check_in', 'check_out', 'guests', 
        'total_price', 'user_id'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $db = new Database();

    // Verify room is still available
    $db->query("SELECT 
                r.id, 
                r.max_guests,
                h.status AS hotel_status
              FROM rooms r
              JOIN hotels h ON r.hotel_id = h.id
              WHERE r.id = :room_id
              AND h.status = 'active'");
    
    $db->bind(':room_id', $_POST['room_id']);
    $room = $db->single();

    if (!$room) {
        throw new Exception("This room is no longer available");
    }

    // Verify guests don't exceed capacity
    if ($_POST['guests'] > $room->max_guests) {
        throw new Exception("This room accommodates maximum {$room->max_guests} guests");
    }

    // Check for date conflicts
    $db->query("SELECT id FROM bookings 
               WHERE room_id = :room_id
               AND (
                   (check_in <= :check_out AND check_out >= :check_in)
                   AND status IN ('confirmed', 'pending')
               )");
    
    $db->bind(':room_id', $_POST['room_id']);
    $db->bind(':check_in', $_POST['check_in']);
    $db->bind(':check_out', $_POST['check_out']);
    $existingBooking = $db->single();

    if ($existingBooking) {
        throw new Exception("This room is already booked for your selected dates");
    }

    // Calculate dates and price
    $date1 = new DateTime($_POST['check_in']);
    $date2 = new DateTime($_POST['check_out']);
    $nights = $date2->diff($date1)->days;
    $totalPrice = $nights * $room->base_price;

    // Verify price matches
    if (abs($totalPrice - $_POST['total_price']) > 0.01) {
        throw new Exception("Pricing discrepancy detected. Please refresh and try again.");
    }

    // Create booking record
    $db->query("INSERT INTO bookings (
                booking_ref,
                user_id,
                room_id,
                hotel_id,
                check_in,
                check_out,
                guests,
                total_price,
                status,
                created_at
              ) VALUES (
                :booking_ref,
                :user_id,
                :room_id,
                :hotel_id,
                :check_in,
                :check_out,
                :guests,
                :total_price,
                'pending',
                NOW()
              )");
    
    $db->bind(':booking_ref', $_POST['booking_ref']);
    $db->bind(':user_id', $_POST['user_id']);
    $db->bind(':room_id', $_POST['room_id']);
    $db->bind(':hotel_id', $_POST['hotel_id']);
    $db->bind(':check_in', $_POST['check_in']);
    $db->bind(':check_out', $_POST['check_out']);
    $db->bind(':guests', $_POST['guests']);
    $db->bind(':total_price', $_POST['total_price']);
    $db->execute();

    $bookingId = $db->lastInsertId();

    // Generate payment token (for payment gateway integration)
    $paymentToken = bin2hex(random_bytes(16));
    
    // Store payment token (you'll need a payments table)
    $db->query("INSERT INTO payments (
                booking_id,
                token,
                amount,
                status,
                created_at
              ) VALUES (
                :booking_id,
                :token,
                :amount,
                'pending',
                NOW()
              )");
    
    $db->bind(':booking_id', $bookingId);
    $db->bind(':token', $paymentToken);
    $db->bind(':amount', $_POST['total_price']);
    $db->execute();

    // Redirect to payment page (you'll need to implement this)
    $_SESSION['payment_token'] = $paymentToken;
    header("Location: payment.php?booking_ref=" . urlencode($_POST['booking_ref']));
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Booking Processing Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Show error page
    include('../includes/header.php');
    echo $pageCSS;
    ?>
    
    <main class="processing-page">
        <div class="processing-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Booking Processing Error</h2>
        <p class="error-message"><?= htmlspecialchars($e->getMessage()) ?></p>
        <p>We couldn't complete your booking. Please try again or contact support.</p>
        
        <div class="action-buttons">
            <a href="javascript:history.back()" class="btn">Try Again</a>
            <a href="/Hotel-Reserve/public/" class="btn">Return Home</a>
        </div>
    </main>
    
    <?php
    include('../includes/footer.php');
}
?>