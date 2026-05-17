<?php
// FILE: notifications_helper.php

function create_notification($conn, $type, $message, $link = '#') {
    $safe_type = htmlspecialchars($type);
    $safe_message = htmlspecialchars($message);
    $safe_link = htmlspecialchars($link);

    $query = "
        INSERT INTO notifications (type, message, link)
        VALUES (?, ?, ?)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $safe_type, $safe_message, $safe_link);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}
?>