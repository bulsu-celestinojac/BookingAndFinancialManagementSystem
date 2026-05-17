
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Amenities | Aleinah's Private Resort</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <!-- Header: Logo and Navigation in one row -->
    <header class="amenities-header">
    <a href="index.php" class="logo" style="text-decoration:none;">
        <img src="images/aleinahslogo.png" alt="Logo" height="120">
        <span class="logo-text">Aleinah's Private Resort</span>
    </a>
        <nav class="amenities-nav-menu">
            <a href="index.php">Home</a>
            <a href="amenities.php" class="active">Amenities</a>
            <a href="rates.php">Rates</a>
            <a href="location.php">Location</a>
            <a href="contact.php">Contact Us</a>
        </nav>
    </header>
    <!-- Elegant Title -->
    <h1 class="amenities-title">OUR AMENITIES</h1>

    <!-- Amenities Grid -->
    <main class="amenities-main">
        <div class="amenities-grid">
            <div class="amenity-card">
                <img src="images/pool.jpg" alt="Private Swimming Pool" class="zoomable-img" data-title="Private Swimming Pool">
                <div class="amenity-info">
                    <h2>Private Swimming Pool</h2>
                    <p>Enjoy exclusive access to a clean, spacious pool perfect for family fun and relaxation.</p>
                </div>
            </div>
            <div class="amenity-card">
                <img src="images/tvroom.jpg" alt="TV Room" class="zoomable-img" data-title="TV Room">
                <div class="amenity-info">
                    <h2>TV Room</h2>
                    <p>Unwind in our cozy TV room, equipped with the latest entertainment systems for your enjoyment.</p>
                </div>
            </div>
            <div class="amenity-card">
                <img src="images/chairstable.jpg" alt="Chair and Table" class="zoomable-img" data-title="Chair and Table">
                <div class="amenity-info">
                    <h2>Chair and Table</h2>
                    <p>Relax and enjoy your meals with our comfortable chairs and tables, perfect for dining or lounging.</p>
                </div>
            </div>
            <div class="amenity-card">
                <img src="images/court.jpg" alt="Basketball Court" class="zoomable-img" data-title="Basketball Court">
                <div class="amenity-info">
                    <h2>Basketball Court</h2>
                    <p>Enjoy a game of basketball on our well-maintained court, perfect for friendly matches.</p>
                </div>
            </div>
            <div class="amenity-card">
                <img src="images/billiard.jpg" alt="Billiard Table " class="zoomable-img" data-title="Billiard Table">
                <div class="amenity-info">
                    <h2>Billiard Table</h2>
                    <p>Challenge your friends to a game of billiards on our high-quality table, perfect for some friendly competition.</p>
                </div>
            </div>
        </div>
    </main>
    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImg">
        <div id="caption"></div>
    </div>
    <script src="amenities.js"></script>
</body>
</html>
<!DOCTYPE html>
