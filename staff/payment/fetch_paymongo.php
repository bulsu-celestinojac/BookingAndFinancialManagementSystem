<?php
// File: fetch_paymongo.php
// PURPOSE: Securely fetches a list of payments from the PayMongo API.

// SECURITY BEST PRACTICE: API keys are loaded via environment variables in production.
// Hardcoded key removed for public repository visibility.
define('PAYMONGO_SECRET_KEY', getenv('PAYMONGO_SECRET_KEY') ?: "SECURE_KEY_HIDDEN_FOR_PORTFOLIO"); 

// The PayMongo API endpoint for listing payments
$url = 'https://api.paymongo.com/v1/payments?limit=100&status=paid'; // Fetch up to 100 paid payments

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of printing it
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    // Authentication using Basic Auth (API Key is the username, password is empty)
    'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
    'Accept: application/json',
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    // Log the error and return an empty array or throw an exception
    error_log("PayMongo API Error (HTTP $http_code): " . $response);
    return []; 
}

$data = json_decode($response, true);

// Extract and reformat the payments data
$paymentsToSync = [];

if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $payment) {
        $attributes = $payment['attributes'];
        
        // Data mapping from PayMongo API structure to your desired structure for sync_paymongo.php
        $paymentsToSync[] = [
            'id'             => $payment['id'],
            'status'         => $attributes['status'],
            'amount'         => $attributes['amount'] / 100, // PayMongo amount is in centavos/smallest unit, divide by 100
            'payment_method' => $attributes['source']['type'] ?? ($attributes['payments'][0]['attributes']['source']['type'] ?? 'unknown'),
            'created_at'     => $attributes['paid_at'] ? date('Y-m-d H:i:s', $attributes['paid_at']) : date('Y-m-d H:i:s', $attributes['created_at']),
            'name'           => $attributes['billing']['name'] ?? 'Guest',
        ];
    }
}

// NOTE: This line returns the array to the including script (sync_paymongo.php)
return $paymentsToSync;