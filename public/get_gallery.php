<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/html');

try {
    if (!isset($_GET['hotel_id'])) {
        throw new Exception('Hotel ID not provided');
    }

    $hotelId = (int)$_GET['hotel_id'];
    $db = new Database();
    
    $db->query("SELECT image_path FROM hotel_images WHERE hotel_id = :hotel_id ORDER BY is_featured DESC");
    $db->bind(':hotel_id', $hotelId);
    $images = $db->resultSet();
    
    if (!empty($images)) {
        echo '<div class="image-gallery">';
        foreach ($images as $image) {
            echo '<div class="gallery-item">';
            echo '<img src="' . htmlspecialchars($image->image_path) . '" alt="Hotel Image">';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No images available for this hotel.</p>';
    }
} catch (Exception $e) {
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>