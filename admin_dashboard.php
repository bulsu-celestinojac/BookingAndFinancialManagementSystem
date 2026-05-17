<?php

// PayMongo Secret Key
$sk = "REDACTED";

// --- Part 1: Handle synchronization status message ---
$sync_status = $_GET['sync_status'] ?? null;
$synced_count = $_GET['count'] ?? 0;
$status_message = "";

if ($sync_status === 'success') {
    $status_message = "Successfully synchronized " . $synced_count . " payments.";
}

// --- Part 2: Fetch and display data directly from PayMongo ---
$paymongoPayments = [];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/payments?limit=20");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode($sk . ":")
]);

$response = curl_exec($ch);
curl_close($ch);

$paymentsData = json_decode($response, true);

if (isset($paymentsData['data'])) {
    foreach ($paymentsData['data'] as $payment) {
        $attributes = $payment['attributes'];
        $paymongoPayments[] = [
            'id' => $payment['id'],
            'amount' => $attributes['amount'] / 100, // PayMongo amount is in cents
            'email' => $attributes['billing']['email'] ?? 'N/A',
            'name' => $attributes['billing']['name'] ?? 'N/A',
            'status' => $attributes['status'],
            'payment_method' => $attributes['source']['type'] ?? 'N/A',
            'created_at' => date('Y-m-d H:i:s', $attributes['created_at'])
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto space-y-8">
        <h1 class="text-3xl font-bold text-center mb-8">Admin Dashboard</h1>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">PayMongo Payments</h2>
            <a href="sync_payments.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
                Synchronize Paid Payments
            </a>
        </div>
        
        <?php if ($status_message): ?>
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-center font-semibold mb-6">
                <?= htmlspecialchars($status_message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <?php if (count($paymongoPayments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($paymongoPayments as $payment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($payment['id']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($payment['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($payment['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?= number_format(floatval($payment['amount']), 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($payment['payment_method']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            $status = $payment['status'];
                                            if ($status === 'paid') {
                                                echo 'bg-green-100 text-green-800';
                                            } elseif ($status === 'pending' || $status === 'awaiting_payment_method') {
                                                echo 'bg-yellow-100 text-yellow-800';
                                            } else {
                                                echo 'bg-red-100 text-red-800';
                                            }
                                            ?>">
                                            <?= htmlspecialchars(strtoupper($status ?? 'N/A')) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($payment['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center">No payment records found from PayMongo.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>