<?php include 'db.php'; ?>
<?php
$flight_id   = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;
$passengers  = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;
$date        = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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
        WHERE f.flight_id = $flight_id LIMIT 1";

$res = $conn->query($sql);
if ($res) $flight = $res->fetch_assoc();
if (!$flight) { header('Location: index.php'); exit; }

$dep = new DateTime($flight['departure_time']);
$arr = new DateTime($flight['arrival_time']);
$diff = $dep->diff($arr);
$duration = $diff->h . 'h ' . $diff->i . 'm';
$total_price = $flight['price'] * $passengers;

$error = '';

// ── Fetch already booked seats ──────────────────────────────
$taken = [];
$seat_res = $conn->query("SELECT seat_number FROM bookings WHERE flight_id = $flight_id AND seat_number IS NOT NULL AND seat_number != ''");
if ($seat_res) while ($row = $seat_res->fetch_assoc()) $taken[] = strtoupper(trim($row['seat_number']));

// ── Handle form submission ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate all passengers first
    $pax_data = [];
    for ($i = 0; $i < $passengers; $i++) {
        $fn   = trim($conn->real_escape_string($_POST['first_name'][$i]   ?? ''));
        $ln   = trim($conn->real_escape_string($_POST['last_name'][$i]    ?? ''));
        $em   = trim($conn->real_escape_string($_POST['email'][$i]        ?? ''));
        $ph   = trim($conn->real_escape_string($_POST['phone'][$i]        ?? ''));
        $pp   = trim($conn->real_escape_string($_POST['passport'][$i]     ?? ''));
        $seat = trim($conn->real_escape_string($_POST['seat_number'][$i]  ?? ''));
        $cls  = trim($conn->real_escape_string($_POST['seat_class'][$i]   ?? 'Economy'));
        $bw   = intval($_POST['baggage_weight'][$i] ?? 0);
        $bt   = trim($conn->real_escape_string($_POST['baggage_type'][$i] ?? 'Check-in'));

        if (!$fn || !$ln || !$em || !$ph || !$pp) {
            $error = "Please fill in all required fields for Passenger " . ($i + 1) . ".";
            break;
        }
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address for Passenger " . ($i + 1) . ".";
            break;
        }
        $pax_data[] = compact('fn','ln','em','ph','pp','seat','cls','bw','bt');
    }

    // Payment is shared (one payment for all passengers)
    $payment_method = trim($conn->real_escape_string($_POST['payment_method'] ?? 'UPI'));

    if (!$error) {
        $conn->begin_transaction();
        try {
            $first_booking_id     = null;
            $first_ticket_number  = null;

            foreach ($pax_data as $idx => $p) {
                // 1. Insert passenger
                $stmt = $conn->prepare("INSERT INTO passengers (first_name, last_name, email, phone, passport_number) VALUES (?,?,?,?,?)");
                $stmt->bind_param("sssss", $p['fn'], $p['ln'], $p['em'], $p['ph'], $p['pp']);
                $stmt->execute();
                $passenger_id = $conn->insert_id;
                $stmt->close();

                // 2. Insert booking
                $booking_date   = date('Y-m-d');
                $booking_status = 'Confirmed';
                $stmt2 = $conn->prepare("INSERT INTO bookings (passenger_id, flight_id, booking_date, seat_number, booking_status) VALUES (?,?,?,?,?)");
                $stmt2->bind_param("iisss", $passenger_id, $flight_id, $booking_date, $p['seat'], $booking_status);
                $stmt2->execute();
                $booking_id = $conn->insert_id;
                $stmt2->close();

                // 3. Insert ticket
                $ticket_number = 'SN' . strtoupper(substr(md5(uniqid()), 0, 8));
                $per_price = $flight['price'];
                $stmt3 = $conn->prepare("INSERT INTO tickets (booking_id, ticket_number, seat_class, price) VALUES (?,?,?,?)");
                $stmt3->bind_param("issi", $booking_id, $ticket_number, $p['cls'], $per_price);
                $stmt3->execute();
                $stmt3->close();

                // 4. Insert payment (one per passenger share)
                $payment_date = date('Y-m-d');
                $stmt4 = $conn->prepare("INSERT INTO payments (booking_id, payment_date, amount, payment_method) VALUES (?,?,?,?)");
                $stmt4->bind_param("isis", $booking_id, $payment_date, $per_price, $payment_method);
                $stmt4->execute();
                $stmt4->close();

                // 5. Insert baggage
                if ($p['bw'] > 0) {
                    $stmt5 = $conn->prepare("INSERT INTO baggage (passenger_id, weight, type) VALUES (?,?,?)");
                    $stmt5->bind_param("iis", $passenger_id, $p['bw'], $p['bt']);
                    $stmt5->execute();
                    $stmt5->close();
                }

                // Save first booking for confirmation redirect
                if ($idx === 0) {
                    $first_booking_id    = $booking_id;
                    $first_ticket_number = $ticket_number;
                }
            }

            $conn->commit();
            header("Location: confirmation.php?booking_id=$first_booking_id&ticket=$first_ticket_number&total_pax=$passengers");
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

            <!-- ── ONE FORM PER PASSENGER ───────────────────────── -->
            <?php for ($i = 0; $i < $passengers; $i++): ?>

            <div class="form-card" style="margin-bottom:20px;">

                <!-- Passenger header -->
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;padding-bottom:16px;border-bottom:2px solid var(--gray-100);">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;font-weight:800;flex-shrink:0;">
                        <?= $i + 1 ?>
                    </div>
                    <div>
                        <h2 style="font-size:17px;font-weight:700;color:var(--dark);margin:0;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-user" style="color:var(--primary);"></i>
                            Passenger <?= $i + 1 ?>
                            <?php if ($i === 0): ?>
                            <span style="font-size:11px;background:var(--primary-light);color:var(--primary);padding:2px 10px;border-radius:100px;font-weight:700;">Primary</span>
                            <?php endif; ?>
                        </h2>
                        <p style="font-size:12px;color:var(--gray-500);margin:2px 0 0;">Fill in details for this traveller</p>
                    </div>
                </div>

                <!-- Personal Info -->
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span style="color:red;">*</span></label>
                        <input type="text" name="first_name[<?= $i ?>]" placeholder="e.g. Rahul" required
                               value="<?= isset($_POST['first_name'][$i]) ? htmlspecialchars($_POST['first_name'][$i]) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:red;">*</span></label>
                        <input type="text" name="last_name[<?= $i ?>]" placeholder="e.g. Sharma" required
                               value="<?= isset($_POST['last_name'][$i]) ? htmlspecialchars($_POST['last_name'][$i]) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span style="color:red;">*</span></label>
                        <input type="email" name="email[<?= $i ?>]" placeholder="rahul@example.com" required
                               value="<?= isset($_POST['email'][$i]) ? htmlspecialchars($_POST['email'][$i]) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span style="color:red;">*</span></label>
                        <input type="tel" name="phone[<?= $i ?>]" placeholder="+91 98765 43210" required
                               value="<?= isset($_POST['phone'][$i]) ? htmlspecialchars($_POST['phone'][$i]) : '' ?>">
                    </div>
                    <div class="form-group full">
                        <label>Passport Number <span style="color:red;">*</span></label>
                        <input type="text" name="passport[<?= $i ?>]" placeholder="e.g. A1234567" required
                               value="<?= isset($_POST['passport'][$i]) ? htmlspecialchars($_POST['passport'][$i]) : '' ?>">
                    </div>
                </div>

                <!-- Seat Preferences -->
                <div style="margin-top:8px;padding-top:18px;border-top:1px solid var(--gray-100);">
                    <h3 style="font-size:15px;font-weight:700;color:var(--dark);margin-bottom:14px;">
                        <i class="fas fa-chair" style="color:var(--primary);margin-right:8px;"></i>Seat Preference
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Seat Number</label>
                            <input type="text" name="seat_number[<?= $i ?>]" id="seat_input_<?= $i ?>"
                                   placeholder="e.g. 3A"
                                   value="<?= isset($_POST['seat_number'][$i]) ? htmlspecialchars($_POST['seat_number'][$i]) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Seat Class</label>
                            <select name="seat_class[<?= $i ?>]">
                                <option value="Economy"    <?= (isset($_POST['seat_class'][$i]) && $_POST['seat_class'][$i]==='Economy')    ?'selected':'' ?>>Economy</option>
                                <option value="Business"   <?= (isset($_POST['seat_class'][$i]) && $_POST['seat_class'][$i]==='Business')   ?'selected':'' ?>>Business</option>
                                <option value="First Class"<?= (isset($_POST['seat_class'][$i]) && $_POST['seat_class'][$i]==='First Class')?'selected':'' ?>>First Class</option>
                            </select>
                        </div>
                    </div>

                    <!-- Seat Map -->
                    <div style="margin-top:10px;">
                        <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:10px;">Quick Seat Selection (Optional)</div>
                        <div style="display:flex;gap:16px;align-items:center;margin-bottom:10px;flex-wrap:wrap;">
                            <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-500);">
                                <span style="width:16px;height:16px;background:#dbeafe;border:1px solid #93c5fd;border-radius:3px;display:inline-block;"></span>Available
                            </span>
                            <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-500);">
                                <span style="width:16px;height:16px;background:#fee2e2;border:1px solid #fca5a5;border-radius:3px;display:inline-block;"></span>Taken
                            </span>
                            <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--gray-500);">
                                <span style="width:16px;height:16px;background:#1a56db;border-radius:3px;display:inline-block;"></span>Selected
                            </span>
                        </div>
                        <div id="seat-map-<?= $i ?>" style="display:flex;flex-direction:column;gap:6px;max-width:380px;max-height:280px;overflow-y:auto;padding-right:6px;">
                            <?php
                            $rows = ['1','2','3','4','5','6','7','8','9','10'];
                            $cols = ['A','B','C','D','E','F'];
                            foreach ($rows as $srow): ?>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <span style="width:26px;font-size:11px;color:var(--gray-500);text-align:right;flex-shrink:0;"><?= $srow ?></span>
                                <?php foreach ($cols as $ci => $col):
                                    $seat = $srow . $col;
                                    $isTaken = in_array($seat, $taken);
                                    if ($ci == 3): ?>
                                    <span style="width:16px;"></span>
                                    <?php endif; ?>
                                    <div class="seat-btn-<?= $i ?>"
                                         data-seat="<?= $seat ?>"
                                         onclick="<?= $isTaken ? '' : "selectSeat($i, '$seat')" ?>"
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

                <!-- Baggage -->
                <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--gray-100);">
                    <h3 style="font-size:15px;font-weight:700;color:var(--dark);margin-bottom:14px;">
                        <i class="fas fa-suitcase" style="color:var(--primary);margin-right:8px;"></i>Baggage
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Baggage Weight (kg)</label>
                            <input type="number" name="baggage_weight[<?= $i ?>]" placeholder="e.g. 15" min="0" max="100"
                                   value="<?= isset($_POST['baggage_weight'][$i]) ? htmlspecialchars($_POST['baggage_weight'][$i]) : '15' ?>">
                        </div>
                        <div class="form-group">
                            <label>Baggage Type</label>
                            <select name="baggage_type[<?= $i ?>]">
                                <option value="Check-in">Check-in Baggage</option>
                                <option value="Carry-on">Carry-on Baggage</option>
                                <option value="Special">Special Item</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div><!-- end form-card -->
            <?php endfor; ?>

            <!-- ── PAYMENT (shared for all passengers) ──────────── -->
            <div class="form-card">
                <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                <p style="font-size:13px;color:var(--gray-500);margin-bottom:18px;">
                    One payment covers all <?= $passengers ?> passenger<?= $passengers > 1 ? 's' : '' ?>.
                    Total: <strong style="color:var(--primary);">₹<?= number_format($total_price) ?></strong>
                </p>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <?php $methods = [
                        ['Credit Card','fas fa-credit-card','#1a56db'],
                        ['Debit Card', 'fas fa-credit-card','#16a34a'],
                        ['UPI',        'fas fa-mobile-alt', '#7c3aed'],
                        ['Net Banking','fas fa-university', '#92400e'],
                    ];
                    foreach ($methods as $mi => $m): ?>
                    <label style="flex:1;min-width:130px;">
                        <input type="radio" name="payment_method" value="<?= $m[0] ?>" <?= $mi==0?'checked':'' ?> style="display:none;" class="payment-radio">
                        <div class="payment-method-btn" data-method="<?= $m[0] ?>"
                             style="border:2px solid <?= $mi==0?'var(--primary)':'var(--gray-300)' ?>;border-radius:10px;padding:14px;text-align:center;cursor:pointer;transition:all 0.2s;background:<?= $mi==0?'var(--primary-light)':'white' ?>;">
                            <i class="<?= $m[1] ?>" style="font-size:20px;color:<?= $m[2] ?>;display:block;margin-bottom:6px;"></i>
                            <span style="font-size:12px;font-weight:600;color:var(--dark);"><?= $m[0] ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <input type="hidden" name="flight_id"   value="<?= $flight_id ?>">
            <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">

        </div><!-- end left column -->

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
                    <span class="summary-label">Taxes & Fees (5%)</span>
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
                    <i class="fas fa-users"></i> <?= $passengers ?> Passenger<?= $passengers>1?'s':'' ?> · <?= $passengers ?> Seat<?= $passengers>1?'s':'' ?>
                </div>

                <button type="submit" class="btn-proceed">
                    <i class="fas fa-lock"></i> Confirm & Pay ₹<?= number_format($total_price) ?>
                </button>

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
// Seat selection per passenger
function selectSeat(paxIndex, seat) {
    // Reset all seats in this passenger's map
    document.querySelectorAll('.seat-btn-' + paxIndex).forEach(btn => {
        if (!btn.dataset.taken) {
            btn.style.background = '#dbeafe';
            btn.style.border     = '1px solid #93c5fd';
            btn.style.color      = '#1e40af';
        }
    });
    // Highlight selected seat
    const btn = document.querySelector('.seat-btn-' + paxIndex + '[data-seat="' + seat + '"]');
    if (btn) {
        btn.style.background = '#1a56db';
        btn.style.border     = '1px solid #1a56db';
        btn.style.color      = 'white';
    }
    // Update the text input
    document.getElementById('seat_input_' + paxIndex).value = seat;
}

// Payment method toggle
document.querySelectorAll('.payment-method-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.payment-method-btn').forEach(b => {
            b.style.border     = '2px solid var(--gray-300)';
            b.style.background = 'white';
        });
        btn.style.border     = '2px solid var(--primary)';
        btn.style.background = 'var(--primary-light)';
        document.querySelectorAll('.payment-radio').forEach(r => {
            r.checked = r.value === btn.dataset.method;
        });
    });
});
</script>
</body>
</html>
