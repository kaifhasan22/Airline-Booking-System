<?php include 'db.php'; ?>
<?php
$booking = null;
$error   = '';
$searched = false;

if (isset($_POST['search']) || isset($_GET['ticket'])) {
    $searched = true;
    $ticket_input = isset($_POST['ticket_number']) ? trim($conn->real_escape_string($_POST['ticket_number'])) : trim($conn->real_escape_string($_GET['ticket']));

    if (empty($ticket_input)) {
        $error = 'Please enter a ticket number.';
    } else {
        $sql = "SELECT b.booking_id, b.booking_date, b.seat_number, b.booking_status,
                       p.first_name, p.last_name, p.email, p.phone, p.passport_number,
                       f.departure_time, f.arrival_time, f.price,
                       al.airline_name,
                       a1.city AS dep_city, a1.airport_name AS dep_airport,
                       a2.city AS arr_city, a2.airport_name AS arr_airport,
                       t.ticket_number, t.seat_class, t.price AS ticket_price,
                       py.payment_method, py.amount, py.payment_date,
                       bg.weight AS baggage_weight, bg.type AS baggage_type,
                       fs.gate_number, fs.status AS flight_status, fs.departure_date
                FROM tickets t
                JOIN bookings b ON t.booking_id = b.booking_id
                JOIN passengers p ON b.passenger_id = p.passenger_id
                JOIN flights f ON b.flight_id = f.flight_id
                JOIN airlines al ON f.airline_id = al.airline_id
                JOIN airports a1 ON f.departure_airport = a1.airport_id
                JOIN airports a2 ON f.arrival_airport = a2.airport_id
                LEFT JOIN payments py ON py.booking_id = b.booking_id
                LEFT JOIN baggage bg ON bg.passenger_id = p.passenger_id
                LEFT JOIN flight_schedule fs ON fs.flight_id = f.flight_id
                WHERE t.ticket_number = '$ticket_input'
                LIMIT 1";

        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $booking = $res->fetch_assoc();
        } else {
            $error = "No booking found for ticket number <strong>" . htmlspecialchars($ticket_input) . "</strong>. Please check and try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find My Booking – SkyNest Airlines</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }
        .result-animate { animation: slideUp 0.4s ease forwards; }
    </style>
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
            <a href="my_booking.php" class="active">My Booking</a>
            <a href="index.php" class="nav-cta"><i class="fas fa-user"></i> Login</a>
        </div>
    </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="page-header-container">
        <h1><i class="fas fa-search" style="margin-right:10px;opacity:0.8;"></i>Find My Booking</h1>
        <p style="opacity:0.8;margin-top:6px;">Enter your ticket number to retrieve your booking details</p>
    </div>
</div>

<div style="max-width:760px;margin:40px auto;padding:0 24px;">

    <!-- SEARCH BOX -->
    <div style="background:white;border-radius:16px;padding:32px;box-shadow:0 4px 16px rgba(0,0,0,0.08);margin-bottom:28px;">
        <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:6px;">
            <i class="fas fa-ticket-alt" style="color:#1a56db;margin-right:8px;"></i>Search by Ticket Number
        </h2>
        <p style="font-size:13px;color:#64748b;margin-bottom:24px;">Your ticket number looks like: <strong style="font-family:monospace;background:#f1f5f9;padding:2px 8px;border-radius:4px;">SN3F8A2B1C</strong></p>

        <form method="POST" action="">
            <div style="display:flex;gap:12px;">
                <div style="flex:1;position:relative;">
                    <i class="fas fa-ticket-alt" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#1a56db;font-size:16px;"></i>
                    <input
                        type="text"
                        name="ticket_number"
                        placeholder="e.g. SN3F8A2B1C"
                        value="<?= isset($_POST['ticket_number']) ? htmlspecialchars($_POST['ticket_number']) : (isset($_GET['ticket']) ? htmlspecialchars($_GET['ticket']) : '') ?>"
                        style="width:100%;padding:14px 14px 14px 44px;border:2px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:15px;outline:none;transition:border-color 0.2s;text-transform:uppercase;letter-spacing:1px;"
                        onfocus="this.style.borderColor='#1a56db'"
                        onblur="this.style.borderColor='#e2e8f0'"
                        required
                    >
                </div>
                <button type="submit" name="search" style="padding:14px 28px;background:linear-gradient(135deg,#1a56db,#1e3a8a);color:white;border:none;border-radius:10px;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-search"></i> Find Booking
                </button>
            </div>
        </form>

        <!-- TIPS -->
        <div style="display:flex;gap:20px;margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
                <i class="fas fa-envelope" style="color:#1a56db;"></i>
                Check your email for the ticket number
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
                <i class="fas fa-print" style="color:#1a56db;"></i>
                Check your printed confirmation
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
                <i class="fas fa-mobile-alt" style="color:#1a56db;"></i>
                Check SMS confirmation
            </div>
        </div>
    </div>

    <!-- ERROR MESSAGE -->
    <?php if ($searched && $error): ?>
    <div class="result-animate" style="background:#fee2e2;border:1px solid #fca5a5;border-radius:12px;padding:20px;margin-bottom:24px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;background:#fecaca;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">❌</div>
        <div>
            <div style="font-weight:700;color:#dc2626;margin-bottom:4px;">Booking Not Found</div>
            <div style="font-size:13px;color:#b91c1c;"><?= $error ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BOOKING RESULT -->
    <?php if ($booking): ?>
    <div class="result-animate">

        <!-- STATUS BANNER -->
        <?php
        $s = $booking['booking_status'];
        $is_confirmed = $s === 'Confirmed';
        $banner_bg    = $is_confirmed ? '#dcfce7' : '#fee2e2';
        $banner_border= $is_confirmed ? '#86efac' : '#fca5a5';
        $banner_color = $is_confirmed ? '#15803d' : '#dc2626';
        $banner_icon  = $is_confirmed ? '✅' : '❌';
        ?>
        <div style="background:<?= $banner_bg ?>;border:1px solid <?= $banner_border ?>;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:24px;"><?= $banner_icon ?></span>
                <div>
                    <div style="font-weight:700;color:<?= $banner_color ?>;font-size:15px;">Booking <?= htmlspecialchars($s) ?></div>
                    <div style="font-size:12px;color:<?= $banner_color ?>;opacity:0.8;">Booked on <?= date('d M Y', strtotime($booking['booking_date'])) ?></div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px;color:<?= $banner_color ?>;opacity:0.7;text-transform:uppercase;letter-spacing:1px;">Booking ID</div>
                <div style="font-weight:800;color:<?= $banner_color ?>;font-size:18px;">#<?= $booking['booking_id'] ?></div>
            </div>
        </div>

        <!-- TICKET CARD -->
        <div class="ticket-card" style="margin-bottom:20px;">
            <div class="ticket-header">
                <div>
                    <h2>✈ SkyNest Airlines – E-Ticket</h2>
                    <div class="ticket-number">
                        Ticket: <?= htmlspecialchars($booking['ticket_number']) ?>
                        &nbsp;·&nbsp;
                        <?= htmlspecialchars($booking['airline_name']) ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($booking['seat_class']) ?></div>
                    <div style="font-size:12px;opacity:0.8;">Seat <?= htmlspecialchars($booking['seat_number'] ?: 'TBD') ?></div>
                </div>
            </div>

            <div class="ticket-body">
                <?php
                $dep = new DateTime($booking['departure_time']);
                $arr = new DateTime($booking['arrival_time']);
                $diff = $dep->diff($arr);
                $duration = $diff->h . 'h ' . $diff->i . 'm';
                ?>

                <!-- ROUTE -->
                <div class="ticket-route">
                    <div class="ticket-city">
                        <h2><?= strtoupper(substr($booking['dep_city'],0,3)) ?></h2>
                        <p><?= htmlspecialchars($booking['dep_city']) ?></p>
                        <p style="font-size:12px;color:#64748b;"><?= htmlspecialchars($booking['dep_airport']) ?></p>
                        <p style="font-size:20px;font-weight:700;color:#0f172a;margin-top:8px;"><?= $dep->format('H:i') ?></p>
                        <p style="font-size:13px;color:#64748b;"><?= $dep->format('D, d M Y') ?></p>
                    </div>

                    <div class="ticket-divider">
                        <hr>
                        <i class="fas fa-plane" style="color:#1a56db;font-size:22px;"></i>
                        <hr>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= $duration ?></div>
                        <div style="font-size:11px;color:#1a56db;font-weight:600;">Non-stop</div>
                    </div>

                    <div class="ticket-city" style="text-align:right;">
                        <h2><?= strtoupper(substr($booking['arr_city'],0,3)) ?></h2>
                        <p><?= htmlspecialchars($booking['arr_city']) ?></p>
                        <p style="font-size:12px;color:#64748b;"><?= htmlspecialchars($booking['arr_airport']) ?></p>
                        <p style="font-size:20px;font-weight:700;color:#0f172a;margin-top:8px;"><?= $arr->format('H:i') ?></p>
                        <p style="font-size:13px;color:#64748b;"><?= $arr->format('D, d M Y') ?></p>
                    </div>
                </div>

                <!-- FLIGHT STATUS -->
                <?php if ($booking['flight_status']): ?>
                <div style="background:<?= $booking['flight_status']=='On Time'?'#dcfce7':($booking['flight_status']=='Delayed'?'#fee2e2':'#fef3c7') ?>;border-radius:8px;padding:10px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-info-circle" style="color:<?= $booking['flight_status']=='On Time'?'#16a34a':($booking['flight_status']=='Delayed'?'#dc2626':'#92400e') ?>;"></i>
                    <span style="font-size:13px;font-weight:600;color:<?= $booking['flight_status']=='On Time'?'#15803d':($booking['flight_status']=='Delayed'?'#dc2626':'#92400e') ?>;">
                        Flight Status: <?= htmlspecialchars($booking['flight_status']) ?>
                        <?php if ($booking['gate_number']): ?> · Gate <?= htmlspecialchars($booking['gate_number']) ?><?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- DETAILS -->
                <div class="ticket-details">
                    <div class="ticket-detail-item">
                        <label>Passenger</label>
                        <span><?= htmlspecialchars($booking['first_name'].' '.$booking['last_name']) ?></span>
                    </div>
                    <div class="ticket-detail-item">
                        <label>Passport</label>
                        <span><?= htmlspecialchars($booking['passport_number']) ?></span>
                    </div>
                    <div class="ticket-detail-item">
                        <label>Amount Paid</label>
                        <span style="color:#1a56db;">₹<?= number_format($booking['amount'] ?? $booking['ticket_price']) ?></span>
                    </div>
                    <div class="ticket-detail-item">
                        <label>Payment</label>
                        <span><?= htmlspecialchars($booking['payment_method'] ?? '—') ?></span>
                    </div>
                </div>

                <!-- BAGGAGE -->
                <?php if ($booking['baggage_weight']): ?>
                <div style="margin-top:20px;padding:12px 16px;background:#f8fafc;border-radius:8px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-suitcase" style="color:#1a56db;"></i>
                    <span style="font-size:13px;color:#334155;font-weight:500;">
                        Baggage: <?= $booking['baggage_weight'] ?>kg (<?= htmlspecialchars($booking['baggage_type']) ?>)
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CONTACT INFO -->
        <div style="background:white;border-radius:12px;padding:20px 24px;box-shadow:0 1px 3px rgba(0,0,0,0.06);margin-bottom:20px;">
            <h3 style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:14px;"><i class="fas fa-user-circle" style="color:#1a56db;margin-right:8px;"></i>Passenger Contact</h3>
            <div style="display:flex;gap:32px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:3px;">Email</div>
                    <div style="font-size:14px;font-weight:600;"><?= htmlspecialchars($booking['email']) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:3px;">Phone</div>
                    <div style="font-size:14px;font-weight:600;"><?= htmlspecialchars($booking['phone']) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:3px;">Payment Date</div>
                    <div style="font-size:14px;font-weight:600;"><?= $booking['payment_date'] ? date('d M Y', strtotime($booking['payment_date'])) : '—' ?></div>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button onclick="window.print()" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:#1a56db;color:white;border:none;border-radius:10px;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fas fa-print"></i> Print Ticket
            </button>
            <a href="index.php" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:white;color:#0f172a;border:2px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:14px;font-weight:700;text-decoration:none;">
                <i class="fas fa-plane"></i> Book Another Flight
            </a>
            <a href="my_booking.php" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:white;color:#1a56db;border:2px solid #1a56db;border-radius:10px;font-family:inherit;font-size:14px;font-weight:700;text-decoration:none;">
                <i class="fas fa-search"></i> Search Again
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- EMPTY STATE (first visit) -->
    <?php if (!$searched): ?>
    <div style="background:white;border-radius:16px;padding:40px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
        <div style="font-size:56px;margin-bottom:16px;">🎫</div>
        <h3 style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:8px;">Retrieve Your Booking</h3>
        <p style="color:#64748b;font-size:14px;max-width:380px;margin:0 auto 24px;">Enter the ticket number from your booking confirmation email to view your flight details.</p>
        <div style="display:flex;gap:20px;justify-content:center;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;background:#f8fafc;padding:10px 16px;border-radius:8px;">
                <i class="fas fa-shield-alt" style="color:#16a34a;"></i> Safe & Secure
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;background:#f8fafc;padding:10px 16px;border-radius:8px;">
                <i class="fas fa-bolt" style="color:#f97316;"></i> Instant Results
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;background:#f8fafc;padding:10px 16px;border-radius:8px;">
                <i class="fas fa-print" style="color:#1a56db;"></i> Print Ticket
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- FOOTER -->
<footer class="footer" style="margin-top:40px;">
    <div class="footer-bottom" style="border-top:none;">
        <span>© 2025 SkyNest Airlines. All rights reserved.</span>
        <span><i class="fas fa-lock" style="margin-right:6px;"></i>100% Secure</span>
    </div>
</footer>

</body>
</html>
