<?php
// FILE: notification_trigger.php

/**
 * Inserts a new notification record into the database.
 * @param mysqli $conn The active database connection.
 * @param string $type The type of alert (e.g., 'Expense Added', 'Pending Approval').
 * @param string $message The descriptive message for the user.
 * @param string $link The URL to navigate to for resolution.
 */
function fireNotification($conn, $type, $message, $link) {
    if ($conn->connect_error) {
        error_log("Notification system failed: DB connection error.");
        return false;
    }
    
    $is_read = 0;
    $stmt = $conn->prepare("INSERT INTO notifications (type, message, link, is_read, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $type, $message, $link, $is_read);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Failed to insert notification: " . $conn->error);
        $stmt->close();
        return false;
    }
}
?>