<?php
// FILE: verify_admin.php

// include 'db_config.php'; // ⬅️ Assuming this line establishes $auth_conn and starts session (or you update it)
// session_start(); // REMOVED: Session is now handled by the included config file.

// Since 'db_config.php' is relative to the current folder, and we assume it's meant to be the central config:
require '../../db_config.php'; // ⬅️ Using the central config

// Assuming the central config provides $conn for aleinahs_resort. 
// If it needs the 'booking_system' connection, you must manually connect or get the secondary connection here.

// If 'db_config.php' provides $conn, we use it, otherwise, you need to confirm your intended connection:
$auth_conn = $conn; 


$password = $_POST['password'] ?? '';

// Use $auth_conn for the authentication check
$stmt = $auth_conn->prepare("SELECT fullname, password FROM users WHERE role='admin' AND status='active'");
$stmt->execute();
$stmt->bind_result($fullname, $hash);

$found = false;
$name = '';
while ($stmt->fetch()) {
    if (password_verify($password, $hash)) {
        // Set the approved_by name in the session for use in add/edit PHP files
        $_SESSION['approved_by_budget'] = $fullname; 
        $name = $fullname;
        $found = true;
        break;
    }
}
$stmt->close();
// Note: $auth_conn->close(); should be executed when the connection object is no longer needed. 

if ($found) {
    echo json_encode(['success' => true, 'fullname' => $name]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid admin password or not authorized.']);
}
?>