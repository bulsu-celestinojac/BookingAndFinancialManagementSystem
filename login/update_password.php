<?php
session_start();

$conn = new mysqli("localhost", "u205310066_admin", "Aleinahsprivate00.", "u205310066_aleinahsresort");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get token and new password from POST
$token = trim($_POST['token'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if (empty($token) || empty($password) || $password !== $confirm_password) {
    // Redirect if inputs are invalid
    header("Location: forgot_password.php?status=invalid_token");
    exit();
}

// Re-validate the token and check for expiration
$stmt = $conn->prepare("SELECT userid, token_expiry FROM users WHERE password_reset_token = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($userid, $token_expiry);
$stmt->fetch();

if ($stmt->num_rows === 0 || strtotime($token_expiry) < time()) {
    header("Location: forgot_password.php?status=invalid_token");
    exit();
}
$stmt->close();

// Hash the new password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update the password and clear the token from the database
$update_stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, token_expiry = NULL WHERE userid = ?");
if (!$update_stmt) { die("Update prepare failed: " . $conn->error); }
$update_stmt->bind_param("si", $hashed_password, $userid);
$update_stmt->execute();
$update_stmt->close();

// Redirect to login page with a success message
header("Location: forgot_password.php?status=updated");
exit();
