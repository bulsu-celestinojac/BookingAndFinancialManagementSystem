<?php
// File: sync_paymongo.php
// PURPOSE: Fetches payments from PayMongo API (via fetch_paymongo.php) and inserts them into the local Income table.

// CRITICAL SECURITY: Include the database config from the project root (two levels up)
require_once '../../db_config.php'; 
// CRITICAL: Include the notification trigger function (adjust path if needed)
require_once '../notifications/notification_trigger.php'; 

// 1. Fetch data securely using the Secret Key
// NOTE: fetch_paymongo.php returns the $paymentsToSync array directly.
$paymentsToSync = require_once 'fetch_paymongo.php'; 

$syncedCount = 0;
$conn->begin_transaction(); // Start transaction for safety

if (!empty($paymentsToSync) && is_array($paymentsToSync)) {
    
    // --- Pre-check existing PayMongo IDs in the income table ---
    // You must have a column named `paymongo_id` in your `income` table for this check.
    $existingIds = [];
    $checkStmt = $conn->query("SELECT paymongo_id FROM income WHERE paymongo_id IS NOT NULL");
    if ($checkStmt) {
        while ($row = $checkStmt->fetch_assoc()) {
            $existingIds[$row['paymongo_id']] = true;
        }
        $checkStmt->close();
    }
    
    // 2. Prepare the insertion statement for the local 'income' table
    // NOTE: This insert query assumes you have a `paymongo_id` column in your `income` table.
    $stmt = $conn->prepare("INSERT INTO income (date, source, amount, description, payment_method, paymongo_id) 
                            VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($paymentsToSync as $payment) {
        
        // 3. CRITICAL: SKIP if the ID already exists
        if (isset($existingIds[$payment['id']])) {
            continue; 
        }

        // Data mapping and cleanup
        $date = date('Y-m-d', strtotime($payment['created_at']));
        $source = 'PayMongo Payment - ' . ($payment['name'] ?? 'Guest');
        $amount = $payment['amount'];
        $description = 'Transaction ID: ' . $payment['id'];
        $paymentMethod = $payment['payment_method'];
        $paymongoId = $payment['id']; // PayMongo ID for duplicate checking

        // Execute insertion only for "paid" status
        if ($payment['status'] === 'paid') {
            // "ssdsss": string, string, double/float, string, string, string (for paymongo_id)
            $stmt->bind_param("ssdsss", $date, $source, $amount, $description, $paymentMethod, $paymongoId); 
            
            if ($stmt->execute()) {
                $syncedCount++;

                // --- CRITICAL: FIRE NOTIFICATION FOR NEW PAYMENT ---
                $type = "Payment Received";
                $message = "New Income: " . number_format($amount, 2) . " received via " . strtoupper($paymentMethod) . ".";
                // CRITICAL: Use Absolute Path for safety (assuming admin is the next level down)
                $link = "/caps/flesvw2/admin/income/index.php"; 
                fireNotification($conn, $type, $message, $link);
                // ---------------------------------------------------
            }
        }
    }
    $stmt->close();
}

$conn->commit(); // Commit transaction on success
$conn->close();

// Redirect back to the main page with a status message
header("Location: index.php?sync_status=success&count=" . $syncedCount);
exit();
?>