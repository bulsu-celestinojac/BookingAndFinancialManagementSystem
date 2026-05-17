<?php
// FILE: notifications_fetch.php
// PURPOSE: Fetches the count and list of UNREAD notifications for the badge/bell.
require '../../db_config.php'; // Access centralized database config

$notifications = [];

// Query all UNREAD notifications, ordered by newest first
$query = "
    SELECT 
        id, type, message, link, created_at
    FROM notifications
    WHERE is_read = 0 
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($query);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => htmlspecialchars($row['type']),
            'message' => htmlspecialchars($row['message']),
            'link' => htmlspecialchars($row['link']),
            'time' => date('M j, g:i A', strtotime($row['created_at']))
        ];
    }
}
$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'count' => count($notifications),
    'notifications' => $notifications // Note: List is included even if JS only uses count
]);
?>