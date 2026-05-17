<?php
// FILE: process_approval.php

session_start();

// --- SECURITY LOGIC ---
// Check for authentication and role
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

// Check for POST request and required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'], $_POST['action'], $_POST['record_type'])) {
    header("Location: index.php?status=error&msg=" . urlencode("Invalid request."));
    exit();
}

// --- DATABASE CONNECTION ---
require '../../db_config.php';

// --- SANITIZE INPUT ---
$recordId = intval($_POST['id']);
$action = $_POST['action'];
$recordType = $_POST['record_type'];

// Map the record type to the correct table name
$tableName = '';
$recordTypeLower = strtolower($recordType);

if ($recordTypeLower === 'expense') {
    $tableName = 'expenses';
} elseif ($recordTypeLower === 'income') {
    $tableName = 'income';
} else {
    // Handle an invalid record type
    header("Location: index.php?status=error&msg=" . urlencode("Invalid record type."));
    exit();
}

// --- PREPARE THE STATEMENT ---
// Using prepared statements to prevent SQL injection
$sql = "UPDATE $tableName SET approval_status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($action === 'approve') {
    $newStatus = 'approved';
    $redirectMsg = "Record approved successfully!";
} elseif ($action === 'decline' || $action === 'reject') {
    $newStatus = 'declined';
    $redirectMsg = "Record declined successfully!";
} else {
    header("Location: index.php?status=error&msg=" . urlencode("Invalid action."));
    exit();
}

$stmt->bind_param("si", $newStatus, $recordId);

// --- EXECUTE THE UPDATE ---
if ($stmt->execute()) {
    // The notification logic has been removed because your tables do not have a user_id column.
    // There is no way to know which user submitted the record to send them a notification.

    // Redirect with success message
    header("Location: index.php?status=success&msg=" . urlencode($redirectMsg));
} else {
    // Redirect with error message
    header("Location: index.php?status=error&msg=" . urlencode("Database error: " . $stmt->error));
}

$stmt->close();
$conn->close();
exit();
?>