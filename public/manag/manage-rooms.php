<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . 'auth.php';

requireManager();

$db = new Database();
$hotelId = getManagerHotelId($_SESSION['user']['id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_room'])) {
            $data = [
                'hotel_id' => $hotelId,
                'room_number' => $_POST['room_number'],
                'type' => $_POST['type'],
                'price_per_night' => $_POST['price_per_night'],
                'capacity' => $_POST['capacity']
            ];
            
            $db->query("INSERT INTO rooms (hotel_id, room_number, type, price_per_night, capacity) 
                       VALUES (:hotel_id, :room_number, :type, :price_per_night, :capacity)");
            $db->bindMultiple($data);
            $db->execute();
            
            $_SESSION['success'] = "Room added successfully";
        }
        
        if (isset($_POST['delete_room'])) {
            $db->query("DELETE FROM rooms WHERE id = ? AND hotel_id = ?");
            $db->bind(1, $_POST['room_id']);
            $db->bind(2, $hotelId);
            $db->execute();
            
            $_SESSION['success'] = "Room deleted";
        }
        
        header("Location: manage-rooms.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Get all rooms
$db->query("SELECT * FROM rooms WHERE hotel_id = ? ORDER BY room_number");
$db->bind(1, $hotelId);
$rooms = $db->resultSet();
?>

<!-- HTML form for managing rooms -->
<div class="container">
    <h2>Manage Rooms</h2>
    
    <!-- Add Room Form -->
    <form method="POST" class="mb-4">
        <input type="hidden" name="add_room" value="1">
        <div class="row g-3">
            <div class="col-md-3">
                <input type="text" name="room_number" class="form-control" placeholder="Room Number" required>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select" required>
                    <option value="standard">Standard</option>
                    <option value="deluxe">Deluxe</option>
                    <option value="suite">Suite</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="price_per_night" class="form-control" placeholder="Price" step="0.01" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="capacity" class="form-control" placeholder="Capacity" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </div>
    </form>

    <!-- Rooms List -->
    <table class="table">
        <thead>
            <tr>
                <th>Room #</th>
                <th>Type</th>
                <th>Price</th>
                <th>Capacity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= htmlspecialchars($room->room_number) ?></td>
                <td><?= ucfirst($room->type) ?></td>
                <td>$<?= number_format($room->price_per_night, 2) ?></td>
                <td><?= $room->capacity ?></td>
                <td>
                    <a href="edit-room.php?id=<?= $room->id ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="room_id" value="<?= $room->id ?>">
                        <button type="submit" name="delete_room" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>