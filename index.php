<?php include 'db.php'; ?>
<?php
// Fetch airports for dropdowns
$airports = [];
$result = $conn->query("SELECT airport_id, airport_name, city, country FROM airports ORDER BY city");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $airports[] = $row;
    }
}

// Fetch featured flights with airline and airport info
$featured_flights = [];
$fq = "SELECT f.flight_id, f.price, f.departure_time, f.arrival_time,
               al.airline_name,
               a1.city AS dep_city, a1.country AS dep_country,
               a2.city AS arr_city, a2.country AS arr_country
        FROM flights f
        JOIN airlines al ON f.airline_id = al.airline_id
        JOIN airports a1 ON f.departure_airport = a1.airport_id
        JOIN airports a2 ON f.arrival_airport = a2.airport_id
        ORDER BY f.price ASC LIMIT 4";
$fr = $conn->query($fq);
if ($fr) {
    while ($row = $fr->fetch_assoc()) {
        $featured_flights[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyNest Airlines – Book Flights Online</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-paper-plane"></i></div>
            <div class="logo-text">
                <span class="logo-name">SkyNest</span>
                <span class="logo-tagline">Airlines</span>
            </div>
        </a>
        <div class="nav-links">
            <a href="index.php" class="active">Flights</a>
            <a href="#">Hotels</a>
            <a href="#">Holidays</a>
            <a href="#">Offers</a>
            <a href="index.php" class="nav-cta"><i class="fas fa-user"></i> Login</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-text">
            <div class="hero-badge">
                <i class="fas fa-star"></i>
                India's Most Trusted Airline Booking Platform
            </div>
            <h1>Fly Higher with<br><span>SkyNest Airlines</span></h1>
            <p>Discover amazing deals on flights to your favorite destinations</p>
        </div>

        <!-- SEARCH CARD -->
        <div class="search-card">
            <div class="trip-tabs">
                <button class="trip-tab active" onclick="setTab(this)">
                    <i class="fas fa-arrow-right"></i> One Way
                </button>
                <button class="trip-tab" onclick="setTab(this)">
                    <i class="fas fa-exchange-alt"></i> Round Trip
                </button>
                <button class="trip-tab" onclick="setTab(this)">
                    <i class="fas fa-random"></i> Multi City
                </button>
            </div>

            <form action="search.php" method="GET">
                <div class="search-form-grid">
                    <div class="form-field">
                        <label>From</label>
                        <i class="fas fa-plane-departure field-icon"></i>
                        <select name="from" required>
                            <option value="">Select Departure City</option>
                            <option value="">Any City</option>
                            <?php foreach ($airports as $ap): ?>
                                <option value="<?= $ap['airport_id'] ?>">
                                    <?= htmlspecialchars($ap['city']) ?> – <?= htmlspecialchars($ap['airport_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>To</label>
                        <i class="fas fa-plane-arrival field-icon"></i>
                        <select name="to" required>
                            <option value="">Select Arrival City</option>
                            <option value="">Any City</option>
                            <?php foreach ($airports as $ap): ?>
                                <option value="<?= $ap['airport_id'] ?>">
                                    <?= htmlspecialchars($ap['city']) ?> – <?= htmlspecialchars($ap['airport_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Departure Date</label>
                        <i class="fas fa-calendar field-icon"></i>
                        <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-field">
                        <label>Passengers</label>
                        <i class="fas fa-user field-icon"></i>
                        <select name="passengers">
                            <option value="1">1 Adult</option>
                            <option value="2">2 Adults</option>
                            <option value="3">3 Adults</option>
                            <option value="4">4 Adults</option>
                            <option value="5">5 Adults</option>
                        </select>
                    </div>
                </div>

                <div class="search-btn-wrap">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Search Flights
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- FEATURED FLIGHTS -->
<?php if (!empty($featured_flights)): ?>
<section class="section">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">Best <span>Deals</span> Right Now</h2>
            <a href="search.php" class="section-link">View all flights <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="offers-grid">
            <?php
            $emojis = ['✈️','🌏','🗺️','🏖️'];
            foreach ($featured_flights as $i => $f):
                $dep = new DateTime($f['departure_time']);
                $arr = new DateTime($f['arrival_time']);
                $diff = $dep->diff($arr);
                $duration = $diff->h . 'h ' . $diff->i . 'm';
            ?>
            <div class="offer-card" onclick="window.location='search.php?from=&to='">
                <div class="offer-card-img"><?= $emojis[$i % 4] ?></div>
                <div class="offer-card-body">
                    <div class="offer-route"><?= htmlspecialchars($f['dep_city']) ?> → <?= htmlspecialchars($f['arr_city']) ?></div>
                    <div class="offer-price">Starting from <strong>₹<?= number_format($f['price']) ?></strong></div>
                    <span class="offer-tag"><?= htmlspecialchars($f['airline_name']) ?></span>
                    <span class="offer-tag" style="margin-left:4px; background:#fef3c7; color:#92400e;"><?= $duration ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- WHY SKYNEST -->
<section class="section why-section">
    <div class="section-container">
        <div class="section-header">
            <h2 class="section-title">Why Choose <span>SkyNest?</span></h2>
        </div>
        <div class="why-grid">
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Secure Booking</h3>
                <p>Your data and payments are fully encrypted and protected.</p>
            </div>
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-tag"></i></div>
                <h3>Best Prices</h3>
                <p>We guarantee the lowest fares on all routes we serve.</p>
            </div>
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-headset"></i></div>
                <h3>24/7 Support</h3>
                <p>Our team is always available to assist you anytime.</p>
            </div>
            <div class="why-item">
                <div class="why-icon"><i class="fas fa-bolt"></i></div>
                <h3>Instant Confirmation</h3>
                <p>Get your e-ticket immediately after booking is confirmed.</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-paper-plane"></i></div>
                <div class="logo-text">
                    <span class="logo-name">SkyNest</span>
                    <span class="logo-tagline">Airlines</span>
                </div>
            </div>
            <p>India's most trusted airline booking platform. Book flights with confidence and fly to your dream destination.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="#">Book Flight</a>
            <a href="#">Check Status</a>
            <a href="#">Manage Booking</a>
            <a href="#">Web Check-in</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="#">Help Center</a>
            <a href="#">Cancellation</a>
            <a href="#">Refund Policy</a>
            <a href="#">Contact Us</a>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <a href="#">About Us</a>
            <a href="#">Careers</a>
            <a href="#">Press</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2025 SkyNest Airlines. All rights reserved.</span>
        <span><i class="fas fa-lock" style="margin-right:6px;"></i>100% Secure Payments</span>
    </div>
</footer>

<script>
function setTab(el) {
    document.querySelectorAll('.trip-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
</script>
</body>
</html>
