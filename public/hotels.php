<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pageCSS = "/assets/css/hotels.css";

try {
    $db = new Database();
    
    if (!isset($_GET['location'])) {
        header("Location: destinations.php?error=no_location");
        exit;
    }
    
    $locationSlug = trim($_GET['location']);
    
    $db->query("SELECT id, name, description FROM locations WHERE slug = :slug LIMIT 1");
    $db->bind(':slug', $locationSlug);
    $location = $db->single();
    
    if (!$location) {
        header("Location: destinations.php?error=location_not_found&slug=" . urlencode($locationSlug));
        exit;
    }
    
    $pageTitle = "Hotels in " . htmlspecialchars($location->name);
    include('../includes/header.php');
    
    $db->query("
        SELECT 
            h.id, 
            h.name, 
            h.description,
            h.price_per_night,
            h.stars,
            (SELECT hi.image_path FROM hotel_images hi 
             WHERE hi.hotel_id = h.id AND hi.is_featured = 1 LIMIT 1) as featured_image,
            (SELECT COUNT(*) FROM hotel_images hi WHERE hi.hotel_id = h.id) as image_count,
            (SELECT GROUP_CONCAT(hi.image_path SEPARATOR '|||') 
             FROM hotel_images hi WHERE hi.hotel_id = h.id) as all_images
        FROM hotels h 
        WHERE h.location_id = :location_id
    ");
    $db->bind(':location_id', $location->id);
    $hotels = $db->resultSet();
    
    echo '<main class="hotels-container">';
    echo '<h1>Hotels in ' . htmlspecialchars($location->name) . '</h1>';
    echo '<p class="location-description">' . htmlspecialchars($location->description) . '</p>';
    
    if (!empty($hotels)) {
        foreach ($hotels as $hotel) {
            $featuredImage = !empty($hotel->featured_image) 
                ? '../' . ltrim($hotel->featured_image, '/')
                : '../assets/images/hotels/default.jpg';
            
            $startingPrice = $hotel->price_per_night ? number_format($hotel->price_per_night, 2) : 'N/A';
            
            echo '<div class="hotel-card">';
            
            // Hotel Image
            echo '<div class="hotel-image-container">';
            echo '<div class="card-image" style="background-image: url(\'' . htmlspecialchars($featuredImage) . '\')">';
            
            if ($hotel->image_count > 1) {
                echo '<div class="image-gallery-preview">';
                $images = explode('|||', $hotel->all_images);
                foreach (array_slice($images, 0, 4) as $image) {
                    echo '<div class="gallery-thumb" style="background-image: url(\'../' . htmlspecialchars(ltrim($image, '/')) . '\')"></div>';
                }
                if (count($images) > 4) {
                    echo '<div class="gallery-thumb more">+' . (count($images) - 4) . '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            
            // Hotel Info
            echo '<div class="hotel-main-info">';
            echo '<h2>' . htmlspecialchars($hotel->name) . '</h2>';
            
            echo '<div class="stars">';
            for ($i = 1; $i <= 5; $i++) {
                echo $i <= $hotel->stars ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
            }
            echo '</div>';
            
            echo '<p>' . htmlspecialchars($hotel->description) . '</p>';
            echo '<div class="card-meta">';
            echo '<span class="price">From $' . $startingPrice . '/night</span>';
            echo '</div>';
            
            // Action Buttons
            echo '<div class="hotel-actions">';
            echo '<button class="show-rooms-btn" data-hotel-id="' . $hotel->id . '">View Room Options</button>';
            if ($hotel->image_count > 1) {
                echo '<button class="view-gallery-btn" data-hotel-id="' . $hotel->id . '">View Gallery</button>';
            }
            echo '</div>';
            echo '</div>';
            
            // Room Types Container (initially hidden)
            echo '<div class="room-types-container" id="rooms-' . $hotel->id . '" style="display:none;">';
            echo '<h3>Available Room Types</h3>';
            echo '<div class="room-types-list" id="room-list-' . $hotel->id . '">';
            echo '<!-- Room options will load here via AJAX -->';
            echo '</div>';
            echo '</div>';
            
            // Gallery Container (initially hidden)
            if ($hotel->image_count > 1) {
                echo '<div class="gallery-container" id="gallery-' . $hotel->id . '" style="display:none;">';
                echo '<h3>Photo Gallery</h3>';
                echo '<div class="gallery-images" id="gallery-images-' . $hotel->id . '">';
                
                $images = explode('|||', $hotel->all_images);
                foreach ($images as $image) {
                    $imagePath = '../' . ltrim($image, '/');
                    echo '<div class="gallery-item">';
                    echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($hotel->name) . '" onerror="this.src=\'../assets/images/hotels/default.jpg\'">';
                    echo '</div>';
                }
                
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>'; // Close hotel-card
        }
    } else {
        echo '<p class="no-hotels">No hotels found in this location.</p>';
    }
    
    echo '</main>';
    
    include('../includes/footer.php');
    
    // JavaScript for dynamic loading
    echo '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Room options toggle
        document.querySelectorAll(".show-rooms-btn").forEach(button => {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                const hotelId = this.getAttribute("data-hotel-id");
                const roomContainer = document.getElementById("rooms-" + hotelId);
                const roomList = document.getElementById("room-list-" + hotelId);
                
                if (roomContainer.style.display === "none" || !roomContainer.style.display) {
                    roomContainer.style.display = "block";
                    
                    if (roomList.innerHTML.trim() === "" || roomList.innerHTML.includes("<!-- Room options will load here via AJAX -->")) {
                        fetch("get_rooms.php?hotel_id=" + hotelId)
                            .then(response => response.text())
                            .then(data => {
                                roomList.innerHTML = data;
                            })
                            .catch(error => {
                                roomList.innerHTML = \'<div class="error-message">Error loading rooms. Please try again.</div>\';
                            });
                    }
                } else {
                    roomContainer.style.display = "none";
                }
                
                this.textContent = roomContainer.style.display === "none" ? "View Room Options" : "Hide Room Options";
            });
        });
        
        // Gallery toggle
        document.querySelectorAll(".view-gallery-btn").forEach(button => {
            button.addEventListener("click", function(e) {
                e.preventDefault();
                const hotelId = this.getAttribute("data-hotel-id");
                const galleryContainer = document.getElementById("gallery-" + hotelId);
                
                if (galleryContainer.style.display === "none" || !galleryContainer.style.display) {
                    galleryContainer.style.display = "block";
                } else {
                    galleryContainer.style.display = "none";
                }
                
                this.textContent = galleryContainer.style.display === "none" ? "View Gallery" : "Hide Gallery";
            });
        });
    });
    </script>
    ';

} catch (PDOException $e) {
    $pageTitle = "Database Error";
    include('../includes/header.php');
    echo '<main class="error"><p>Database error: ' . htmlspecialchars($e->getMessage()) . '</p></main>';
    include('../includes/footer.php');
} catch (Exception $e) {
    header("Location: destinations.php?error=server_error");
    exit;
}
?>