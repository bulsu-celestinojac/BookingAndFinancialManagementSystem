<?php
include 'dbconfig.php';
header('Content-Type: application/json');

$bookedDates = [];
$sql = "SELECT booked_date FROM payments WHERE status = 'paid'";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Add debug output to verify dates
        error_log("Found booked date: " . $row['booked_date']);
        $bookedDates[] = $row['booked_date'];
    }
}

// Add debug output of final array
error_log("Final booked dates: " . print_r($bookedDates, true));

echo json_encode($bookedDates);
mysqli_close($conn);
?>