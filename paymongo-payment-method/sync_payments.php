<?php
require_once 'db.php';

// SECURITY BEST PRACTICE: API keys are loaded via environment variables in production.
// Hardcoded key removed for public repository visibility.
$sk = getenv('PAYMONGO_SECRET_KEY') ?: "SECURE_KEY_HIDDEN_FOR_PORTFOLIO";

// Set the URL to fetch payments. We filter for 'paid' payments.
$url = "https://api.paymongo.com/v1/payments?status=paid";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode($sk . ":"),
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$apiData = json_decode($response, true);
$syncedCount = 0;

if (isset($apiData['data']) && count($apiData['data']) > 0) {
    // Prepare the SQL statement to insert or update records
    $stmt = $conn->prepare("
        INSERT INTO payments (checkout_session_id, status, amount, payment_method, name, email, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        amount = VALUES(amount),
        payment_method = VALUES(payment_method),
        name = VALUES(name),
        email = VALUES(email),
        created_at = VALUES(created_at)
    ");

    if ($stmt) {
        foreach ($apiData['data'] as $payment) {
            $attributes = $payment['attributes'];
            
            // Extract necessary data from the API response
            $checkoutSessionId = $attributes['checkout_session_id'] ?? $payment['id'];
            $status = $attributes['status'] ?? null;
            $amount = floatval($attributes['amount'] ?? 0) / 100;
            $paymentMethod = $attributes['payments'][0]['attributes']['source']['type'] ?? 'N/A';
            $name = $attributes['billing']['name'] ?? 'N/A';
            $email = $attributes['billing']['email'] ?? 'N/A';
            $createdAt = date('Y-m-d H:i:s', $attributes['created_at'] ?? time());
            
            $stmt->bind_param("sssdsss", $checkoutSessionId, $status, $amount, $paymentMethod, $name, $email, $createdAt);
            $stmt->execute();
            $syncedCount++;
        }
        $stmt->close();
    }
}
$conn->close();

// Redirect back to the dashboard with a success message
header("Location: admin_dashboard.php?sync_status=success&count=" . $syncedCount);
exit;