<?php
include 'dbconfig.php'; // Database connection

// Get raw payload from PayMongo
$rawPayload = file_get_contents("php://input");
file_put_contents("webhook_log.txt", "Received webhook payload: " . $rawPayload . PHP_EOL, FILE_APPEND);

// Decode JSON
$data = json_decode($rawPayload, true);

if (!$data) {
    http_response_code(400);
    file_put_contents("webhook_log.txt", "Invalid payload received." . PHP_EOL, FILE_APPEND);
    exit("Invalid payload");
}

// ✅ Process only successful payments
if (isset($data['data']['attributes']['type']) && $data['data']['attributes']['type'] === 'checkout_session.payment.paid') {
    // Get the unique checkout session ID from the webhook payload
    $checkoutSessionId = $data['data']['attributes']['data']['id'];

    // Prepare an UPDATE statement
    $stmt = $conn->prepare("UPDATE payments SET status = 'paid' WHERE checkout_session_id = ?");
    $stmt->bind_param("s", $checkoutSessionId);

    if ($stmt->execute()) {
        file_put_contents("webhook_log.txt", "Successfully updated checkout session ID: $checkoutSessionId to paid." . PHP_EOL, FILE_APPEND);
    } else {
        file_put_contents("webhook_log.txt", "DB Update Failed for checkout session ID $checkoutSessionId: {$stmt->error}" . PHP_EOL, FILE_APPEND);
    }
    $stmt->close();
}

http_response_code(200);
echo json_encode(['success' => true]);
?>