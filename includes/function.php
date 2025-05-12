<?php
function getFeaturedHotels($limit = 4) {
    global $db;
    $db->query("SELECT h.*, l.name as location_name 
               FROM hotels h 
               JOIN locations l ON h.location_id = l.id 
               WHERE l.is_featured = 1 
               ORDER BY h.stars DESC 
               LIMIT ?", [$limit]);
    return $db->resultSet();
}

function getHotelRooms($hotelId) {
    global $db;
    $db->query("SELECT * FROM room_types 
               WHERE hotel_id = ? 
               ORDER BY base_price ASC", [$hotelId]);
    return $db->resultSet();
}
?>