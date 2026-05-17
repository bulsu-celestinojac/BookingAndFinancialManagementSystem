<?php
$host = "sql101.infinityfree.com";
$username = "if0_39215582";
$password = "2S3iEryUecZm7N";
$dbname = "if0_39215582_bookings";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database error.");
}

$sql = "SELECT booking_date, package FROM bookings ORDER BY booking_date ASC";
$result = $conn->query($sql);

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Booked Calendar</title>
  <style>
    body {
      font-family: Arial;
      padding: 20px;
    }
    h2 {
      color: #009039;
    }
    ul {
      list-style: none;
      padding: 0;
    }
    li {
      background: #f9f9f9;
      border: 1px solid #ddd;
      margin-bottom: 5px;
      padding: 10px;
      border-left: 5px solid #38f9d7;
    }
  </style>
</head>
<body>
  <h2>📅 Booked Dates</h2>
  <?php if (count($bookings) > 0): ?>
    <ul>
      <?php foreach ($bookings as $booking): ?>
        <li><strong><?= $booking['booking_date'] ?></strong> – <?= $booking['package'] ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No bookings yet.</p>
  <?php endif; ?>
</body>
</html>
