<?php
// includes/admin-functions.php

function connectDB() {
    try {
        return new PDO(
            'mysql:host=localhost;dbname=hotel_reservation_system;charset=utf8mb4',
            'root',
            ''
        );
    } catch (PDOException $e) {
        die("❌ DB Connection failed: " . $e->getMessage());
    }
}

// 📥 Add a new hotel
function addHotel($name, $slug, $location_id, $price, $stars, $description, $amenities) {
    $db = connectDB();
    $query = "INSERT INTO hotels (name, slug, location_id, price_per_night, stars, description, amenities)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    return $stmt->execute([$name, $slug, $location_id, $price, $stars, $description, $amenities]);
}

// 🗑️ Delete a hotel by ID
function deleteHotel($id) {
    $db = connectDB();
    $stmt = $db->prepare("DELETE FROM hotels WHERE id = ?");
    return $stmt->execute([$id]);
}

// 📝 Update hotel details
function updateHotel($id, $name, $slug, $location_id, $price, $stars, $description, $amenities) {
    $db = connectDB();
    $query = "UPDATE hotels SET 
                name = ?, 
                slug = ?, 
                location_id = ?, 
                price_per_night = ?, 
                stars = ?, 
                description = ?, 
                amenities = ?
              WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$name, $slug, $location_id, $price, $stars, $description, $amenities, $id]);
}

// 📋 Get all hotels
function getAllHotels() {
    $db = connectDB();
    $stmt = $db->query("SELECT * FROM hotels");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 🔍 Get a hotel by ID
function getHotelById($id) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
