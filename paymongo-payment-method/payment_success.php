<?php
$package = $_GET['package'] ?? '';
$booking_date = $_GET['date'] ?? '';
$customer_name = $_GET['name'] ?? 'Guest';
$customer_email = $_GET['email'] ?? '';

// Connect to DB
$conn = new mysqli("localhost", "root", "", "booking_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prevent duplicates
$check = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
$check->bind_param("s", $booking_date);
$check->execute();
$check->bind_result($count);
$check->fetch();
$check->close();

if ($count > 0) {
    echo "This date is already booked.";
    exit;
}

// Insert with customer info
$stmt = $conn->prepare("INSERT INTO bookings (package, booking_date, customer_name, customer_email) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $package, $booking_date, $customer_name, $customer_email);

if ($stmt->execute()) {
    echo "<h2>✅ Booking confirmed!</h2>";
    echo "<p>Name: $customer_name<br>Email: $customer_email<br>Package: $package<br>Date: $booking_date</p>";
} else {
    echo "❌ Booking failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
