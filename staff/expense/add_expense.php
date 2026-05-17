<?php
// FILE: add_expense.php

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
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'] ?? '';
    $paidBy = $_POST['paid_by'];
    $enteredBy = $_SESSION['username'];
    
    // File upload handling
    $proofFilePath = null;
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '-' . basename($_FILES['proof_file']['name']);
        $destination = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $destination)) {
            $proofFilePath = $destination;
        } else {
            header("Location: index.php?error=" . urlencode("Failed to upload file."));
            exit();
        }
    }

    // Prepare and execute the SQL query to insert a new PENDING expense record
    $sql = "INSERT INTO expenses (user_id, date, category, amount, description, paid_by, entered_by, proof_file, approval_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header("Location: index.php?error=" . urlencode("SQL prepare error: " . $conn->error));
        exit();
    }
    
    $approvalStatus = 'pending';
    $stmt->bind_param("isdssssss", $userId, $date, $category, $amount, $description, $paidBy, $enteredBy, $proofFilePath, $approvalStatus);
    
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