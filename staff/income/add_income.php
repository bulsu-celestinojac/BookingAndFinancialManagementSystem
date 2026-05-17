<?php
// FILE: add_income.php

session_start();

// --- SECURITY LOGIC ---
if (!isset($_SESSION['userid']) || !isset($_SESSION['role'])) {
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

require_once '../../db_config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $userId = $_SESSION['userid']; // Get the user ID from the session
    $date = $_POST['date'];
    $source = $_POST['source']; // Changed from category to source
    $amount = $_POST['amount'];
    $description = $_POST['description'] ?? '';
    $paymentMethod = $_POST['payment_method'];
    $enteredBy = $_SESSION['username'];
    
    // Incomes may not have a proof file, so you can adapt this part
    $proofFilePath = null; // Assuming no file upload for income for now.
    
    // Prepare and execute the SQL query to insert a new PENDING income record
    $sql = "INSERT INTO incomes (user_id, date, source, amount, description, payment_method, entered_by, approval_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header("Location: index.php?error=" . urlencode("SQL prepare error: " . $conn->error));
        exit();
    }
    
    $approvalStatus = 'pending';
    $stmt->bind_param("isdsssss", $userId, $date, $source, $amount, $description, $paymentMethod, $enteredBy, $approvalStatus);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=added");
        exit();
    } else {
        header("Location: index.php?error=" . urlencode("Database error: " . $stmt->error));
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    // If not a POST request, redirect back to the form
    header("Location: index.php");
    exit();
}
?>