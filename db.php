<?php
$host = 'localhost';
$dbname = 'airline_booking_system';
$username = 'root';
$password = ''; // Default XAMPP password is empty

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
