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

// Set page title
$pageTitle = "Manager Dashboard";
include __DIR__ . '/../../includes/header.php';

// Initialize variables with default values
$hotel = (object)[
    'name' => 'No Hotel Assigned',
    'location_name' => 'Unknown Location'
];

$rooms = [];
$recentReservations = [];
$staffCount = 0;

try {
    // Get manager's hotel data with null checks
    $db->query("SELECT h.*, l.name as location_name 
               FROM hotels h
               LEFT JOIN locations l ON h.location_id = l.id
               WHERE h.id = ?");
    $db->bind(1, $managerHotelId);
    $hotelData = $db->single();
    if ($hotelData) {
        $hotel = $hotelData;
    }

    // Get rooms for this hotel with all fields
    $db->query("SELECT * FROM rooms WHERE hotel_id = ? ORDER BY room_number");
    $db->bind(1, $managerHotelId);
    $rooms = $db->resultSet() ?: [];

    // Get recent reservations for this hotel
    $db->query("SELECT r.*, u.first_name, u.last_name, rm.room_number 
               FROM reservations r
               JOIN users u ON r.user_id = u.id
               JOIN rooms rm ON r.room_id = rm.id
               WHERE rm.hotel_id = ?
               ORDER BY r.check_in_date DESC
               LIMIT 5");
    $db->bind(1, $managerHotelId);
    $recentReservations = $db->resultSet() ?: [];

    // Get staff count (managers assigned to this hotel)
    $db->query("SELECT COUNT(*) as count FROM users WHERE hotel_id = ? AND role = 'manager'");
    $db->bind(1, $managerHotelId);
    $staffCount = $db->single()->count ?? 0;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load dashboard data. Please try again.";
}

// Handle room actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_room'])) {
            $roomId = (int)$_POST['room_id'];
            
            // Check if room belongs to manager's hotel
            $db->query("SELECT id FROM rooms WHERE id = ? AND hotel_id = ?");
            $db->bind(1, $roomId);
            $db->bind(2, $managerHotelId);
            $validRoom = $db->single();
            
            if ($validRoom) {
                $db->query("DELETE FROM rooms WHERE id = ?");
                $db->bind(1, $roomId);
                $db->execute();
                
                $_SESSION['success'] = "Room deleted successfully";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error'] = "Invalid room or not authorized";
            }
        }
    } catch (PDOException $e) {
        error_log("Room deletion error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete room. Please try again.";
    }
}
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
        .hotel-header {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .room-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
            height: 100%;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .action-buttons {
            margin-top: 10px;
        }
        .room-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .amenities-list {
            list-style-type: none;
            padding-left: 0;
        }
        .amenities-list li:before {
            content: "• ";
            color: #0d6efd;
        }
        .no-image-placeholder {
            height: 200px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Display messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Dashboard Header -->
        <div class="hotel-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-hotel me-2"></i><?= htmlspecialchars($hotel->name) ?></h1>
                    <p class="lead mb-1">
                        <i class="fas fa-map-marker-alt me-1"></i> 
                        <?= htmlspecialchars($hotel->location_name) ?>
                    </p>
                </div>
                <div>
                    <a href="edit-hotel.php" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Hotel
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Rooms</h5>
                        <h2><?= count($rooms) ?></h2>
                        <button class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                            <i class="fas fa-plus"></i> Add Room
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Reservations</h5>
                        <h2><?= count($recentReservations) ?></h2>
                        <a href="reservations.php" class="text-white">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title">Occupancy</h5>
                        <h2>85%</h2>
                        <a href="reports.php" class="text-dark">View Report</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reservations -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Reservations</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentReservations)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReservations as $reservation): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reservation->first_name.' '.$reservation->last_name) ?></td>
                                    <td><?= htmlspecialchars($reservation->room_number ?? 'N/A') ?></td>
                                    <td><?= date('M d, Y', strtotime($reservation->check_in_date)) ?></td>
                                    <td><?= date('M d, Y', strtotime($reservation->check_out_date)) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            ($reservation->status ?? '') === 'confirmed' ? 'success' : 
                                            (($reservation->status ?? '') === 'pending' ? 'warning' : 'secondary')
                                        ?>">
                                            <?= ucfirst($reservation->status ?? 'unknown') ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No recent reservations found.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rooms Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Rooms</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus me-1"></i> Add Room
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $room): 
                            $roomImages = $room->images ? json_decode($room->images, true) : [];
                            $amenities = $room->amenities ? json_decode($room->amenities, true) : [];
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card room-card">
                                <?php if (!empty($roomImages) && !empty($roomImages[0])): ?>
                                    <img src="<?= htmlspecialchars($roomImages[0]) ?>" class="card-img-top room-image" alt="<?= htmlspecialchars($room->name) ?>">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="fas fa-image fa-4x"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($room->name) ?> (Room #<?= htmlspecialchars($room->room_number) ?>)</h5>
                                    <p class="card-text">
                                        <strong>Type:</strong> <?= htmlspecialchars($room->type ?? 'Not specified') ?><br>
                                        <strong>Price:</strong> $<?= number_format($room->base_price ?? 0, 2) ?>/night<br>
                                        <strong>Capacity:</strong> <?= $room->max_guests ?? 0 ?> person(s)<br>
                                        <strong>Size:</strong> <?= $room->size_sqm ?? 'N/A' ?> sqm<br>
                                        <strong>Bed Type:</strong> <?= $room->bed_type ?? 'Not specified' ?>
                                    </p>
                                    <?php if (!empty($amenities)): ?>
                                        <h6>Amenities:</h6>
                                        <ul class="amenities-list">
                                            <?php foreach ($amenities as $amenity): ?>
                                                <li><?= htmlspecialchars($amenity) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <div class="action-buttons d-flex justify-content-between">
                                        <a href="edit-room.php?id=<?= $room->id ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="room_id" value="<?= $room->id ?>">
                                            <button type="submit" name="delete_room" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this room?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i> No rooms found for this hotel.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="add-room.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoomModalLabel">Add New Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="hotel_id" value="<?= $managerHotelId ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="room_number" name="room_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Room Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Room Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="Standard">Standard</option>
                                    <option value="Deluxe">Deluxe</option>
                                    <option value="Suite">Suite</option>
                                    <option value="Executive">Executive</option>
                                    <option value="Family">Family</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="bed_type" class="form-label">Bed Type</label>
                                <select class="form-select" id="bed_type" name="bed_type" required>
                                    <option value="Single">Single</option>
                                    <option value="Double">Double</option>
                                    <option value="Queen">Queen</option>
                                    <option value="King">King</option>
                                    <option value="Twin">Twin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="max_guests" class="form-label">Max Guests</label>
                                <input type="number" class="form-control" id="max_guests" name="max_guests" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="base_price" class="form-label">Base Price ($/night)</label>
                                <input type="number" class="form-control" id="base_price" name="base_price" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label for="size_sqm" class="form-label">Size (sqm)</label>
                                <input type="number" class="form-control" id="size_sqm" name="size_sqm" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="breakfast_included" name="breakfast_included" value="1" checked>
                                    <label class="form-check-label" for="breakfast_included">Breakfast Included</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="refundable" name="refundable" value="1">
                                    <label class="form-check-label" for="refundable">Refundable</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amenities" class="form-label">Amenities (comma separated)</label>
                            <input type="text" class="form-control" id="amenities" name="amenities" placeholder="WiFi, TV, AC, Mini Bar">
                        </div>
                        
                        <div class="mb-3">
                            <label for="room_images" class="form-label">Room Images</label>
                            <input class="form-control" type="file" id="room_images" name="room_images[]" multiple accept="image/*">
                            <small class="text-muted">Upload multiple images (JPEG, PNG)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>

<?php include __DIR__ . '/../../includes/footer.php'; ?>