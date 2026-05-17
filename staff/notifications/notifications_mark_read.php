<?php
// FILE: notifications_mark_read.php
// PURPOSE: Updates the is_read status of notifications in the database.
require '../../db_config.php'; 

// CRITICAL: Expects an ID or 'all' action via POST from notifications.js
$id = $_POST['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$success = false;
$conn_update = null;

try {
    // Re-establish connection or use $conn if safe. Re-establishing here for isolated action:
    $conn_update = new mysqli("localhost", "root", "", "aleinahs_resort"); // NOTE: Use your db_config variables here!

    if ($id === 'all') {
        // Mark all notifications as read
        $stmt = $conn_update->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    } else {
        // Mark a single notification as read
        $stmt = $conn_update->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
    }
    
    if ($stmt->execute()) {
        $success = true;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Failed to mark notifications read: " . $e->getMessage());
}

if ($conn_update) {
    $conn_update->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
?>