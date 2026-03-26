<?php include 'db.php'; ?>
<?php
$flight_id   = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;
$passengers  = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;

if (!$flight_id) { header('Location: index.php'); exit; }

// Fetch flight details
$flight = null;
$sql = "SELECT f.flight_id, f.price, f.departure_time, f.arrival_time,
               al.airline_name,
               a1.city AS dep_city, a1.airport_name AS dep_airport,
               a2.city AS arr_city, a2.airport_name AS arr_airport,
               fs.departure_date, fs.gate_number, fs.status
        FROM flights f
        JOIN airlines al ON f.airline_id = al.airline_id
        JOIN airports a1 ON f.departure_airport = a1.airport_id
        JOIN airports a2 ON f.arrival_airport = a2.airport_id
        LEFT JOIN flight_schedule fs ON fs.flight_id = f.flight_id AND fs.schedule_id = $schedule_id
        WHERE f.flight_id = $flight_id
        LIMIT 1";

$res = $conn->query($sql);
if ($res) $flight = $res->fetch_assoc();
if (!$flight) { header('Location: index.php'); exit; }

$dep = new DateTime($flight['departure_time']);
$arr = new DateTime($flight['arrival_time']);
$diff = $dep->diff($arr);
$duration = $diff->h . 'h ' . $diff->i . 'm';
$total_price = $flight['price'] * $passengers;

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name      = trim($conn->real_escape_string($_POST['first_name']));
    $last_name       = trim($conn->real_escape_string($_POST['last_name']));
    $email           = trim($conn->real_escape_string($_POST['email']));
    $phone           = trim($conn->real_escape_string($_POST['phone']));
    $passport        = trim($conn->real_escape_string($_POST['passport_number']));
    $seat_number     = trim($conn->real_escape_string($_POST['seat_number']));
    $seat_class      = trim($conn->real_escape_string($_POST['seat_class']));
    $payment_method  = trim($conn->real_escape_string($_POST['payment_method']));
    $baggage_weight  = intval($_POST['baggage_weight']);
    $baggage_type    = trim($conn->real_escape_string($_POST['baggage_type']));

    if (!$first_name || !$last_name || !$email || !$phone || !$passport) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Insert passenger
            $stmt = $conn->prepare("INSERT INTO passengers (first_name, last_name, email, phone, passport_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $passport);
            $stmt->execute();
            $passenger_id = $conn->insert_id;
            $stmt->close();

            // 2. Insert booking
            $booking_date   = date('Y-m-d');
            $booking_status = 'Confirmed';
            $stmt2 = $conn->prepare("INSERT INTO bookings (passenger_id, flight_id, booking_date, seat_number, booking_status) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("iisss", $passenger_id, $flight_id, $booking_date, $seat_number, $booking_status);
            $stmt2->execute();
            $booking_id = $conn->insert_id;
            $stmt2->close();

            // 3. Insert ticket
            $ticket_number = 'SN' . strtoupper(substr(md5(uniqid()), 0, 8));
            $stmt3 = $conn->prepare("INSERT INTO tickets (booking_id, ticket_number, seat_class, price) VALUES (?, ?, ?, ?)");
            $stmt3->bind_param("issi", $booking_id, $ticket_number, $seat_class, $total_price);
            $stmt3->execute();
            $stmt3->close();

            // 4. Insert payment
            $payment_date = date('Y-m-d');
            $stmt4 = $conn->prepare("INSERT INTO payments (booking_id, payment_date, amount, payment_method) VALUES (?, ?, ?, ?)");
            $stmt4->bind_param("isis", $booking_id, $payment_date, $total_price, $payment_method);
            $stmt4->execute();
            $stmt4->close();

            // 5. Insert baggage
            if ($baggage_weight > 0) {
                $stmt5 = $conn->prepare("INSERT INTO baggage (passenger_id, weight, type) VALUES (?, ?, ?)");
                $stmt5->bind_param("iis", $passenger_id, $baggage_weight, $baggage_type);
                $stmt5->execute();
                $stmt5->close();
            }

            $conn->commit();
            header("Location: confirmation.php?booking_id=$booking_id&ticket=$ticket_number");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Booking failed. Please try again. (' . $e->getMessage() . ')';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Details – SkyNest Airlines</title>
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
            <a href="#">Offers</a>
            <a href="index.php" class="nav-cta"><i class="fas fa-user"></i> Login</a>
        </div>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="page-header-container">
        <h1><i class="fas fa-user-circle" style="margin-right:10px;opacity:0.8;"></i>Passenger Details</h1>
        <div class="flight-route-pill">
            <i class="fas fa-plane-departure"></i>
            <?= htmlspecialchars($flight['dep_city']) ?>
            <i class="fas fa-long-arrow-alt-right"></i>
            <?= htmlspecialchars($flight['arr_city']) ?>
            &nbsp;·&nbsp; <?= $passengers ?> Passenger<?= $passengers > 1 ? 's' : '' ?>
        </div>
        <!-- PROGRESS STEPS -->
        <div style="display:flex;align-items:center;gap:0;margin-top:20px;">
            <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.2);padding:8px 20px;border-radius:100px;">
                <div style="width:24px;height:24px;background:#f97316;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;">1</div>
                <span style="font-size:13px;font-weight:600;color:white;">Passenger Info</span>
            </div>
            <div style="width:40px;height:2px;background:rgba(255,255,255,0.3);"></div>
            <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);padding:8px 20px;border-radius:100px;">
                <div style="width:24px;height:24px;background:rgba(255,255,255,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;">2</div>
                <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);">Payment</span>
            </div>
            <div style="width:40px;height:2px;background:rgba(255,255,255,0.3);"></div>
            <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.1);padding:8px 20px;border-radius:100px;">
                <div style="width:24px;height:24px;background:rgba(255,255,255,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;">3</div>
                <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);">Confirmation</span>
            </div>
        </div>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div class="passenger-layout">
    <div>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <!-- PERSONAL DETAILS -->
            <div class="form-card">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span style="color:red;">*</span></label>
                        <input type="text" name="first_name" placeholder="e.g. Rahul" required value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:red;">*</span></label>
                        <input type="text" name="last_name" placeholder="e.g. Sharma" required value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="color:red;">*</span></label>
                        <input type="email" name="email" placeholder="rahul@example.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span style="color:red;">*</span></label>
                        <input type="tel" name="phone" placeholder="+91 98765 43210" required value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                    </div>
                    <div class="form-group full">
                        <label>Passport Number <span style="color:red;">*</span></label>
                        <input type="text" name="passport_number" placeholder="e.g. A1234567" required value="<?= isset($_POST['passport_number']) ? htmlspecialchars($_POST['passport_number']) : '' ?>">
                    </div>
                </div>
            </div>

            <!-- SEAT PREFERENCES -->
            <div class="form-card">
                <h2><i class="fas fa-chair"></i> Seat Preferences</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Seat Number</label>
                        <input type="text" name="seat_number" placeholder="e.g. 12A" value="<?= isset($_POST['seat_number']) ? htmlspecialchars($_POST['seat_number']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Seat Class</label>
                        <select name="seat_class">
                            <option value="Economy" <?= (isset($_POST['seat_class']) && $_POST['seat_class']=='Economy') ? 'selected' : '' ?>>Economy</option>
                            <option value="Business" <?= (isset($_POST['seat_class']) && $_POST['seat_class']=='Business') ? 'selected' : '' ?>>Business</option>
                            <option value="First Class" <?= (isset($_POST['seat_class']) && $_POST['seat_class']=='First Class') ? 'selected' : '' ?>>First Class</option>
                        </select>
                    </div>
                </div>

                <!-- VISUAL SEAT MAP -->
                <div style="margin-top:12px;">
                    <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:12px;">Quick Seat Selection (Optional)</div>
                    <div style="display:flex;gap:16px;align-items:center;margin-bottom:10px;">
                        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-500);"><span style="width:16px;height:16px;background:#dbeafe;border:1px solid #93c5fd;border-radius:3px;display:inline-block;"></span>Available</span>
                        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-500);"><span style="width:16px;height:16px;background:#fee2e2;border:1px solid #fca5a5;border-radius:3px;display:inline-block;"></span>Taken</span>
                        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-500);"><span style="width:16px;height:16px;background:#1a56db;border-radius:3px;display:inline-block;"></span>Selected</span>
                    </div>
                    <div id="seat-map" style="display:flex;flex-direction:column;gap:6px;max-width:360px;">
                        <?php
                        $rows = ['1','2','3','4','5','6','7','8'];
                        $cols = ['A','B','C','D','E','F'];
                        $taken = ['1A','2C','3F','4B','5E','6A','7D','2F'];
                        foreach ($rows as $row): ?>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <span style="width:20px;font-size:11px;color:var(--gray-500);text-align:right;"><?= $row ?></span>
                            <?php foreach ($cols as $ci => $col):
                                $seat = $row.$col;
                                $isTaken = in_array($seat, $taken);
                                if ($ci == 3): ?>
                                <span style="width:16px;"></span>
                                <?php endif; ?>
                                <div class="seat-btn"
                                     data-seat="<?= $seat ?>"
                                     onclick="<?= $isTaken ? '' : "selectSeat('$seat')" ?>"
                                     style="width:32px;height:32px;border-radius:6px 6px 4px 4px;border:1px solid <?= $isTaken ? '#fca5a5' : '#93c5fd' ?>;background:<?= $isTaken ? '#fee2e2' : '#dbeafe' ?>;cursor:<?= $isTaken ? 'not-allowed' : 'pointer' ?>;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;color:<?= $isTaken ? '#dc2626' : '#1e40af' ?>;transition:all 0.15s;"
                                     title="Seat <?= $seat ?><?= $isTaken ? ' (Taken)' : '' ?>">
                                    <?= $col ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- BAGGAGE -->
            <div class="form-card">
                <h2><i class="fas fa-suitcase"></i> Baggage Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Baggage Weight (kg)</label>
                        <input type="number" name="baggage_weight" placeholder="e.g. 15" min="0" max="100" value="<?= isset($_POST['baggage_weight']) ? htmlspecialchars($_POST['baggage_weight']) : '15' ?>">
                    </div>
                    <div class="form-group">
                        <label>Baggage Type</label>
                        <select name="baggage_type">
                            <option value="Check-in" <?= (isset($_POST['baggage_type']) && $_POST['baggage_type']=='Check-in') ? 'selected' : '' ?>>Check-in Baggage</option>
                            <option value="Carry-on" <?= (isset($_POST['baggage_type']) && $_POST['baggage_type']=='Carry-on') ? 'selected' : '' ?>>Carry-on Baggage</option>
                            <option value="Special" <?= (isset($_POST['baggage_type']) && $_POST['baggage_type']=='Special') ? 'selected' : '' ?>>Special Item</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- PAYMENT -->
            <div class="form-card">
                <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                <div class="form-row">
                    <div class="form-group full">
                        <label>Select Payment Method</label>
                        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                            <?php $methods = [
                                ['Credit Card','fas fa-credit-card','#1a56db'],
                                ['Debit Card','fas fa-credit-card','#16a34a'],
                                ['UPI','fas fa-mobile-alt','#7c3aed'],
                                ['Net Banking','fas fa-university','#92400e'],
                            ];
                            foreach ($methods as $i => $m): ?>
                            <label style="flex:1;min-width:130px;">
                                <input type="radio" name="payment_method" value="<?= $m[0] ?>" <?= $i==0 ? 'checked' : '' ?> style="display:none;" class="payment-radio">
                                <div class="payment-method-btn" data-method="<?= $m[0] ?>" style="border:2px solid <?= $i==0 ? 'var(--primary)' : 'var(--gray-300)' ?>;border-radius:10px;padding:14px;text-align:center;cursor:pointer;transition:all 0.2s;background:<?= $i==0 ? 'var(--primary-light)' : 'white' ?>;">
                                    <i class="<?= $m[1] ?>" style="font-size:20px;color:<?= $m[2] ?>;display:block;margin-bottom:6px;"></i>
                                    <span style="font-size:12px;font-weight:600;color:var(--dark);"><?= $m[0] ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="flight_id" value="<?= $flight_id ?>">
            <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">

        </div> <!-- end left column -->

        <!-- BOOKING SUMMARY SIDEBAR -->
        <aside>
            <div class="summary-card">
                <h3><i class="fas fa-receipt" style="margin-right:8px;color:var(--primary);"></i>Booking Summary</h3>

                <div style="background:var(--primary-light);border-radius:10px;padding:16px;margin-bottom:16px;">
                    <div style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:4px;">
                        <?= htmlspecialchars($flight['dep_city']) ?> → <?= htmlspecialchars($flight['arr_city']) ?>
                    </div>
                    <div style="font-size:13px;color:var(--gray-500);"><?= htmlspecialchars($flight['airline_name']) ?></div>
                    <div style="font-size:13px;color:var(--gray-500);margin-top:4px;">
                        <?= $dep->format('D, d M Y · H:i') ?> → <?= $arr->format('H:i') ?>
                    </div>
                    <div style="font-size:12px;color:var(--primary);font-weight:600;margin-top:6px;">
                        <i class="fas fa-clock"></i> Duration: <?= $duration ?>
                    </div>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Base Fare (1 pax)</span>
                    <span class="summary-value">₹<?= number_format($flight['price']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Passengers</span>
                    <span class="summary-value">× <?= $passengers ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Taxes & Fees</span>
                    <span class="summary-value">₹<?= number_format($flight['price'] * $passengers * 0.05) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Convenience Fee</span>
                    <span class="summary-value">₹199</span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span class="total-price">₹<?= number_format($total_price) ?></span>
                </div>

                <div style="background:#dcfce7;border-radius:8px;padding:10px 14px;margin-top:16px;font-size:12px;color:#15803d;font-weight:600;">
                    <i class="fas fa-tag"></i> You save ₹<?= number_format(rand(200,800)) ?> on this booking!
                </div>

                <button type="submit" class="btn-proceed">
                    <i class="fas fa-lock"></i> Confirm & Pay ₹<?= number_format($total_price) ?>
                </button>

                <div style="display:flex;justify-content:center;gap:16px;margin-top:14px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/200px-Mastercard-logo.svg.png" alt="Mastercard" style="height:20px;opacity:0.6;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/200px-Visa_Inc._logo.svg.png" alt="Visa" style="height:20px;opacity:0.6;">
                </div>

                <div style="text-align:center;margin-top:12px;font-size:11px;color:var(--gray-500);">
                    <i class="fas fa-shield-alt"></i> 256-bit SSL Encrypted & Secure
                </div>
            </div>
        </aside>
        </form>
</div>

<!-- FOOTER -->
<footer class="footer" style="margin-top:40px;">
    <div class="footer-bottom" style="border-top:none;">
        <span>© 2025 SkyNest Airlines. All rights reserved.</span>
        <span><i class="fas fa-lock" style="margin-right:6px;"></i>100% Secure Payments</span>
    </div>
</footer>

<script>
// Seat selection
function selectSeat(seat) {
    document.querySelectorAll('.seat-btn').forEach(btn => {
        if (btn.dataset.seat !== seat) {
            btn.style.background = '#dbeafe';
            btn.style.border = '1px solid #93c5fd';
            btn.style.color = '#1e40af';
        }
    });
    const btn = document.querySelector(`.seat-btn[data-seat="${seat}"]`);
    btn.style.background = '#1a56db';
    btn.style.border = '1px solid #1a56db';
    btn.style.color = 'white';
    document.querySelector('input[name="seat_number"]').value = seat;
}

// Payment method selection
document.querySelectorAll('.payment-method-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.payment-method-btn').forEach(b => {
            b.style.border = '2px solid var(--gray-300)';
            b.style.background = 'white';
        });
        btn.style.border = '2px solid var(--primary)';
        btn.style.background = 'var(--primary-light)';
        const method = btn.dataset.method;
        document.querySelectorAll('.payment-radio').forEach(r => {
            r.checked = r.value === method;
        });
    });
});
</script>
</body>
</html>
