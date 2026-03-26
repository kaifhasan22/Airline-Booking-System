<?php
session_start(); // MUST be first line before anything else
include 'db.php';

// Simple admin password protection
$admin_password = 'admin123'; // Change this to your own password
$logged_in = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

if (isset($_POST['admin_login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin'] = true;
        $logged_in = true;
    } else {
        $login_error = 'Incorrect password. Try again.';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ---- DELETE BOOKING ----
$delete_msg = '';
if (isset($_POST['delete_booking']) && isset($_POST['booking_id']) && $logged_in) {
    $del_id = intval($_POST['booking_id']);

    $conn->begin_transaction();
    try {
        // Get passenger_id first
        $res = $conn->query("SELECT passenger_id FROM bookings WHERE booking_id = $del_id");
        $row = $res ? $res->fetch_assoc() : null;
        $passenger_id = $row ? intval($row['passenger_id']) : 0;

        // Delete in correct order to respect foreign keys
        $conn->query("DELETE FROM payments WHERE booking_id = $del_id");
        $conn->query("DELETE FROM tickets  WHERE booking_id = $del_id");
        if ($passenger_id) {
            $conn->query("DELETE FROM baggage WHERE passenger_id = $passenger_id");
        }
        $conn->query("DELETE FROM bookings WHERE booking_id = $del_id");

        $conn->commit();
        $delete_msg = "success:Booking #$del_id has been deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $delete_msg = "error:Failed to delete booking #$del_id. Please try again.";
    }
}

// Active tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'bookings';

// ---- FETCH DATA ----

// Bookings
$bookings = [];
$bq = "SELECT b.booking_id, b.booking_date, b.seat_number, b.booking_status,
              p.first_name, p.last_name, p.email, p.phone,
              f.departure_time, f.arrival_time, f.price,
              al.airline_name,
              a1.city AS dep_city, a2.city AS arr_city,
              t.ticket_number, t.seat_class,
              py.amount, py.payment_method
       FROM bookings b
       JOIN passengers p ON b.passenger_id = p.passenger_id
       JOIN flights f ON b.flight_id = f.flight_id
       JOIN airlines al ON f.airline_id = al.airline_id
       JOIN airports a1 ON f.departure_airport = a1.airport_id
       JOIN airports a2 ON f.arrival_airport = a2.airport_id
       LEFT JOIN tickets t ON t.booking_id = b.booking_id
       LEFT JOIN payments py ON py.booking_id = b.booking_id
       ORDER BY b.booking_id DESC";
$br = $conn->query($bq);
if ($br) while ($row = $br->fetch_assoc()) $bookings[] = $row;

// Passengers
$passengers = [];
$pr = $conn->query("SELECT * FROM passengers ORDER BY passenger_id DESC");
if ($pr) while ($row = $pr->fetch_assoc()) $passengers[] = $row;

// Flights
$flights = [];
$fq = "SELECT f.*, al.airline_name,
              a1.city AS dep_city, a1.airport_name AS dep_airport,
              a2.city AS arr_city, a2.airport_name AS arr_airport,
              fs.departure_date, fs.gate_number, fs.status
       FROM flights f
       JOIN airlines al ON f.airline_id = al.airline_id
       JOIN airports a1 ON f.departure_airport = a1.airport_id
       JOIN airports a2 ON f.arrival_airport = a2.airport_id
       LEFT JOIN flight_schedule fs ON fs.flight_id = f.flight_id
       ORDER BY f.flight_id DESC";
$fr = $conn->query($fq);
if ($fr) while ($row = $fr->fetch_assoc()) $flights[] = $row;

// Payments
$payments = [];
$pq = "SELECT py.*, b.booking_id, b.seat_number,
              p.first_name, p.last_name,
              a1.city AS dep_city, a2.city AS arr_city
       FROM payments py
       JOIN bookings b ON py.booking_id = b.booking_id
       JOIN passengers p ON b.passenger_id = p.passenger_id
       JOIN flights f ON b.flight_id = f.flight_id
       JOIN airports a1 ON f.departure_airport = a1.airport_id
       JOIN airports a2 ON f.arrival_airport = a2.airport_id
       ORDER BY py.payment_id DESC";
$pyr = $conn->query($pq);
if ($pyr) while ($row = $pyr->fetch_assoc()) $payments[] = $row;

// Stats
$total_bookings  = count($bookings);
$total_revenue   = array_sum(array_column($payments, 'amount'));
$total_passengers = count($passengers);
$total_flights   = count($flights);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel – SkyNest Airlines</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; }

        .admin-wrapper { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .admin-sidebar {
            width: 240px;
            background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 100%);
            padding: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #1a56db, #f97316);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 16px;
        }

        .sidebar-logo-text { color: white; font-weight: 700; font-size: 16px; font-family: 'Playfair Display', serif; }
        .sidebar-logo-sub { color: rgba(255,255,255,0.5); font-size: 10px; letter-spacing: 1px; text-transform: uppercase; }

        .sidebar-nav { padding: 20px 12px; flex: 1; }

        .sidebar-label {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 0 8px;
            margin-bottom: 8px;
            margin-top: 16px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 2px;
        }

        .sidebar-link:hover { background: rgba(255,255,255,0.08); color: white; }
        .sidebar-link.active { background: rgba(26,86,219,0.4); color: white; border-left: 3px solid #f97316; }
        .sidebar-link i { width: 18px; text-align: center; font-size: 15px; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* MAIN CONTENT */
        .admin-main {
            margin-left: 240px;
            flex: 1;
            padding: 0;
        }

        .admin-topbar {
            background: white;
            padding: 16px 32px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .admin-topbar h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .admin-topbar p { font-size: 13px; color: #64748b; margin-top: 2px; }

        .admin-content { padding: 28px 32px; }

        /* STAT CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 16px;
            border-left: 4px solid var(--color);
        }

        .stat-icon {
            width: 52px; height: 52px;
            background: var(--bg);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            color: var(--color);
            flex-shrink: 0;
        }

        .stat-number { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }

        /* DATA TABLE */
        .data-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .data-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .data-card-header h2 { font-size: 16px; font-weight: 700; color: #0f172a; }

        .search-input {
            padding: 8px 14px 8px 36px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            width: 220px;
            background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center;
        }

        .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .admin-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        .admin-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .admin-table tr:hover td { background: #f8fafc; }
        .admin-table tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-blue  { background: #dbeafe; color: #1d4ed8; }
        .badge-orange{ background: #fef3c7; color: #92400e; }
        .badge-red   { background: #fee2e2; color: #dc2626; }

        /* LOGIN */
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 380px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .login-card .logo-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #1a56db, #f97316);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: white;
            margin: 0 auto 16px;
        }

        .login-card h2 { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px; font-family: 'Playfair Display', serif; }
        .login-card p { color: #64748b; font-size: 14px; margin-bottom: 28px; }

        .login-input {
            width: 100%;
            padding: 13px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            outline: none;
            margin-bottom: 14px;
            transition: border-color 0.2s;
        }
        .login-input:focus { border-color: #1a56db; }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a56db, #1e3a8a);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }

        @media (max-width: 900px) {
            .admin-sidebar { display: none; }
            .admin-main { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<?php if (!$logged_in): ?>
<!-- LOGIN SCREEN -->
<div class="login-wrap">
    <div class="login-card">
        <div class="logo-icon"><i class="fas fa-paper-plane"></i></div>
        <h2>Admin Panel</h2>
        <p>SkyNest Airlines — Staff Access Only</p>
        <?php if (isset($login_error)): ?>
        <div style="background:#fee2e2;color:#dc2626;padding:10px;border-radius:8px;font-size:13px;margin-bottom:14px;">
            <i class="fas fa-exclamation-circle"></i> <?= $login_error ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" class="login-input" placeholder="Enter admin password" required autofocus>
            <button type="submit" name="admin_login" class="login-btn">
                <i class="fas fa-lock"></i> Login to Admin Panel
            </button>
        </form>
        <div style="margin-top:16px;font-size:12px;color:#94a3b8;">Default password: <strong>admin123</strong></div>
        <a href="index.php" style="display:block;margin-top:12px;font-size:13px;color:#1a56db;text-decoration:none;">← Back to Website</a>
    </div>
</div>

<?php else: ?>
<!-- ADMIN DASHBOARD -->
<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon"><i class="fas fa-paper-plane"></i></div>
            <div>
                <div class="sidebar-logo-text">SkyNest</div>
                <div class="sidebar-logo-sub">Admin Panel</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Dashboard</div>
            <a href="admin.php?tab=bookings" class="sidebar-link <?= $tab=='bookings' ? 'active' : '' ?>">
                <i class="fas fa-ticket-alt"></i> All Bookings
            </a>
            <a href="admin.php?tab=passengers" class="sidebar-link <?= $tab=='passengers' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Passengers
            </a>
            <a href="admin.php?tab=flights" class="sidebar-link <?= $tab=='flights' ? 'active' : '' ?>">
                <i class="fas fa-plane"></i> Flights
            </a>
            <a href="admin.php?tab=payments" class="sidebar-link <?= $tab=='payments' ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i> Payments
            </a>

            <div class="sidebar-label">Quick Links</div>
            <a href="index.php" class="sidebar-link" target="_blank">
                <i class="fas fa-home"></i> View Website
            </a>
            <a href="my_booking.php" class="sidebar-link" target="_blank">
                <i class="fas fa-search"></i> Search Booking
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="admin.php?logout=1" class="sidebar-link" style="color:rgba(255,100,100,0.8);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>
                    <?php
                    $titles = ['bookings'=>'All Bookings','passengers'=>'Passengers','flights'=>'Flights','payments'=>'Payments'];
                    echo $titles[$tab] ?? 'Dashboard';
                    ?>
                </h1>
                <p>SkyNest Airlines · <?= date('D, d M Y') ?></p>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#1a56db,#f97316);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:14px;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#0f172a;">Admin</div>
                    <div style="font-size:11px;color:#64748b;">Administrator</div>
                </div>
            </div>
        </div>

        <div class="admin-content">

            <!-- DELETE MESSAGE -->
            <?php if ($delete_msg):
                $parts = explode(":", $delete_msg, 2);
                $dtype = $parts[0]; $dtext = $parts[1];
                $is_succ = $dtype === "success";
            ?>
            <div style="background:<?= $is_succ?"#dcfce7":"#fee2e2" ?>;border:1px solid <?= $is_succ?"#86efac":"#fca5a5" ?>;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <i class="fas <?= $is_succ?"fa-check-circle":"fa-exclamation-circle" ?>" style="color:<?= $is_succ?"#16a34a":"#dc2626" ?>;font-size:18px;"></i>
                <span style="font-size:14px;font-weight:600;color:<?= $is_succ?"#15803d":"#dc2626" ?>;"><?= htmlspecialchars($dtext) ?></span>
            </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card" style="--color:#1a56db;--bg:#dbeafe;">
                    <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div>
                        <div class="stat-number"><?= $total_bookings ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                </div>
                <div class="stat-card" style="--color:#16a34a;--bg:#dcfce7;">
                    <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                    <div>
                        <div class="stat-number">₹<?= number_format($total_revenue) ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="stat-card" style="--color:#7c3aed;--bg:#ede9fe;">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="stat-number"><?= $total_passengers ?></div>
                        <div class="stat-label">Passengers</div>
                    </div>
                </div>
                <div class="stat-card" style="--color:#f97316;--bg:#ffedd5;">
                    <div class="stat-icon"><i class="fas fa-plane"></i></div>
                    <div>
                        <div class="stat-number"><?= $total_flights ?></div>
                        <div class="stat-label">Flights</div>
                    </div>
                </div>
            </div>

            <!-- BOOKINGS TAB -->
            <?php if ($tab === 'bookings'): ?>
            <div class="data-card">
                <div class="data-card-header">
                    <h2><i class="fas fa-ticket-alt" style="color:#1a56db;margin-right:8px;"></i>All Bookings (<?= count($bookings) ?>)</h2>
                    <input type="text" class="search-input" placeholder="Search bookings..." oninput="filterTable(this,'bookings-table')">
                </div>
                <div style="overflow-x:auto;">
                <table class="admin-table" id="bookings-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Airline</th>
                            <th>Departure</th>
                            <th>Ticket No.</th>
                            <th>Seat</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><strong>#<?= $b['booking_id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></div>
                                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($b['email']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($b['dep_city']) ?> → <?= htmlspecialchars($b['arr_city']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($b['airline_name']) ?></td>
                            <td><?= date('d M Y', strtotime($b['departure_time'])) ?><br><span style="font-size:11px;color:#64748b;"><?= date('H:i', strtotime($b['departure_time'])) ?></span></td>
                            <td><span style="font-family:monospace;font-size:12px;background:#f1f5f9;padding:3px 8px;border-radius:4px;"><?= htmlspecialchars($b['ticket_number'] ?? '—') ?></span></td>
                            <td><?= htmlspecialchars($b['seat_number'] ?? '—') ?> <span style="font-size:11px;color:#64748b;"><?= htmlspecialchars($b['seat_class'] ?? '') ?></span></td>
                            <td><strong>₹<?= number_format($b['amount'] ?? 0) ?></strong></td>
                            <td><?= htmlspecialchars($b['payment_method'] ?? '—') ?></td>
                            <td>
                                <?php
                                $s = $b['booking_status'];
                                $cls = $s=='Confirmed' ? 'badge-green' : ($s=='Cancelled' ? 'badge-red' : 'badge-orange');
                                ?>
                                <span class="badge <?= $cls ?>"><?= htmlspecialchars($s) ?></span>
                            </td>
                            <td style="text-align:center;">
                                <button
                                    onclick="confirmDelete(<?= $b['booking_id'] ?>, '<?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?>')"
                                    style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;padding:6px 14px;border-radius:6px;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.2s;"
                                    onmouseover="this.style.background='#dc2626';this.style.color='white';"
                                    onmouseout="this.style.background='#fee2e2';this.style.color='#dc2626';">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                        <tr><td colspan="11" style="text-align:center;padding:40px;color:#64748b;">No bookings found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- PASSENGERS TAB -->
            <?php elseif ($tab === 'passengers'): ?>
            <div class="data-card">
                <div class="data-card-header">
                    <h2><i class="fas fa-users" style="color:#7c3aed;margin-right:8px;"></i>All Passengers (<?= count($passengers) ?>)</h2>
                    <input type="text" class="search-input" placeholder="Search passengers..." oninput="filterTable(this,'passengers-table')">
                </div>
                <div style="overflow-x:auto;">
                <table class="admin-table" id="passengers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Passport Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($passengers as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['passenger_id'] ?></strong></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;background:linear-gradient(135deg,#ede9fe,#c4b5fd);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#7c3aed;flex-shrink:0;">
                                        <?= strtoupper(substr($p['first_name'],0,1).substr($p['last_name'],0,1)) ?>
                                    </div>
                                    <span style="font-weight:600;"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= htmlspecialchars($p['phone']) ?></td>
                            <td><span style="font-family:monospace;background:#f1f5f9;padding:3px 8px;border-radius:4px;"><?= htmlspecialchars($p['passport_number']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($passengers)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:40px;color:#64748b;">No passengers found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- FLIGHTS TAB -->
            <?php elseif ($tab === 'flights'): ?>
            <div class="data-card">
                <div class="data-card-header">
                    <h2><i class="fas fa-plane" style="color:#f97316;margin-right:8px;"></i>All Flights (<?= count($flights) ?>)</h2>
                    <input type="text" class="search-input" placeholder="Search flights..." oninput="filterTable(this,'flights-table')">
                </div>
                <div style="overflow-x:auto;">
                <table class="admin-table" id="flights-table">
                    <thead>
                        <tr>
                            <th>Flight ID</th>
                            <th>Airline</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Price</th>
                            <th>Gate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flights as $f):
                            $dep = new DateTime($f['departure_time']);
                            $arr = new DateTime($f['arrival_time']);
                            $diff = $dep->diff($arr);
                        ?>
                        <tr>
                            <td><strong>#<?= str_pad($f['flight_id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                            <td><?= htmlspecialchars($f['airline_name']) ?></td>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($f['dep_city']) ?> → <?= htmlspecialchars($f['arr_city']) ?></div>
                                <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($f['dep_airport']) ?></div>
                            </td>
                            <td><?= $dep->format('d M Y H:i') ?></td>
                            <td><?= $arr->format('d M Y H:i') ?><br><span style="font-size:11px;color:#64748b;"><?= $diff->h ?>h <?= $diff->i ?>m</span></td>
                            <td><strong>₹<?= number_format($f['price']) ?></strong></td>
                            <td><?= htmlspecialchars($f['gate_number'] ?? '—') ?></td>
                            <td>
                                <?php
                                $s = $f['status'] ?? 'Scheduled';
                                $cls = $s=='On Time' ? 'badge-green' : ($s=='Delayed' ? 'badge-red' : 'badge-orange');
                                ?>
                                <span class="badge <?= $cls ?>"><?= htmlspecialchars($s) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($flights)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:#64748b;">No flights found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- PAYMENTS TAB -->
            <?php elseif ($tab === 'payments'): ?>
            <div class="data-card">
                <div class="data-card-header">
                    <h2><i class="fas fa-credit-card" style="color:#16a34a;margin-right:8px;"></i>All Payments (<?= count($payments) ?>)</h2>
                    <input type="text" class="search-input" placeholder="Search payments..." oninput="filterTable(this,'payments-table')">
                </div>
                <div style="overflow-x:auto;">
                <table class="admin-table" id="payments-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking ID</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['payment_id'] ?></strong></td>
                            <td>#<?= $p['booking_id'] ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></td>
                            <td><?= htmlspecialchars($p['dep_city']) ?> → <?= htmlspecialchars($p['arr_city']) ?></td>
                            <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                            <td><strong style="color:#16a34a;">₹<?= number_format($p['amount']) ?></strong></td>
                            <td>
                                <?php
                                $m = $p['payment_method'];
                                $cls = $m=='Credit Card'?'badge-blue':($m=='UPI'?'badge-green':($m=='Net Banking'?'badge-orange':'badge-blue'));
                                ?>
                                <span class="badge <?= $cls ?>"><?= htmlspecialchars($m) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">No payments found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<?php endif; ?>

<script>
function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
}

function confirmDelete(bookingId, passengerName) {
    document.getElementById('modal-booking-id').textContent   = '#' + bookingId;
    document.getElementById('modal-passenger-name').textContent = passengerName;
    document.getElementById('delete-booking-id').value        = bookingId;
    document.getElementById('delete-modal').style.display     = 'flex';
}

function closeModal() {
    document.getElementById('delete-modal').style.display = 'none';
}

// Close modal if clicking outside the box
document.addEventListener('click', function(e) {
    const modal = document.getElementById('delete-modal');
    if (e.target === modal) closeModal();
});
</script>

<!-- DELETE CONFIRMATION MODAL -->
<div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
    <div style="background:white;border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:popIn 0.3s ease;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:60px;height:60px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:26px;">🗑️</div>
            <h3 style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:8px;">Delete Booking?</h3>
            <p style="font-size:14px;color:#64748b;line-height:1.6;">
                You are about to permanently delete booking
                <strong id="modal-booking-id" style="color:#dc2626;"></strong>
                for passenger <strong id="modal-passenger-name"></strong>.
            </p>
            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;margin-top:12px;font-size:12px;color:#92400e;">
                <i class="fas fa-exclamation-triangle"></i>
                This will also delete the ticket, payment and baggage records. This <strong>cannot be undone</strong>.
            </div>
        </div>
        <form method="POST" action="admin.php?tab=bookings">
            <input type="hidden" name="booking_id" id="delete-booking-id">
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeModal()" style="flex:1;padding:12px;background:#f1f5f9;color:#334155;border:none;border-radius:10px;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" name="delete_booking" style="flex:1;padding:12px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:white;border:none;border-radius:10px;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-trash-alt"></i> Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
