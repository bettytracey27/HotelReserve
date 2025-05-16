<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$basePath = $_SERVER['DOCUMENT_ROOT'];
$webPath = '/public/uploads/locations/';

$pageTitle = "HotelReserve Ethiopia - Luxury Stays Across Ethiopia";
$pageCSS = "home.css";
include(__DIR__ . '/../includes/header.php');

?>
<main class="home-page">
    <!-- Hero Carousel -->
    <section class="hero-carousel">
        <div class="carousel-container">
            <div class="carousel-slides">
                <?php
                $locationImages = [
                    '1746318863_Semien Mountains.jpeg',
                    '1746318893_lalibela.jpeg',
                    '1746315826_logo.png',
                    '1746321471_Gonder.jpeg',
                    '1746321504_axum.jpeg',
                    '1746365762_addis abeba.png',
                    '1746365778_arba minch.png',
                    '1746365793_asela.png',
                    '1746365806_bahir dar.png',
                    '1746365820_harar.jpeg'
                ];
                
                foreach ($locationImages as $image) {
                    if (file_exists($basePath . $webPath . $image)) {
                        echo '<div class="carousel-slide" style="background-image: url(\''.$webPath.$image.'\')"></div>';
                    }
                }
                ?>
            </div>
            <div class="hero-content">
                <h1>Discover Ethiopia's <span>Hidden Gems</span></h1>
                <p class="subtitle">Experience luxury stays at Ethiopia's finest hotels and lodges</p>
                <a href="destinations.php" class="explore-btn">Explore Destinations</a>
            </div>
            <div class="carousel-controls">
                <button class="carousel-prev"><i class="fas fa-chevron-left"></i></button>
                <button class="carousel-next"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="carousel-dots"></div>
        </div>
    </section>

    <!-- Featured Destinations -->
    <section class="featured-section">
        <div class="section-header">
            <h2>Our <span>Featured</span> Destinations</h2>
            <p>Explore Ethiopia's most breathtaking locations</p>
        </div>
        
        <div class="destinations-grid">
            <?php
            try {
                $db = new Database();
                
                $db->query("SELECT id, name, slug, description 
                           FROM locations 
                           WHERE id IN (1, 2, 3, 6)");
                $featuredLocations = $db->resultSet();
                
                foreach ($featuredLocations as $location) {
                    $imageFiles = [
                        1 => '1746318893_lalibela.jpeg',
                        2 => '1746321504_axum.jpeg',
                        3 => '1746321471_Gonder.jpeg',
                        6 => '1746318863_Semien Mountains.jpeg'
                    ];
                    
                    $imageFile = $imageFiles[$location->id] ?? 'default.jpg';
                    $imagePath = $webPath . $imageFile;
                    $shortDesc = substr($location->description ?? '', 0, 100) . '...';
                    
                    echo '
                    <div class="destination-card">
                        <div class="card-image" style="background-image: url(\''.htmlspecialchars($imagePath).'\')">
                            <div class="overlay">
                                <h3>'.htmlspecialchars($location->name).'</h3>
                            </div>
                        </div>
                        <div class="card-content">
                            <p>'.htmlspecialchars($shortDesc).'</p>
                            <a href="destinations.php?destination='.htmlspecialchars($location->slug).'" class="explore-link">
                                Explore <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">Error loading destinations: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="benefits-section">
        <div class="section-header">
            <h2>Why <span>Choose</span> Us</h2>
            <p>What makes us different from others</p>
        </div>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h3>Curated Selection</h3>
                <p>Only the finest hotels that meet our standards</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3>Local Expertise</h3>
                <p>Authentic recommendations from Ethiopian experts</p>
            </div>
            
            <div class="benefit-card">
                <div class="fas fa-lock"></i>
                </div>
                <h3>Secure Booking</h3>
                <p>Your information is always protected</p>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const slides = document.querySelectorAll('.carousel-slide');
    const dotsContainer = document.querySelector('.carousel-dots');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    let currentIndex = 0;
    
    // Create dots
    slides.forEach((_, index) => {
        const dot = document.createElement('span');
        dot.classList.add('dot');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });
    
    const dots = document.querySelectorAll('.dot');
    
    function updateCarousel() {
        slides.forEach((slide, index) => {
            slide.style.transform = `translateX(${100 * (index - currentIndex)}%)`;
        });
        
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }
    
    function goToSlide(index) {
        currentIndex = index;
        updateCarousel();
    }
    
    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        updateCarousel();
    }
    
    function prevSlide() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        updateCarousel();
    }
    
    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);
    
    let slideInterval = setInterval(nextSlide, 1000);
    
    // Pause on hover
    const carousel = document.querySelector('.carousel-container');
    carousel.addEventListener('mouseenter', () => clearInterval(slideInterval));
    carousel.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    
    updateCarousel();
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>