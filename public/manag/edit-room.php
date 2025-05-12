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

// Get room ID from URL
$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$room = null;
$errors = [];

// Fetch room data
try {
    $db->query("SELECT * FROM rooms WHERE id = ? AND hotel_id = ?");
    $db->bind(1, $roomId);
    $db->bind(2, $managerHotelId);
    $room = $db->single();
    
    if (!$room) {
        $_SESSION['error'] = "Room not found or you don't have permission to edit it";
        header("Location: index.php");
        exit();
    }
    
    // Decode JSON fields
    $room->amenities = $room->amenities ? json_decode($room->amenities, true) : [];
    $room->images = $room->images ? json_decode($room->images, true) : [];
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load room data";
    header("Location: index.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $requiredFields = ['room_number', 'name', 'max_guests', 'base_price'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[$field] = "This field is required";
            }
        }
        
        if (!empty($errors)) {
            throw new Exception("Please fill all required fields");
        }
        
        // Process uploaded images
        $imagePaths = $room->images; // Keep existing images
        
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
        
        // Handle image deletions
        if (!empty($_POST['delete_images'])) {
            $imagesToDelete = $_POST['delete_images'];
            foreach ($imagesToDelete as $imageIndex) {
                if (isset($imagePaths[$imageIndex])) {
                    $imagePath = __DIR__ . '/../..' . $imagePaths[$imageIndex];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    unset($imagePaths[$imageIndex]);
                }
            }
            $imagePaths = array_values($imagePaths); // Reindex array
        }

        // Prepare amenities
        $amenities = [];
        if (!empty($_POST['amenities'])) {
            $amenities = array_map('trim', explode(',', $_POST['amenities']));
            $amenities = array_filter($amenities);
        }

        // Update room data
        $db->query("UPDATE rooms SET 
            room_number = ?, 
            name = ?, 
            description = ?, 
            max_guests = ?, 
            base_price = ?, 
            breakfast_included = ?, 
            refundable = ?, 
            amenities = ?, 
            images = ?, 
            size_sqm = ?, 
            bed_type = ?, 
            type = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND hotel_id = ?");
        
        $db->bind(1, $_POST['room_number']);
        $db->bind(2, $_POST['name']);
        $db->bind(3, $_POST['description']);
        $db->bind(4, (int)$_POST['max_guests']);
        $db->bind(5, (float)$_POST['base_price']);
        $db->bind(6, isset($_POST['breakfast_included']) ? 1 : 0);
        $db->bind(7, isset($_POST['refundable']) ? 1 : 0);
        $db->bind(8, json_encode($amenities));
        $db->bind(9, json_encode($imagePaths));
        $db->bind(10, !empty($_POST['size_sqm']) ? (int)$_POST['size_sqm'] : null);
        $db->bind(11, $_POST['bed_type']);
        $db->bind(12, $_POST['type']);
        $db->bind(13, $roomId);
        $db->bind(14, $managerHotelId);
        
        $db->execute();

        $_SESSION['success'] = "Room updated successfully";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        error_log("Error updating room: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update room: " . $e->getMessage();
        header("Location: edit-room.php?id=" . $roomId);
        exit();
    }
}

// Set page title
$pageTitle = "Edit Room";
include __DIR__ . '/../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-preview {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        .image-preview img {
            height: 150px;
            width: auto;
            border-radius: 5px;
        }
        .delete-checkbox {
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .amenities-list {
            list-style-type: none;
            padding-left: 0;
        }
        .amenities-list li:before {
            content: "• ";
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4"><i class="fas fa-edit me-2"></i>Edit Room</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form action="edit-room.php?id=<?= $roomId ?>" method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="room_number" class="form-label">Room Number*</label>
                    <input type="text" class="form-control <?= isset($errors['room_number']) ? 'is-invalid' : '' ?>" 
                           id="room_number" name="room_number" value="<?= htmlspecialchars($room->room_number) ?>" required>
                    <?php if (isset($errors['room_number'])): ?>
                        <div class="invalid-feedback"><?= $errors['room_number'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="name" class="form-label">Room Name*</label>
                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                           id="name" name="name" value="<?= htmlspecialchars($room->name) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= $errors['name'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="type" class="form-label">Room Type*</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="Standard" <?= $room->type === 'Standard' ? 'selected' : '' ?>>Standard</option>
                        <option value="Deluxe" <?= $room->type === 'Deluxe' ? 'selected' : '' ?>>Deluxe</option>
                        <option value="Suite" <?= $room->type === 'Suite' ? 'selected' : '' ?>>Suite</option>
                        <option value="Executive" <?= $room->type === 'Executive' ? 'selected' : '' ?>>Executive</option>
                        <option value="Family" <?= $room->type === 'Family' ? 'selected' : '' ?>>Family</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="bed_type" class="form-label">Bed Type*</label>
                    <select class="form-select" id="bed_type" name="bed_type" required>
                        <option value="Single" <?= $room->bed_type === 'Single' ? 'selected' : '' ?>>Single</option>
                        <option value="Double" <?= $room->bed_type === 'Double' ? 'selected' : '' ?>>Double</option>
                        <option value="Queen" <?= $room->bed_type === 'Queen' ? 'selected' : '' ?>>Queen</option>
                        <option value="King" <?= $room->bed_type === 'King' ? 'selected' : '' ?>>King</option>
                        <option value="Twin" <?= $room->bed_type === 'Twin' ? 'selected' : '' ?>>Twin</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="max_guests" class="form-label">Max Guests*</label>
                    <input type="number" class="form-control <?= isset($errors['max_guests']) ? 'is-invalid' : '' ?>" 
                           id="max_guests" name="max_guests" min="1" value="<?= htmlspecialchars($room->max_guests) ?>" required>
                    <?php if (isset($errors['max_guests'])): ?>
                        <div class="invalid-feedback"><?= $errors['max_guests'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="base_price" class="form-label">Base Price ($/night)*</label>
                    <input type="number" class="form-control <?= isset($errors['base_price']) ? 'is-invalid' : '' ?>" 
                           id="base_price" name="base_price" min="0" step="0.01" 
                           value="<?= htmlspecialchars($room->base_price) ?>" required>
                    <?php if (isset($errors['base_price'])): ?>
                        <div class="invalid-feedback"><?= $errors['base_price'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="size_sqm" class="form-label">Size (sqm)</label>
                    <input type="number" class="form-control" id="size_sqm" name="size_sqm" min="0" 
                           value="<?= htmlspecialchars($room->size_sqm) ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($room->description) ?></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="breakfast_included" 
                               name="breakfast_included" value="1" <?= $room->breakfast_included ? 'checked' : '' ?>>
                        <label class="form-check-label" for="breakfast_included">Breakfast Included</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="refundable" 
                               name="refundable" value="1" <?= $room->refundable ? 'checked' : '' ?>>
                        <label class="form-check-label" for="refundable">Refundable</label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="amenities" class="form-label">Amenities (comma separated)</label>
                <input type="text" class="form-control" id="amenities" name="amenities" 
                       value="<?= htmlspecialchars(implode(', ', $room->amenities)) ?>">
                <small class="text-muted">Example: WiFi, TV, AC, Mini Bar</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Existing Images</label>
                <div class="d-flex flex-wrap">
                    <?php if (!empty($room->images)): ?>
                        <?php foreach ($room->images as $index => $image): ?>
                            <div class="image-preview">
                                <img src="<?= htmlspecialchars($image) ?>" alt="Room Image" class="img-thumbnail">
                                <div class="form-check delete-checkbox">
                                    <input class="form-check-input" type="checkbox" 
                                           name="delete_images[]" value="<?= $index ?>" id="delete_<?= $index ?>">
                                    <label class="form-check-label" for="delete_<?= $index ?>">Delete</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No images uploaded yet</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="room_images" class="form-label">Add More Images</label>
                <input class="form-control" type="file" id="room_images" name="room_images[]" multiple accept="image/*">
                <small class="text-muted">Upload additional images (JPEG, PNG)</small>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include __DIR__ . '/../../includes/footer.php'; ?>