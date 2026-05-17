<?php
// callback.php

// Read raw webhook JSON
$input = file_get_contents("php://input");
$event = json_decode($input, true);

// Log incoming webhooks for debugging (optional)
file_put_contents("webhook.log", date('Y-m-d H:i:s') . " " . $input . "\n", FILE_APPEND);

// Check payment status
$status = $event['data']['attributes']['status'] ?? '';

if (in_array($status, ['paid', 'authorized'])) {
    // Extract ID and metadata
    $paymentId = $event['data']['id'] ?? '';
    $meta = $event['data']['attributes']['metadata'] ?? [];

    $package      = $meta['package']       ?? '';
    $booking_date = $meta['booking_date']  ?? '';
    $customerName = $meta['customer_name'] ?? '';
    $customerEmail= $meta['customer_email']?? '';

    if ($booking_date && $package) {
        // Connect to your DB
        $conn = new mysqli("localhost", "root", "", "booking_system");
        if ($conn->connect_error) {
            error_log("DB conn failed: " . $conn->connect_error);
            http_response_code(500);
            exit;
        }

        // Prevent double booking for the same date
        $check = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
        $check->bind_param("s", $booking_date);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count == 0) {
            // Insert new booking
            $stmt = $conn->prepare("
                INSERT INTO bookings 
                (payment_id, package, booking_date, customer_name, customer_email) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $paymentId, $package, $booking_date, $customerName, $customerEmail);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
    }
}

// Always acknowledge receipt of webhook
http_response_code(200);
?>
<?php
// callback.php

// Read raw webhook JSON
$input = file_get_contents("php://input");
$event = json_decode($input, true);

// Log incoming webhooks for debugging (optional)
file_put_contents("webhook.log", date('Y-m-d H:i:s') . " " . $input . "\n", FILE_APPEND);

// Check payment status
$status = $event['data']['attributes']['status'] ?? '';

if (in_array($status, ['paid', 'authorized'])) {
    // Extract ID and metadata
    $paymentId = $event['data']['id'] ?? '';
    $meta = $event['data']['attributes']['metadata'] ?? [];

    $package      = $meta['package']       ?? '';
    $booking_date = $meta['booking_date']  ?? '';
    $customerName = $meta['customer_name'] ?? '';
    $customerEmail= $meta['customer_email']?? '';

    if ($booking_date && $package) {
        // Connect to your DB
        $conn = new mysqli("localhost", "root", "", "booking_system");
        if ($conn->connect_error) {
            error_log("DB conn failed: " . $conn->connect_error);
            http_response_code(500);
            exit;
        }

        // Prevent double booking for the same date
        $check = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
        $check->bind_param("s", $booking_date);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count == 0) {
            // Insert new booking
            $stmt = $conn->prepare("
                INSERT INTO bookings 
                (payment_id, package, booking_date, customer_name, customer_email) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $paymentId, $package, $booking_date, $customerName, $customerEmail);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
    }
}

// Always acknowledge receipt of webhook
http_response_code(200);
?>
