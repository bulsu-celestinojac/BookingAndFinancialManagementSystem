<?php
include 'dbconfig.php'; // Database connection

// Get POST values safely
$name        = $_POST['name'] ?? '';
$email       = $_POST['email'] ?? '';
$phone       = $_POST['phone'] ?? '';
$package     = $_POST['package'] ?? '';
$booked_date = $_POST['booked_date'] ?? '';
$method      = $_POST['payment_method'] ?? '';
$final_price = $_POST['final_price'] ?? '';


if (!$name || !$email || !$phone || !$package || !$booked_date || !$method || !$final_price) {
    exit("All fields are required.");
}

$amount = floatval($final_price);
$sk = "HELLO_FROM_PORTFOLIO"; // Replace with your actual PayMongo Secret git checkout --orphan new_main

if ($method === 'gcash' || $method === 'paymaya') {
    $ch = curl_init("https://api.paymongo.com/v1/checkout_sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . base64_encode($sk . ":"),
        "Content-Type: application/json"
    ]);

    $payload = [
        "data" => [
            "attributes" => [
                "line_items" => [[
                    "name" => "Resort Package ₱" . number_format($amount, 2),
                    "amount" => intval($amount) * 100,
                    "currency" => "PHP",
                    "quantity" => 1
                ]],
                "payment_method_types" => [$method],
                "success_url" => "https://white-leopard-812103.hostingersite.com/paymongo-payment-method/success.php",
                "cancel_url" => "http://localhost/index.php",
                "metadata" => [
                    "name"        => $name,
                    "email"       => $email,
                    "phone"       => $phone,
                    "package"     => $package,
                    "booked_date" => $booked_date,
                    "method"      => $method,
                    "final_price" => $final_price
                ]
            ]
        ]
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['data']['attributes']['checkout_url'])) {
        $checkoutId = $data['data']['id'];

        // Corrected INSERT statement to include checkout_session_id
        $stmt = $conn->prepare("
            INSERT INTO payments (name, email, phone, package, booked_date, amount, payment_method, status, checkout_session_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        
        // sssssdss (string, string, string, string, string, double, string, string)
        $stmt->bind_param("sssssdss", $name, $email, $phone, $package, $booked_date, $amount, $method, $checkoutId);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $data['data']['attributes']['checkout_url']);
        exit;
    } else {
        echo "Error creating Checkout Session.";
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
        exit;
    }
}
?>