<?php
session_start();

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = getenv('DB_HOST') ?: "localhost";
$db_username = getenv('DB_USER') ?: "u205310066_admin";
$db_password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "u205310066_aleinahsresort";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sanitize input
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header("Location: index.php?error=empty");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT userid, fullname, username, password, role, email, is_locked FROM users WHERE username = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    header("Location: index.php?error=nouser");
    exit();
}

$stmt->bind_result($userid, $fullname, $db_username_result, $db_password_hash, $role, $email, $is_locked);
$stmt->fetch();
$stmt->close(); // Close the statement as soon as you're done

if ($is_locked) {
    header("Location: index.php?error=locked");
    exit();
}

// Password check
if (!password_verify($password, $db_password_hash)) {
    header("Location: index.php?error=wrongpass");
    exit();
}

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Store OTP and reset attempts with a prepared statement
$update = $conn->prepare("UPDATE users SET otp_code = ?, otp_attempts = 0 WHERE userid = ?");
if (!$update) {
    die("Update prepare failed: " . $conn->error);
}
$update->bind_param("si", $otp, $userid);
$update->execute();
$update->close();

// Send OTP via email
$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com'; 
$mail->SMTPAuth = true;
$mail->Username = 'aleinahsprivateresort@gmail.com'; 
$mail->Password = 'mgwrfjsjfubnkeuv'; 
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('aleinahsprivateresort@gmail.com', 'Aleinah Resort');
$mail->addAddress($email, $fullname);
$mail->Subject = 'Your OTP Code';
$mail->isHTML(true);
$mail->Body = "
    <div style='font-family:Segoe UI,Arial,sans-serif;max-width:400px;margin:auto;padding:24px;background:#f7fafd;border-radius:10px;box-shadow:0 2px 8px rgba(31,38,135,0.08);'>
        <h2 style='color:#155A78;margin-top:0;'>Aleinah's Private Resort</h2>
        <p style='font-size:1.1rem;color:#222;'>Hello <strong>$fullname</strong>,</p>
        <p style='font-size:1.1rem;color:#222;'>Your one-time password (OTP) is:</p>
        <div style='font-size:2rem;font-weight:bold;letter-spacing:6px;color:#155A78;background:#fff;padding:16px 0;margin:18px 0 24px 0;text-align:center;border-radius:8px;border:1px solid #e0e0e0;'>$otp</div>
        <p style='font-size:1rem;color:#444;'>Enter this code to complete your login.<br>
        This code will expire soon for your security.</p>
        <p style='font-size:0.95rem;color:#888;margin-top:32px;'>If you did not request this, please ignore this email.</p>
        <p style='font-size:1rem;color:#155A78;margin-top:32px;'>Thank you,<br>Aleinah's Private Resort Team</p>
    </div>
";

if (!$mail->send()) {
    // It's better not to display this to the user for security reasons. Log it instead.
    error_log("Mailer Error: " . $mail->ErrorInfo);
    // You could redirect to a general error page
    // header("Location: index.php?error=emailfail"); 
    // For this example, we'll continue, assuming the DB update worked.
}

// Store user session and redirect to OTP page
$_SESSION['userid'] = $userid;
$_SESSION['fullname'] = $fullname;
$_SESSION['role'] = $role;
$_SESSION['otp_verified'] = false; // Add this line if not already present

header("Location: otp.php");
exit();