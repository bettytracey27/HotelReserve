<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = new Database();

    // Set page variables
    $pageTitle = "Explore Ethiopia's Finest Destinations";
    $pageCSS = "/assets/css/destinations.css";

    // Get form values
    $destinationSlug = $_GET['destination'] ?? '';
    $checkIn = $_GET['check_in'] ?? '';
    $checkOut = $_GET['check_out'] ?? '';
    $guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 2;
    $guests = max(1, min($guests, 4)); 

    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    include('../includes/header.php');
?>

<main class="destinations-page">
    <section class="page-header" style="background-image: url('/assets/images/destinations/ethiopia-banner.jpg')">
        <div class="header-overlay">
            <h1>Discover Ethiopia's Wonders</h1>
            <p>Find your perfect stay in our most breathtaking locations</p>
        </div>
    </section>

    <!-- Search Form -->
    <div class="search-filters">
        <form method="GET" class="booking-widget">
            <div class="filter-group">
                <label for="destination">Destination</label>
                <select id="destination" name="destination" class="form-control">
                    <option value="">All Destinations</option>
                    <?php
                    $db->query("SELECT id, name, slug FROM locations ORDER BY name ASC");
                    foreach ($db->resultSet() as $dest) {
                        $selected = $destinationSlug === $dest->slug ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($dest->slug) . '" ' . $selected . '>' . htmlspecialchars($dest->name) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="check-in">Check In</label>
                <input type="date" id="check-in" name="check_in"
                       value="<?= htmlspecialchars($checkIn) ?>"
                       min="<?= $today ?>" class="form-control">
            </div>

            <div class="filter-group">
                <label for="check-out">Check Out</label>
                <input type="date" id="check-out" name="check_out"
                       value="<?= htmlspecialchars($checkOut) ?>"
                       min="<?= $tomorrow ?>" class="form-control">
            </div>

            <div class="filter-group">
                <label for="guests">Guests</label>
                <select id="guests" name="guests" class="form-control">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?= $i ?>" <?= $guests === $i ? 'selected' : '' ?>>
                            <?= $i ?> Guest<?= $i > 1 ? 's' : '' ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search Hotels
            </button>

            <?php if ($destinationSlug || $checkIn || $checkOut): ?>
                <a href="destinations.php" class="clear-filter">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Destinations Grid -->
    <div class="destinations-grid">
        <?php
        $query = "SELECT 
                    l.id, 
                    l.name, 
                    l.slug,
                    l.description, 
                    l.image AS image_path,  -- changed column name here
                    l.region,
                    COUNT(h.id) AS hotels_count,
                    IFNULL(MIN(h.price_per_night), 0) AS starting_price
                  FROM locations l
                  LEFT JOIN hotels h ON h.location_id = l.id";

        if ($destinationSlug) {
            $query .= " WHERE l.slug = :slug";
        }

        $query .= " GROUP BY l.id ORDER BY l.name ASC";

        $db->query($query);
        if ($destinationSlug) {
            $db->bind(':slug', $destinationSlug);
        }

        $destinations = $db->resultSet();

        if (empty($destinations)) {
            echo '<div class="no-results">No destinations found matching your criteria.</div>';
        } else {
            foreach ($destinations as $destination) {
                $defaultImage = '/assets/images/destinations/default.jpg';
                $imagePath = $destination->image_path ?: $defaultImage;
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;

                // Check if file exists
                if (!file_exists($fullPath)) {
                    $imagePath = $defaultImage;
                }

                $priceDisplay = $destination->starting_price > 0
                    ? '$' . number_format($destination->starting_price, 2)
                    : 'Contact for price';
                ?>

                <div class="destination-card">
                    <div class="card-image-container">
                        <img src="<?= htmlspecialchars($imagePath) ?>"
                             alt="<?= htmlspecialchars($destination->name) ?>"
                             class="card-image"
                             onerror="this.onerror=null;this.src='/assets/images/destinations/default.jpg'">
                        <span class="region-badge"><?= htmlspecialchars($destination->region) ?></span>
                    </div>

                    <div class="card-content">
                        <h3><?= htmlspecialchars($destination->name) ?></h3>
                        <p class="description"><?= htmlspecialchars($destination->description) ?></p>

                        <div class="card-meta">
                            <span class="price"><?= $priceDisplay ?></span>
                            <span class="hotels"><?= (int)$destination->hotels_count ?> hotel<?= $destination->hotels_count != 1 ? 's' : '' ?></span>
                        </div>

                        <a href="hotels.php?location=<?= urlencode($destination->slug) ?>" class="btn view-hotels-btn">
                            View Hotels
                        </a>
                    </div>
                </div>

                <?php
            }
        }
        ?>
    </div>
</main>

<?php
    include('../includes/footer.php');
} catch (Exception $e) {
    echo '<div class="error-container">';
    echo '<h2>Application Error</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . htmlspecialchars($e->getLine()) . '</p>';
    echo '</div>';
}
?>
