<?php
// FILE: add_income.php (Hypothetical Revised Content)
require '../../db_config.php'; 
// --- NEW: Include the notification trigger utility ---
require '../notifications/notification_trigger.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'] ?? '';
    $source = $_POST['source'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    // Assuming prepared statement logic here for insertion...
    $stmt = $conn->prepare("INSERT INTO income (date, source, amount, description, payment_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $date, $source, $amount, $description, $payment_method); 

    if ($stmt->execute()) {
        $stmt->close();
        
        // --- CRITICAL NOTIFICATION CALL ---
        $message = "Income of ₱" . number_format($amount, 2) . " received from " . htmlspecialchars($source) . ".";
        
        // Trigger the notification function
        fireNotification($conn, 
            "Income Added", 
            $message, 
            "../income/index.php"
        );
        // ------------------------------------

        echo "<script>alert('Income added successfully!');window.location.href='index.php';</script>";
        exit();
    } else {
        $stmt->close();
        echo "<script>alert('Error adding income: " . htmlspecialchars($conn->error) . "'); window.location.href='index.php';</script>";
    }
}
?>