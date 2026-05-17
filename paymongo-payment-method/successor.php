<?php
// This script verifies payment status and inserts the record into the database.
// This ensures that only successfully paid transactions are saved.

session_start();
include 'dbconfig.php';

if (!isset($_GET['session_id'])) {
    die("Error: Missing checkout session ID.");
}

$checkoutSessionId = $_GET['session_id'];

$bookingDetails = $_SESSION['booking_details'] ?? null;
if (!$bookingDetails) {
    die("Error: Booking details not found in session.");
}

// Call PayMongo API to verify payment status
$secretKey ="YOUR_PAYMONGO_KEY_HERE";

$ch = curl_init("https://api.paymongo.com/v1/checkout_sessions/" . $checkoutSessionId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode($secretKey . ":")
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

$isPaid = isset($result['data']['attributes']['payment_intent']['attributes']['status']) 
          && $result['data']['attributes']['payment_intent']['attributes']['status'] === 'paid';

if ($isPaid) {
    // Payment is authorized, now save to database
    $stmt = $conn->prepare("INSERT INTO payments 
        (name, email, phone, booking_date, package, amount, status, checkout_session_id, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, ?)");
    $stmt->bind_param(
        "sssssdss", 
        $bookingDetails['name'], 
        $bookingDetails['email'], 
        $bookingDetails['phone'], 
        $bookingDetails['booking_date'], 
        $bookingDetails['package'], 
        $bookingDetails['price'], 
        $checkoutSessionId, 
        $bookingDetails['payment_method']
    );

    if ($stmt->execute()) {
        $message = "Your payment was successful and your booking has been confirmed.";
        $icon = '<svg class="w-20 h-20 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    } else {
        $message = "Payment successful, but we could not save your booking. Please contact us with your session ID: " . $checkoutSessionId;
        $icon = '<svg class="w-20 h-20 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
    }
} else {
    $message = "Your payment was not successful. Please try again or contact support.";
    $icon = '<svg class="w-20 h-20 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md text-center">
        <div class="flex flex-col items-center justify-center mb-6">
            <?php echo $icon; ?>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Payment Status</h1>
        <p class="text-gray-600 mb-6"><?php echo $message; ?></p>
        <a href="index.php" class="inline-block mt-8 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            Return to Homepage
        </a>
    </div>
</body>
</html>