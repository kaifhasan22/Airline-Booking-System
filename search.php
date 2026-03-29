<?php include 'db.php'; ?>
<?php
$from       = isset($_GET['from']) ? intval($_GET['from']) : 0;
$to         = isset($_GET['to']) ? intval($_GET['to']) : 0;
$date       = isset($_GET['date']) ? trim($_GET['date']) : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

// Fetch airports for re-search dropdowns
$airports = [];
$res = $conn->query("SELECT airport_id, airport_name, city FROM airports ORDER BY city");
if ($res) while ($row = $res->fetch_assoc()) $airports[] = $row;

// Get city names for header display
$from_city = ''; $to_city = '';
foreach ($airports as $ap) {
    if ($ap['airport_id'] == $from) $from_city = $ap['city'];
    if ($ap['airport_id'] == $to)   $to_city   = $ap['city'];
}


$flights = [];
$where   = ["1=1"];   // always-true base so we can safely append AND clauses

if ($from) $where[] = "f.departure_airport = $from";
if ($to)   $where[] = "f.arrival_airport   = $to";

// Date filter: match schedule date OR flights with no schedule entry yet
$date_clause = '';
if ($date !== '') {
    $safe_date   = $conn->real_escape_string($date);
    $date_clause = "AND (fs.departure_date = '$safe_date' OR fs.departure_date IS NULL)";
}

$sql = "
    SELECT
        f.flight_id,
        f.price,
        f.departure_time,
        f.arrival_time,
        al.airline_name,
        al.airline_id,
        a1.city         AS dep_city,
        a1.airport_name AS dep_airport,
        a1.country      AS dep_country,
        a2.city         AS arr_city,
        a2.airport_name AS arr_airport,
        a2.country      AS arr_country,
        fs.departure_date,
        fs.gate_number,
        fs.status,
        fs.schedule_id
    FROM flights f
    JOIN  airlines al ON f.airline_id        = al.airline_id
    JOIN  airports a1 ON f.departure_airport = a1.airport_id
    JOIN  airports a2 ON f.arrival_airport   = a2.airport_id
    LEFT JOIN flight_schedule fs ON fs.flight_id = f.flight_id
              $date_clause
    WHERE " . implode(' AND ', $where) . "
    ORDER BY f.price ASC
";

$result = $conn->query($sql);
if ($result) while ($row = $result->fetch_assoc()) $flights[] = $row;

// Remove duplicates (same flight_id appearing multiple times from schedule rows)
$seen = [];
$flights = array_filter($flights, function($f) use (&$seen) {
    if (isset($seen[$f['flight_id']])) return false;
    $seen[$f['flight_id']] = true;
    return true;
});
$flights = array_values($flights);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Results – SkyNest Airlines</title>
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
            <a href="index.php">Flights</a>
            <a href="#">Hotels</a>
            <a href="#">Holidays</a>
            <a href="#">Offers</a>
            <a href="index.php" class="nav-cta"><i class="fas fa-user"></i> Login</a>
        </div>
    </div>
</nav>

<!-- PAGE HEADER + MINI RE-SEARCH -->
<div class="page-header">
    <div class="page-header-container">
        <h1><i class="fas fa-search" style="margin-right:10px;opacity:0.8;"></i>Search Flights</h1>

        <?php if ($from_city || $to_city || $date): ?>
        <div class="flight-route-pill">
            <i class="fas fa-plane-departure"></i>
            <?= $from_city ? htmlspecialchars($from_city) : 'Any City' ?>
            <i class="fas fa-long-arrow-alt-right"></i>
            <?= $to_city ? htmlspecialchars($to_city) : 'Any City' ?>
            <?php if ($date): ?>
                &nbsp;·&nbsp; <?= date('D, d M Y', strtotime($date)) ?>
            <?php endif; ?>
            &nbsp;·&nbsp; <?= $passengers ?> Passenger<?= $passengers > 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>

        <!-- MINI RE-SEARCH FORM -->
        <form action="search.php" method="GET"
              style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;text-transform:uppercase;letter-spacing:1px;">From</label>
                <select name="from" style="padding:10px 14px;border-radius:8px;border:none;font-family:inherit;font-size:14px;min-width:160px;">
                    <option value="">Any City</option>
                    <?php foreach ($airports as $ap): ?>
                    <option value="<?= $ap['airport_id'] ?>" <?= $ap['airport_id']==$from?'selected':'' ?>>
                        <?= htmlspecialchars($ap['city']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;text-transform:uppercase;letter-spacing:1px;">To</label>
                <select name="to" style="padding:10px 14px;border-radius:8px;border:none;font-family:inherit;font-size:14px;min-width:160px;">
                    <option value="">Any City</option>
                    <?php foreach ($airports as $ap): ?>
                    <option value="<?= $ap['airport_id'] ?>" <?= $ap['airport_id']==$to?'selected':'' ?>>
                        <?= htmlspecialchars($ap['city']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;text-transform:uppercase;letter-spacing:1px;">Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"
                       style="padding:10px 14px;border-radius:8px;border:none;font-family:inherit;font-size:14px;">
            </div>

            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;text-transform:uppercase;letter-spacing:1px;">Passengers</label>
                <select name="passengers" style="padding:10px 14px;border-radius:8px;border:none;font-family:inherit;font-size:14px;">
                    <?php for ($i=1;$i<=6;$i++): ?>
                    <option value="<?=$i?>" <?=$i==$passengers?'selected':''?>><?=$i?> Adult<?=$i>1?'s':''?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit"
                    style="padding:10px 24px;background:#f97316;color:white;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
</div>

<!-- RESULTS LAYOUT -->
<div class="results-layout">

    <!-- FILTER PANEL -->
    <aside class="filter-panel">
        <h3><i class="fas fa-sliders-h" style="margin-right:8px;color:var(--primary);"></i>Filters</h3>

        <div class="filter-group">
            <h4>Stops</h4>
            <label class="filter-option"><input type="checkbox" checked> Non-stop</label>
            <label class="filter-option"><input type="checkbox"> 1 Stop</label>
            <label class="filter-option"><input type="checkbox"> 2+ Stops</label>
        </div>

        <div class="filter-group">
            <h4>Departure Time</h4>
            <label class="filter-option"><input type="checkbox"> Early Morning (0–6)</label>
            <label class="filter-option"><input type="checkbox" checked> Morning (6–12)</label>
            <label class="filter-option"><input type="checkbox" checked> Afternoon (12–18)</label>
            <label class="filter-option"><input type="checkbox"> Evening (18–24)</label>
        </div>

        <div class="filter-group">
            <h4>Price Range</h4>
            <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--gray-500);margin-bottom:8px;">
                <span>₹0</span><span>₹50,000</span>
            </div>
            <input type="range" min="0" max="50000" value="50000"
                   style="width:100%;accent-color:var(--primary);"
                   oninput="this.nextElementSibling.textContent='Up to ₹'+Number(this.value).toLocaleString()">
            <div style="font-size:13px;color:var(--primary);font-weight:600;margin-top:6px;">Up to ₹50,000</div>
        </div>

        <div class="filter-group">
            <h4>Airlines</h4>
            <?php
            $al_list = [];
            $ar = $conn->query("SELECT DISTINCT airline_name FROM airlines");
            if ($ar) while ($row = $ar->fetch_assoc()) $al_list[] = $row['airline_name'];
            foreach ($al_list as $al): ?>
            <label class="filter-option">
                <input type="checkbox" checked> <?= htmlspecialchars($al) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- FLIGHT RESULTS -->
    <div>
        <!-- RESULTS HEADER -->
        <div class="results-header">
            <div class="results-count">
                <?php if (empty($flights)): ?>
                    <span style="color:var(--danger);">No flights found</span>
                <?php else: ?>
                    Showing <span><?= count($flights) ?></span>
                    flight<?= count($flights) != 1 ? 's' : '' ?>
                    <?php if ($from_city || $to_city): ?>
                        from <span><?= $from_city ?: 'Any' ?></span>
                        to <span><?= $to_city ?: 'Any' ?></span>
                    <?php endif; ?>
                    <?php if ($date): ?>
                        on <span><?= date('d M Y', strtotime($date)) ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <select class="sort-select" onchange="sortFlights(this.value)">
                <option value="price_asc">Sort: Price – Low to High</option>
                <option value="price_desc">Sort: Price – High to Low</option>
                <option value="duration">Sort: Duration</option>
            </select>
        </div>

        <!-- NO RESULTS STATE -->
        <?php if (empty($flights)): ?>
        <div style="background:white;border-radius:14px;padding:60px;text-align:center;box-shadow:var(--shadow-sm);">
            <div style="font-size:56px;margin-bottom:16px;">✈️</div>
            <h3 style="font-size:20px;font-weight:700;color:var(--dark);margin-bottom:8px;">No Flights Found</h3>
            <p style="color:var(--gray-500);margin-bottom:6px;">
                We couldn't find any flights
                <?php if ($from_city || $to_city): ?>
                    from <strong><?= $from_city ?: 'your origin' ?></strong>
                    to <strong><?= $to_city ?: 'your destination' ?></strong>
                <?php endif; ?>
                <?php if ($date): ?>
                    on <strong><?= date('d M Y', strtotime($date)) ?></strong>
                <?php endif; ?>
            </p>
            <p style="color:var(--gray-500);font-size:13px;margin-bottom:24px;">
                Try a different date, or leave the date blank to see all available flights on this route.
            </p>
            <a href="index.php" style="display:inline-block;padding:12px 28px;background:var(--primary);color:white;border-radius:8px;text-decoration:none;font-weight:600;">
                ← Modify Search
            </a>
        </div>

        <?php else: ?>

        <!-- FLIGHT CARDS -->
        <div id="flights-list">
        <?php foreach ($flights as $f):
            $dep = new DateTime($f['departure_time']);
            $arr = new DateTime($f['arrival_time']);
            $diff = $dep->diff($arr);
            $duration = $diff->h . 'h ' . $diff->i . 'm';
            $duration_mins = ($diff->h * 60) + $diff->i;

            // Use searched date for display; fallback to schedule date or today
            $display_date = $date ?: ($f['departure_date'] ?: date('Y-m-d'));

            $status = $f['status'] ?? 'Scheduled';
            $status_color = $status === 'On Time'  ? '#16a34a'
                          : ($status === 'Delayed' ? '#dc2626' : '#92400e');
        ?>
        <div class="flight-card"
             data-price="<?= $f['price'] ?>"
             data-duration="<?= $duration_mins ?>">

            <!-- AIRLINE -->
            <div class="airline-info">
                <div class="airline-logo">✈️</div>
                <div>
                    <div class="airline-name"><?= htmlspecialchars($f['airline_name']) ?></div>
                    <div class="flight-number">
                        Flight #<?= str_pad($f['flight_id'], 4, '0', STR_PAD_LEFT) ?>
                    </div>
                    <div style="font-size:11px;font-weight:700;color:<?= $status_color ?>;margin-top:4px;">
                        ● <?= htmlspecialchars($status) ?>
                    </div>
                </div>
            </div>

            <!-- DEPARTURE -->
            <div class="time-info">
                <div class="time"><?= $dep->format('H:i') ?></div>
                <div class="airport-code"><?= htmlspecialchars($f['dep_city']) ?></div>
                <div style="font-size:11px;color:var(--gray-500);">
                    <?= date('d M', strtotime($display_date)) ?>
                </div>
            </div>

            <!-- DURATION -->
            <div class="duration-info">
                <div class="duration-line">
                    <hr><i class="fas fa-plane"></i><hr>
                </div>
                <div class="duration-text"><?= $duration ?> · Non-stop</div>
                <?php if ($f['gate_number']): ?>
                <div style="font-size:11px;color:var(--primary);font-weight:600;text-align:center;margin-top:4px;">
                    Gate <?= htmlspecialchars($f['gate_number']) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ARRIVAL -->
            <div class="time-info">
                <div class="time"><?= $arr->format('H:i') ?></div>
                <div class="airport-code"><?= htmlspecialchars($f['arr_city']) ?></div>
                <div style="font-size:11px;color:var(--gray-500);">
                    <?= htmlspecialchars($f['arr_airport']) ?>
                </div>
            </div>

            <!-- PRICE -->
            <div class="flight-price">
                <div class="price">₹<?= number_format($f['price'] * $passengers) ?></div>
                <div class="price-label">
                    for <?= $passengers ?> passenger<?= $passengers > 1 ? 's' : '' ?>
                </div>
                <div style="font-size:12px;color:var(--gray-500);">
                    ₹<?= number_format($f['price']) ?>/person
                </div>
            </div>

            <!-- BOOK BUTTON -->
            <div>
                <a href="passenger.php?flight_id=<?= $f['flight_id'] ?>&passengers=<?= $passengers ?>&schedule_id=<?= $f['schedule_id'] ?? 0 ?>&date=<?= urlencode($display_date) ?>"
                   class="btn-book">
                    Book Now
                </a>
                <div style="font-size:11px;color:var(--gray-500);text-align:center;margin-top:6px;">
                    <i class="fas fa-chair"></i> Seats available
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div><!-- end #flights-list -->

        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer" style="margin-top:40px;">
    <div class="footer-bottom" style="border-top:none;">
        <span>© 2025 SkyNest Airlines. All rights reserved.</span>
        <span><i class="fas fa-lock" style="margin-right:6px;"></i>100% Secure Payments</span>
    </div>
</footer>

<script>
function sortFlights(val) {
    const list = document.getElementById('flights-list');
    if (!list) return;
    const cards = Array.from(list.querySelectorAll('.flight-card'));
    cards.sort((a, b) => {
        if (val === 'price_asc')  return parseInt(a.dataset.price)    - parseInt(b.dataset.price);
        if (val === 'price_desc') return parseInt(b.dataset.price)    - parseInt(a.dataset.price);
        if (val === 'duration')   return parseInt(a.dataset.duration) - parseInt(b.dataset.duration);
        return 0;
    });
    cards.forEach(c => list.appendChild(c));
}
</script>
</body>
</html>
