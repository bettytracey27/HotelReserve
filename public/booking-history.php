<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$db = new Database();
$db->query("SELECT * FROM bookings WHERE user_id = :user_id ORDER BY check_in DESC");
$db->bind(':user_id', $_SESSION['user']['id']);
$bookings = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - HotelReserve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .history-page {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .history-container {
            display: flex;
            gap: 20px;
        }
        
        .history-sidebar {
            width: 250px;
            background: #1a3e2f;
            border-radius: 8px;
            padding: 20px;
            color: white;
        }
        
        .user-card {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            font-size: 60px;
            color: #d4edda;
            margin-bottom: 10px;
        }
        
        .user-card h3 {
            margin: 10px 0 5px;
            color: white;
        }
        
        .user-card p {
            color: #d4edda;
            font-size: 14px;
        }
        
        .history-nav {
            display: flex;
            flex-direction: column;
        }
        
        .history-nav a {
            color: white;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .history-nav a:hover {
            background-color: #2d5b45;
            text-decoration: none;
        }
        
        .history-nav a.active {
            background-color: #3a7d55;
            font-weight: bold;
        }
        
        .history-nav i {
            width: 20px;
            text-align: center;
        }
        
        .history-content {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .history-content h2 {
            color: #1a3e2f;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .history-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #1a3e2f;
        }
        
        .filter-group select, 
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 180px;
        }
        
        .apply-btn {
            background-color: #3a7d55;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-end;
            transition: background-color 0.3s;
        }
        
        .apply-btn:hover {
            background-color: #2d5b45;
        }
        
        .booking-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .booking-table th {
            background-color: #1a3e2f;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .booking-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .booking-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }
        
        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-badge.confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .action-link {
            color: #3a7d55;
            text-decoration: none;
            font-weight: 500;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #d4edda;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .explore-btn {
            display: inline-block;
            background-color: #3a7d55;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .explore-btn:hover {
            background-color: #2d5b45;
        }
        
        @media (max-width: 768px) {
            .history-container {
                flex-direction: column;
            }
            
            .history-sidebar {
                width: 100%;
            }
            
            .history-filters {
                flex-direction: column;
            }
            
            .booking-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <main class="history-page">
        <div class="history-container">
            <div class="history-sidebar">
                <div class="user-card">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3><?= htmlspecialchars($_SESSION['user']['username']) ?></h3>
                    <p><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
                </div>
                
                <nav class="history-nav">
                    <a href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="booking-history.php" class="active">
                        <i class="fas fa-history"></i> Booking History
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </nav>
            </div>
            
            <div class="history-content">
                <h2>Booking History</h2>
                
                <div class="history-filters">
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter">
                            <option value="all">All Bookings</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-from">From</label>
                        <input type="date" id="date-from">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-to">To</label>
                        <input type="date" id="date-to">
                    </div>
                    
                    <button class="apply-btn">Apply Filters</button>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>You haven't made any bookings yet</p>
                        <a href="destinations.php" class="explore-btn">Explore Hotels</a>
                    </div>
                <?php else: ?>
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>Hotel</th>
                                <th>Dates</th>
                                <th>Nights</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): 
                                $checkIn = new DateTime($booking->check_in);
                                $checkOut = new DateTime($booking->check_out);
                                $nights = $checkIn->diff($checkOut)->days;
                                
                                // Determine status
                                $statusClass = strtolower($booking->status);
                                $statusText = ucfirst($booking->status);
                                
                                // Special case for pending payment
                                if (property_exists($booking, 'payment_status') && $booking->payment_status === 'pending') {
                                    $statusClass = 'pending';
                                    $statusText = 'Pending Payment';
                                }
                                
                                // Check if stay is completed
                                if ($checkOut < new DateTime()) {
                                    $statusClass = 'completed';
                                    $statusText = 'Completed';
                                }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking->hotel_name ?? 'Hotel Booking') ?></td>
                                    <td>
                                        <?= $checkIn->format('M j, Y') ?> - 
                                        <?= $checkOut->format('M j, Y') ?>
                                    </td>
                                    <td><?= $nights ?></td>
                                    <td>$<?= number_format($booking->total_price ?? 0, 2) ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="booking-details.php?id=<?= $booking->id ?>" class="action-link">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include('../includes/footer.php'); ?>
    
    <script>
        // Simple filter functionality
        document.querySelector('.apply-btn').addEventListener('click', function() {
            const status = document.getElementById('status-filter').value;
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            
            // In a real implementation, you would send these filters to the server
            // and reload the bookings. This is just a placeholder.
            alert('Filters would be applied here. Status: ' + status + 
                  ', From: ' + dateFrom + ', To: ' + dateTo);
        });
    </script>
</body>
</html>