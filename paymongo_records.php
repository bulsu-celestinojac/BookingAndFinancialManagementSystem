<?php
// Your PayMongo test secret key
$secretKey = "REDACTED";

// Fetch all payments via PayMongo API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/payments");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode($secretKey . ":"),
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Handle API errors
if (!isset($data['data'])) {
    echo "❌ Failed to fetch payment data from PayMongo.";
    exit;
}

// Display header
echo "<h2 style='font-family:sans-serif;'>Confirmed Bookings (PayMongo Test Mode)</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; font-family:sans-serif;'>";
echo "<tr style='background:#f0f0f0;'>
        <th>Status</th>
        <th>Amount (₱)</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Package</th>
        <th>Booking Date</th>
        <th>Paid At</th>
      </tr>";

// Loop through records
foreach ($data['data'] as $payment) {
    $attr = $payment['attributes'];

    // Skip anything not "paid"
    if ($attr['status'] !== 'paid') continue;

    $meta = $attr['metadata'] ?? [];

    // Skip if it's not from your booking form
    if (empty($meta['booking_date']) || empty($meta['package'])) continue;

    // Extract metadata
    $amount = number_format($attr['amount'] / 100, 2);
    $name = htmlspecialchars($meta['customer_name'] ?? 'Unknown');
    $email = htmlspecialchars($meta['customer_email'] ?? 'N/A');
    $package = htmlspecialchars($meta['package']);
    $dateBooked = htmlspecialchars($meta['booking_date']);
    $status = ucfirst($attr['status']);
    $paidAt = $attr['paid_at'] ? date("Y-m-d H:i", strtotime($attr['paid_at'])) : '—';

    echo "<tr>
            <td>$status</td>
            <td>₱$amount</td>
            <td>$name</td>
            <td>$email</td>
            <td>$package</td>
            <td>$dateBooked</td>
            <td>$paidAt</td>
          </tr>";
}

echo "</table>";
?>
