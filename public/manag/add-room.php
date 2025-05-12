<?php
// Start session and error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required files
require_once __DIR__ . '/../../includes/db.php';
require_once 'auth.php';

// Check if user is manager
requireManager();

// Initialize database
$db = new Database();
$managerHotelId = getManagerHotelId($_SESSION['user']['id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate hotel_id belongs to manager
        $hotelId = (int)$_POST['hotel_id'];
        if ($hotelId !== $managerHotelId) {
            throw new Exception("Invalid hotel assignment");
        }

        // Process uploaded images
        $imagePaths = [];
        if (!empty($_FILES['room_images']['name'][0])) {
            $uploadDir = __DIR__ . '/../../uploads/rooms/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($_FILES['room_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['room_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '_' . basename($_FILES['room_images']['name'][$key]);
                    $targetPath = $uploadDir . $fileName;
                    
                    // Validate image
                    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                    $allowedTypes = ['jpg', 'jpeg', 'png'];
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception("Only JPG, JPEG, PNG files are allowed.");
                    }
                    
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $imagePaths[] = '/uploads/rooms/' . $fileName;
                    }
                }
            }
        }

        // Prepare amenities
        $amenities = [];
        if (!empty($_POST['amenities'])) {
            $amenities = array_map('trim', explode(',', $_POST['amenities']));
            $amenities = array_filter($amenities);
        }

        // Insert room data
        $db->query("INSERT INTO rooms (
            hotel_id, room_number, name, description, max_guests, 
            base_price, breakfast_included, refundable, amenities, 
            images, size_sqm, bed_type, type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $db->bind(1, $hotelId);
        $db->bind(2, $_POST['room_number']);
        $db->bind(3, $_POST['name']);
        $db->bind(4, $_POST['description']);
        $db->bind(5, (int)$_POST['max_guests']);
        $db->bind(6, (float)$_POST['base_price']);
        $db->bind(7, isset($_POST['breakfast_included']) ? 1 : 0);
        $db->bind(8, isset($_POST['refundable']) ? 1 : 0);
        $db->bind(9, json_encode($amenities));
        $db->bind(10, json_encode($imagePaths));
        $db->bind(11, !empty($_POST['size_sqm']) ? (int)$_POST['size_sqm'] : null);
        $db->bind(12, $_POST['bed_type']);
        $db->bind(13, $_POST['type']);
        
        $db->execute();

        $_SESSION['success'] = "Room added successfully";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        error_log("Error adding room: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add room: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// If not POST request, redirect
header("Location: index.php");
exit();