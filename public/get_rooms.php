<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/html');
$pageCSS = "/assets/css/hotels.css";

try {
    $db = new Database();
    echo "<!-- Database connection successful! -->";
    
    if (!isset($_GET['hotel_id'])) {
        die('<div class="error-message">Hotel ID not provided</div>');
    }
    
    $hotelId = (int)$_GET['hotel_id'];
    
    // First verify the hotel exists
    $db->query("SELECT id FROM hotels WHERE id = :hotel_id LIMIT 1");
    $db->bind(':hotel_id', $hotelId);
    $hotelExists = $db->single();
    
    if (!$hotelExists) {
        die('<div class="error-message">Hotel not found</div>');
    }
    
    // Fetch room types for this hotel
    $db->query("SELECT 
                id, 
                name, 
                description, 
                max_guests, 
                base_price as price, 
                breakfast_included, 
                refundable, 
                amenities, 
                size_sqm, 
                bed_type
                FROM rooms 
                WHERE hotel_id = :hotel_id 
                ORDER BY base_price");
    $db->bind(':hotel_id', $hotelId);
    $rooms = $db->resultSet();
    
    if (!empty($rooms)) {
        echo '<div class="room-types-grid">';
        foreach ($rooms as $room) {
            $amenities = !empty($room->amenities) ? json_decode($room->amenities) : [];
            
            echo '<div class="room-card" data-room-id="' . $room->id . '">';
            
            // Room Header with Price
            echo '<div class="room-header">';
            echo '<h4>' . htmlspecialchars($room->name) . '</h4>';
            echo '<div class="room-price">';
            echo '<span class="amount">$' . number_format($room->price, 2) . '</span>';
            echo '<span class="per-night">per night</span>';
            echo '</div>';
            echo '</div>';
            
            // Room Details
            echo '<div class="room-details">';
            
            // Description
            if (!empty($room->description)) {
                echo '<p class="room-description">' . htmlspecialchars($room->description) . '</p>';
            }
            
            // Specifications
            echo '<div class="room-specs">';
            echo '<div class="spec-item"><i class="fas fa-users"></i> ' . $room->max_guests . ' Guests</div>';
            
            if (!empty($room->size_sqm)) {
                echo '<div class="spec-item"><i class="fas fa-arrows-alt"></i> ' . $room->size_sqm . ' sqm</div>';
            }
            
            if (!empty($room->bed_type)) {
                echo '<div class="spec-item"><i class="fas fa-bed"></i> ' . htmlspecialchars($room->bed_type) . '</div>';
            }
            
            echo '<div class="spec-item"><i class="fas fa-door-open"></i> ' . rand(2, 5) . ' Rooms Left</div>';
            echo '</div>';
            
            // Amenities
            if (!empty($amenities)) {
                echo '<div class="room-amenities">';
                echo '<h5><i class="fas fa-star"></i> Room Amenities</h5>';
                echo '<div class="amenities-grid">';
                foreach ($amenities as $amenity) {
                    echo '<div class="amenity-item">';
                    echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($amenity);
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
            
            // Policies
            echo '<div class="room-policies">';
            echo '<div class="policy ' . ($room->breakfast_included ? 'included' : 'not-included') . '">';
            echo '<i class="fas fa-' . ($room->breakfast_included ? 'check' : 'times') . '"></i> ';
            echo 'Breakfast ' . ($room->breakfast_included ? 'Included' : 'Not Included');
            echo '</div>';
            
            echo '<div class="policy ' . ($room->refundable ? 'refundable' : 'non-refundable') . '">';
            echo '<i class="fas fa-' . ($room->refundable ? 'check' : 'times') . '"></i> ';
            echo ($room->refundable ? 'Free Cancellation' : 'Non-Refundable');
            echo '</div>';
            echo '</div>';
            
            // Book Now Button
            echo '<a href="booking.php?room_id=' . $room->id . '" class="book-now-btn">';
            echo '<i class="fas fa-calendar-check"></i> Book Now';
            echo '</a>';
            
            echo '</div>'; // Close room-details
            echo '</div>'; // Close room-card
        }
        echo '</div>'; // Close room-types-grid
    } else {
        echo '<div class="no-rooms-message">';
        echo '<div class="no-rooms-icon"><i class="fas fa-door-open"></i></div>';
        echo '<h4>No Rooms Available</h4>';
        echo '<p>All rooms are currently booked. Please check back later.</p>';
        echo '</div>';
    }
    
} catch (PDOException $e) {
    error_log("Database Error in get_rooms.php: " . $e->getMessage());
    echo '<div class="error-message">';
    echo '<div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>';
    echo '<h4>Database Error</h4>';
    echo '<p>We couldn\'t load room information. Please try again later.</p>';
    echo '</div>';
} catch (Exception $e) {
    error_log("General Error in get_rooms.php: " . $e->getMessage());
    echo '<div class="error-message">';
    echo '<div class="error-icon"><i class="fas fa-exclamation-circle"></i></div>';
    echo '<h4>Loading Error</h4>';
    echo '<p>There was an issue loading room details. Our team has been notified.</p>';
    echo '</div>';
}
?>