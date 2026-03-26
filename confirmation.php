<?php include 'db.php'; ?>
<?php
$booking_id    = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$ticket_number = isset($_GET['ticket']) ? htmlspecialchars($_GET['ticket']) : '';

if (!$booking_id) { header('Location: index.php'); exit; }

// Fetch full booking details
$booking = null;
$sql = "SELECT b.booking_id, b.booking_date, b.seat_number, b.booking_status,
               p.first_name, p.last_name, p.email, p.phone, p.passport_number,
               f.departure_time, f.arrival_time, f.price AS base_price,
               al.airline_name,
               a1.city AS dep_city, a1.airport_name AS dep_airport,
               a2.city AS arr_city, a2.airport_name AS arr_airport,
               t.ticket_number, t.seat_class, t.price AS total_price,
               py.payment_method, py.amount,
               bg.weight AS baggage_weight, bg.type AS baggage_type
        FROM bookings b
        JOIN passengers p ON b.passenger_id = p.passenger_id
        JOIN flights f ON b.flight_id = f.flight_id
        JOIN airlines al ON f.airline_id = al.airline_id
        JOIN airports a1 ON f.departure_airport = a1.airport_id
        JOIN airports a2 ON f.arrival_airport = a2.airport_id
        LEFT JOIN tickets t ON t.booking_id = b.booking_id
        LEFT JOIN payments py ON py.booking_id = b.booking_id
        LEFT JOIN baggage bg ON bg.passenger_id = p.passenger_id
        WHERE b.booking_id = $booking_id
        LIMIT 1";

$res = $conn->query($sql);
if ($res) $booking = $res->fetch_assoc();
if (!$booking) { header('Location: index.php'); exit; }

$dep = new DateTime($booking['departure_time']);
$arr = new DateTime($booking['arrival_time']);
$diff = $dep->diff($arr);
$duration = $diff->h . 'h ' . $diff->i . 'm';
$tn = $booking['ticket_number'] ?: $ticket_number;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed – SkyNest Airlines</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            70% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .confirm-icon { animation: popIn 0.6s cubic-bezier(0.34,1.56,0.64,1) forwards; }
        .ticket-card { animation: slideUp 0.5s ease 0.3s both; }
        .confirm-actions { animation: slideUp 0.5s ease 0.5s both; }

        @media print {
            .navbar, .confirm-actions, footer { display: none !important; }
            .ticket-card { box-shadow: none !important; }
        }
    </style>
</head>
<body style="background:var(--gray-100);">

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
            <a href="index.php">Book Another Flight</a>
            <a href="index.php" class="nav-cta"><i class="fas fa-user"></i> My Bookings</a>
        </div>
    </div>
</nav>

<div class="confirm-container">

    <!-- SUCCESS HEADER -->
    <div class="confirm-header">
        <div class="confirm-icon">✅</div>
        <h1>Booking Confirmed!</h1>
        <p>Your flight has been booked successfully. Safe travels, <?= htmlspecialchars($booking['first_name']) ?>! ✈️</p>

        <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
            <div style="background:#dcfce7;border:1px solid #86efac;padding:8px 20px;border-radius:100px;font-size:13px;font-weight:700;color:#15803d;">
                <i class="fas fa-check-circle"></i> Payment Successful
            </div>
            <div style="background:#dbeafe;border:1px solid #93c5fd;padding:8px 20px;border-radius:100px;font-size:13px;font-weight:700;color:#1e40af;">
                <i class="fas fa-ticket-alt"></i> Ticket: <?= htmlspecialchars($tn) ?>
            </div>
            <div style="background:#fef3c7;border:1px solid #fcd34d;padding:8px 20px;border-radius:100px;font-size:13px;font-weight:700;color:#92400e;">
                <i class="fas fa-hashtag"></i> Booking ID: #<?= $booking_id ?>
            </div>
        </div>
    </div>

    <!-- TICKET CARD -->
    <div class="ticket-card">
        <div class="ticket-header">
            <div>
                <h2>✈ SkyNest Airlines</h2>
                <div class="ticket-number">Ticket No: <?= htmlspecialchars($tn) ?> &nbsp;·&nbsp; Booking #<?= $booking_id ?></div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:20px;font-weight:700;"><?= htmlspecialchars($booking['seat_class']) ?></div>
                <div style="font-size:12px;opacity:0.8;">Seat <?= htmlspecialchars($booking['seat_number'] ?: 'TBD') ?></div>
            </div>
        </div>

        <div class="ticket-body">
            <!-- ROUTE -->
            <div class="ticket-route">
                <div class="ticket-city">
                    <h2><?= strtoupper(substr($booking['dep_city'], 0, 3)) ?></h2>
                    <p><?= htmlspecialchars($booking['dep_city']) ?></p>
                    <p style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($booking['dep_airport']) ?></p>
                    <p style="font-size:20px;font-weight:700;color:var(--dark);margin-top:8px;"><?= $dep->format('H:i') ?></p>
                    <p style="font-size:13px;color:var(--gray-500);"><?= $dep->format('D, d M Y') ?></p>
                </div>

                <div class="ticket-divider">
                    <hr>
                    <i class="fas fa-plane" style="color:var(--primary);font-size:22px;"></i>
                    <hr>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;"><?= $duration ?></div>
                    <div style="font-size:11px;color:var(--primary);font-weight:600;">Non-stop</div>
                </div>

                <div class="ticket-city" style="text-align:right;">
                    <h2><?= strtoupper(substr($booking['arr_city'], 0, 3)) ?></h2>
                    <p><?= htmlspecialchars($booking['arr_city']) ?></p>
                    <p style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($booking['arr_airport']) ?></p>
                    <p style="font-size:20px;font-weight:700;color:var(--dark);margin-top:8px;"><?= $arr->format('H:i') ?></p>
                    <p style="font-size:13px;color:var(--gray-500);"><?= $arr->format('D, d M Y') ?></p>
                </div>
            </div>

            <!-- DETAILS GRID -->
            <div class="ticket-details">
                <div class="ticket-detail-item">
                    <label>Passenger</label>
                    <span><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></span>
                </div>
                <div class="ticket-detail-item">
                    <label>Passport</label>
                    <span><?= htmlspecialchars($booking['passport_number']) ?></span>
                </div>
                <div class="ticket-detail-item">
                    <label>Airline</label>
                    <span><?= htmlspecialchars($booking['airline_name']) ?></span>
                </div>
                <div class="ticket-detail-item">
                    <label>Status</label>
                    <span style="color:var(--success);">✓ <?= htmlspecialchars($booking['booking_status']) ?></span>
                </div>
                <div class="ticket-detail-item">
                    <label>Seat</label>
                    <span><?= htmlspecialchars($booking['seat_number'] ?: 'TBD') ?> (<?= htmlspecialchars($booking['seat_class']) ?>)</span>
                </div>
                <div class="ticket-detail-item">
                    <label>Amount Paid</label>
                    <span style="color:var(--primary);">₹<?= number_format($booking['total_price'] ?: $booking['amount']) ?></span>
                </div>
                <div class="ticket-detail-item">
                    <label>Payment</label>
                    <span><?= htmlspecialchars($booking['payment_method']) ?></span>
                </div>
                <div class="ticket-detail-item">
                    <label>Booking Date</label>
                    <span><?= date('d M Y', strtotime($booking['booking_date'])) ?></span>
                </div>
            </div>

            <!-- BAGGAGE INFO -->
            <?php if ($booking['baggage_weight']): ?>
            <div style="margin-top:20px;padding:14px;background:var(--gray-100);border-radius:10px;display:flex;align-items:center;gap:12px;">
                <i class="fas fa-suitcase" style="color:var(--primary);font-size:18px;"></i>
                <span style="font-size:14px;font-weight:500;color:var(--dark);">
                    Baggage: <?= $booking['baggage_weight'] ?>kg (<?= htmlspecialchars($booking['baggage_type']) ?>)
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PASSENGER CONTACT INFO -->
    <div style="background:var(--white);border-radius:var(--radius-md);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:20px;">
        <h3 style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:16px;"><i class="fas fa-envelope" style="color:var(--primary);margin-right:8px;"></i>Confirmation Sent To</h3>
        <div style="display:flex;gap:32px;flex-wrap:wrap;">
            <div>
                <div style="font-size:11px;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:4px;">Email</div>
                <div style="font-size:15px;font-weight:600;color:var(--dark);"><?= htmlspecialchars($booking['email']) ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:4px;">Phone</div>
                <div style="font-size:15px;font-weight:600;color:var(--dark);"><?= htmlspecialchars($booking['phone']) ?></div>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="confirm-actions" style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-bottom:40px;">
        <button onclick="window.print()" style="display:flex;align-items:center;gap:8px;padding:14px 28px;background:var(--primary);color:white;border:none;border-radius:var(--radius-md);font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;">
            <i class="fas fa-print"></i> Print Ticket
        </button>
        <a href="index.php" style="display:flex;align-items:center;gap:8px;padding:14px 28px;background:var(--white);color:var(--dark);border:2px solid var(--gray-300);border-radius:var(--radius-md);font-family:inherit;font-size:15px;font-weight:700;text-decoration:none;">
            <i class="fas fa-plus"></i> Book Another Flight
        </a>
        <button onclick="shareTicket()" style="display:flex;align-items:center;gap:8px;padding:14px 28px;background:var(--white);color:var(--primary);border:2px solid var(--primary);border-radius:var(--radius-md);font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;">
            <i class="fas fa-share-alt"></i> Share
        </button>
    </div>

    <!-- IMPORTANT INFO -->
    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:var(--radius-md);padding:20px;margin-bottom:32px;">
        <h4 style="font-size:14px;font-weight:700;color:#92400e;margin-bottom:10px;"><i class="fas fa-info-circle"></i> Important Information</h4>
        <ul style="list-style:none;padding:0;">
            <li style="font-size:13px;color:#92400e;margin-bottom:6px;">✓ Please arrive at the airport at least 2 hours before departure</li>
            <li style="font-size:13px;color:#92400e;margin-bottom:6px;">✓ Carry a valid government ID along with your passport</li>
            <li style="font-size:13px;color:#92400e;margin-bottom:6px;">✓ Web check-in opens 24 hours before departure</li>
            <li style="font-size:13px;color:#92400e;">✓ Your e-ticket has been sent to <?= htmlspecialchars($booking['email']) ?></li>
        </ul>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-bottom" style="border-top:none;">
        <span>© 2025 SkyNest Airlines. All rights reserved.</span>
        <span><i class="fas fa-lock" style="margin-right:6px;"></i>100% Secure Payments</span>
    </div>
</footer>

<script>
function shareTicket() {
    if (navigator.share) {
        navigator.share({
            title: 'My SkyNest Booking',
            text: `Booking confirmed! Ticket: <?= htmlspecialchars($tn) ?> · <?= htmlspecialchars($booking['dep_city']) ?> → <?= htmlspecialchars($booking['arr_city']) ?>`,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Booking link copied to clipboard!');
    }
}
</script>
</body>
</html>
