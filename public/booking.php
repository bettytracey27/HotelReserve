<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure secure session
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

// Handle form submission to update dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dates'])) {
    $checkIn = $_POST['check_in'] ?? '';
    $checkOut = $_POST['check_out'] ?? '';
 
    header("Location: booking_confirmation.php?room_id={$_GET['room_id']}&check_in={$checkIn}&check_out={$checkOut}&guests={$_GET['guests']}");
    exit();
}

try {
    $db = new Database();
    
    // Validate inputs
    if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
        throw new Exception("Invalid room specified");
    }
    
    $roomId = (int)$_GET['room_id'];
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $guests = (int)($_GET['guests'] ?? 1);
    
    // Get room details
    $query = "SELECT 
                r.id,
                r.hotel_id,
                r.room_number,
                r.name AS room_name,
                r.max_guests,
                r.base_price,
                h.name AS hotel_name,
                h.min_stay
              FROM rooms r
              JOIN hotels h ON r.hotel_id = h.id
              WHERE r.id = :room_id AND h.status = 'active'";
    
    $db->query($query);
    $db->bind(':room_id', $roomId);
    $room = $db->single();
    
    if (!$room) {
        throw new Exception("Room not available for booking");
    }
    
    // Validate guests
    if ($guests > $room->max_guests) {
        throw new Exception("This room accommodates maximum {$room->max_guests} guests");
    }
    
    // Calculate price and nights
    $nights = 1;
    $totalPrice = $room->base_price;
    
    if ($checkIn && $checkOut) {
        try {
            $date1 = new DateTime($checkIn);
            $date2 = new DateTime($checkOut);
            $interval = $date2->diff($date1);
            $nights = $interval->days;
            
            // Validate minimum stay
            if (isset($room->min_stay) && $nights < $room->min_stay) {
                throw new Exception("Minimum stay required: {$room->min_stay} nights");
            }
            
            $totalPrice = $nights * $room->base_price;
        } catch (Exception $e) {
            throw new Exception("Invalid dates: " . $e->getMessage());
        }
    }
    
    // Get user details - updated to include primary_phone
    $db->query("SELECT first_name, last_name, email, phone, primary_phone FROM users WHERE id = :user_id");
    $db->bind(':user_id', $_SESSION['user']['id']);
    $user = $db->single();
    
    if (!$user) {
        throw new Exception("User information not found");
    }

    // Generate booking reference
    $bookingRef = 'HR-' . strtoupper(substr(uniqid(), -8)) . '-' . date('Ymd');
    
    // Generate QR code data with all required information
    $qrData = [
        'booking_ref' => $bookingRef,
        'guest_name' => $user->first_name . ' ' . $user->last_name,
        'hotel' => $room->hotel_name,
        'room' => $room->room_name,
        'room_number' => $room->room_number,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'nights' => $nights,
        'guests' => $guests,
        'total' => '$' . number_format($totalPrice, 2),
        'expires' => date('Y-m-d H:i:s', strtotime('+24 hours'))
    ];
    
    // QR Code Generation
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode(json_encode($qrData));

    include('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - HotelReserve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .booking-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .confirmation-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .confirmation-header i {
            color: #27ae60;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .confirmation-alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .confirmation-alert i {
            color: #ffc107;
            font-size: 1.5em;
        }
        .confirmation-details {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .booking-summary, .user-details {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .booking-summary h2, .user-details h2 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .detail-item {
            margin-bottom: 15px;
            display: flex;
        }
        .detail-label {
            font-weight: 600;
            width: 120px;
            color: #555;
        }
        .detail-value {
            flex: 1;
        }
        .total-price {
            font-weight: bold;
            color: #27ae60;
            font-size: 1.2em;
        }
        .confirmation-number {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #e9f7fe;
            border-radius: 8px;
            margin: 20px 0;
        }
        .expiry-notice {
            color: #d35400;
            font-weight: bold;
        }
        .qr-section {
            margin: 30px 0;
            padding: 20px;
            background: #f0f8ff;
            border-radius: 8px;
        }
        .qr-container {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .qr-info {
            flex: 1;
        }
        .qr-info ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn-confirm {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn-confirm:hover {
            background: #219653;
        }
        .btn-print {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            margin-left: 15px;
        }
        .btn-print:hover {
            background: #2980b9;
        }
        .btn-update {
            background: #f39c12;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            margin-right: 15px;
        }
        .btn-update:hover {
            background: #e67e22;
        }
        .terms-confirm {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .date-edit-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .date-input-group {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .date-input {
            flex: 1;
            min-width: 200px;
        }
        .date-input label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .date-input input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        @media (max-width: 768px) {
            .confirmation-details {
                flex-direction: column;
            }
            .qr-container {
                flex-direction: column;
                text-align: center;
            }
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .btn-print, .btn-update {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="confirmation-header">
            <i class="fas fa-check-circle"></i>
            <h1>Booking Confirmation</h1>
            <p>Please review and confirm your reservation details</p>
        </div>
        
        <div class="confirmation-alert">
            <i class="fas fa-clock"></i>
            <p>Your reservation will be held for 24 hours. Complete payment to confirm your booking.</p>
        </div>
        
        <!-- Date Editing Form -->
        <form method="post" action="" class="date-edit-form">
            <h2><i class="fas fa-calendar-alt"></i> Edit Stay Dates</h2>
            <div class="date-input-group">
                <div class="date-input">
                    <label for="check_in">Check-in Date</label>
                    <input type="text" id="check_in" name="check_in" value="<?= htmlspecialchars($checkIn) ?>" required>
                </div>
                <div class="date-input">
                    <label for="check_out">Check-out Date</label>
                    <input type="text" id="check_out" name="check_out" value="<?= htmlspecialchars($checkOut) ?>" required>
                </div>
            </div>
            <button type="submit" name="update_dates" class="btn-update">
                <i class="fas fa-sync-alt"></i> Update Dates
            </button>
            <p><small>Updating dates will recalculate your total price and update the QR code</small></p>
        </form>
        
        <div class="confirmation-details">
            <div class="booking-summary">
                <h2><i class="fas fa-hotel"></i> Room Details</h2>
                <div class="detail-item">
                    <span class="detail-label">Hotel:</span>
                    <span class="detail-value"><?= htmlspecialchars($room->hotel_name) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value"><?= htmlspecialchars($room->room_name) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Room Number:</span>
                    <span class="detail-value"><?= htmlspecialchars($room->room_number) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Check-in:</span>
                    <span class="detail-value"><?= htmlspecialchars($checkIn) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Check-out:</span>
                    <span class="detail-value"><?= htmlspecialchars($checkOut) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Nights:</span>
                    <span class="detail-value"><?= $nights ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Guests:</span>
                    <span class="detail-value"><?= $guests ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Total Price:</span>
                    <span class="detail-value total-price">$<?= number_format($totalPrice, 2) ?></span>
                </div>
            </div>
            
            <div class="user-details">
                <h2><i class="fas fa-user-circle"></i> Your Information</h2>
                <div class="detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($user->first_name . ' ' . $user->last_name) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($user->email) ?></span>
                </div>
                <?php if (!empty($user->phone)): ?>
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($user->phone) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($user->primary_phone)): ?>
                <div class="detail-item">
                    <span class="detail-label">Primary Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($user->primary_phone) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="confirmation-number">
            <i class="fas fa-tag"></i>
            <span>Booking Reference: <strong><?= $bookingRef ?></strong></span>
            <span class="expiry-notice">Expires: <?= date('M j, Y g:i A', strtotime('+24 hours')) ?></span>
        </div>
        
        <div class="qr-section">
            <div class="qr-container">
                <img src="<?= $qrCodeUrl ?>" alt="Booking QR Code" id="qr-code-image">
                <div class="qr-info">
                    <h3><i class="fas fa-qrcode"></i> Your Booking QR Code</h3>
                    <p>Scan this code at check-in. It contains:</p>
                    <ul>
                        <li>Your full name and contact information</li>
                        <li>Hotel and room details</li>
                        <li>Exact check-in/out dates (<?= $nights ?> nights)</li>
                        <li>Total price and booking reference</li>
                        <li>Expiration time if not paid</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <form method="POST" action="process_booking.php">
                <input type="hidden" name="booking_ref" value="<?= $bookingRef ?>">
                <input type="hidden" name="room_id" value="<?= $room->id ?>">
                <input type="hidden" name="hotel_id" value="<?= $room->hotel_id ?>">
                <input type="hidden" name="check_in" value="<?= htmlspecialchars($checkIn) ?>">
                <input type="hidden" name="check_out" value="<?= htmlspecialchars($checkOut) ?>">
                <input type="hidden" name="guests" value="<?= $guests ?>">
                <input type="hidden" name="total_price" value="<?= $totalPrice ?>">
                <input type="hidden" name="user_id" value="<?= $_SESSION['user']['id'] ?>">
                
                <div class="terms-confirm">
                    <input type="checkbox" id="confirm_details" name="confirm_details" required>
                    <label for="confirm_details">I confirm all details are correct and accept the cancellation policy</label>
                </div>
                
                <button type="submit" class="btn-confirm">
                    <i class="fas fa-credit-card"></i> Proceed to Payment
                </button>
                <button type="button" class="btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Confirmation
                </button>
            </form>
        </div>
    </div>

    <!-- Date Picker Library -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("#check_in", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr) {
                // Set minimum check-out date to day after check-in
                const checkOutPicker = document.querySelector("#check_out")._flatpickr;
                if (selectedDates.length > 0) {
                    const minDate = new Date(selectedDates[0]);
                    minDate.setDate(minDate.getDate() + 1);
                    checkOutPicker.set("minDate", minDate);
                    
                    // If current check-out is before new check-in, update it
                    if (checkOutPicker.selectedDates[0] && 
                        checkOutPicker.selectedDates[0] <= selectedDates[0]) {
                        checkOutPicker.setDate(minDate);
                    }
                }
            }
        });
        
        flatpickr("#check_out", {
            minDate: "tomorrow",
            dateFormat: "Y-m-d"
        });
    </script>
</body>
</html>
<?php
include('../includes/footer.php');

} catch (Exception $e) {
    include('../includes/header.php');
    ?>
    <div class="booking-container" style="text-align: center;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e74c3c;"></i>
        <h1>Booking Error</h1>
        <p style="color: #e74c3c; font-size: 1.2rem;"><?= htmlspecialchars($e->getMessage()) ?></p>
        <div style="margin-top: 30px;">
            <a href="javascript:history.back()" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            <a href="/Hotel-Reserve/public/" style="background: #2ecc71; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; margin-left: 10px;">
                <i class="fas fa-home"></i> Return Home
            </a>
        </div>
    </div>
    <?php
    include('../includes/footer.php');
}
?>