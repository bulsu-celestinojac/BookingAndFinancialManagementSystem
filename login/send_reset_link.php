<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection details
$servername = "localhost";
$db_username = "u205310066_admin";
$db_password = "Aleinahsprivate00.";
$dbname = "u205310066_aleinahsresort";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get and sanitize email from POST
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    // Redirect back if no email is provided
    header("Location: forgot_password.php?status=error");
    exit();
}

// Check if a user with this email exists
$stmt = $conn->prepare("SELECT userid FROM users WHERE email = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Generate a unique token
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", time() + 3600); // Token expires in 1 hour

    // Update the user's record with the new token and expiry
    $update_stmt = $conn->prepare("UPDATE users SET password_reset_token = ?, token_expiry = ? WHERE email = ?");
    if (!$update_stmt) { die("Update prepare failed: " . $conn->error); }
    $update_stmt->bind_param("sss", $token, $expiry, $email);
    $update_stmt->execute();
    $update_stmt->close();

    // Send email with the reset link
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; 
    $mail->SMTPAuth = true;
    $mail->Username = 'aleinahsprivateresort@gmail.com'; 
    $mail->Password = 'mgwrfjsjfubnkeuv'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('aleinahsprivateresort@gmail.com', 'Aleinah Resort');
    $mail->addAddress($email);
    $mail->Subject = 'Password Reset Request';
    $mail->isHTML(true);
    
    // Construct the reset link dynamically using the current host and a relative path
    $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $reset_link = str_replace("send_reset_link.php", "reset_password.php", $reset_link);
    $reset_link .= "?token=" . urlencode($token);
    
    $mail->Body = "
        <div style='font-family:Segoe UI,Arial,sans-serif;max-width:400px;margin:auto;padding:24px;background:#f7fafd;border-radius:10px;box-shadow:0 2px 8px rgba(31,38,135,0.08);'>
            <h2 style='color:#155A78;margin-top:0;'>Aleinah's Private Resort</h2>
            <p style='font-size:1.1rem;color:#222;'>Hello,</p>
            <p style='font-size:1.1rem;color:#222;'>A password reset was requested for your account. Click the link below to reset your password:</p>
            <div style='text-align:center;margin:18px 0 24px 0;'>
                <a href='$reset_link' style='display:inline-block;padding:12px 24px;background-color:#155A78;color:#fff;text-decoration:none;font-weight:bold;border-radius:8px;'>Reset Password</a>
            </div>
            <p style='font-size:1rem;color:#444;'>If you did not request this, please ignore this email.<br>This link will expire in one hour.</p>
            <p style='font-size:0.95rem;color:#888;margin-top:32px;'>Note: If you have trouble clicking the link, copy and paste it into your browser:</p>
            <pre style='font-size:0.9rem;word-wrap:break-word;word-break:break-all;color:#444;background:#fff;padding:8px;border:1px solid #e0e0e0;border-radius:4px;'>$reset_link</pre>
            <p style='font-size:1rem;color:#155A78;margin-top:32px;'>Thank you,<br>Aleinah's Private Resort Team</p>
        </div>
    ";
    
    $mail->send();
}

// Always redirect to the same page with a generic success message
// This prevents a user from knowing whether an email exists in the system
header("Location: forgot_password.php?status=sent");
exit();
    