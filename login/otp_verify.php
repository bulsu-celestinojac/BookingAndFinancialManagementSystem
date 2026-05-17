<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php"); // Redirect to login if session is lost
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = $_SESSION['userid'];
    $otp = trim($_POST['otp'] ?? '');

    $conn = new mysqli("localhost", "u205310066_admin", "@7BF100Rl", "u205310066_aleinahsresort");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // --- FETCH USER DETAILS AND OTP ---
    // Fetch otp_code, attempts, and importantly, the user's role
    $stmt = $conn->prepare("SELECT otp_code, otp_attempts, is_locked, fullname, role FROM users WHERE userid = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->bind_result($code, $attempts, $is_locked, $fullname, $role);
    $stmt->fetch();
    $stmt->close();
    
    // Check if the account is already locked
    if ($is_locked) {
        $conn->close();
        header("Location: otp.php?error=otplocked");
        exit();
    }

    if ($otp === $code) {
        // OTP is correct. Clean up OTP data in the database.
        $update_stmt = $conn->prepare("UPDATE users SET otp_code = NULL, otp_attempts = 0 WHERE userid = ?");
        if (!$update_stmt) { $conn->close(); die("Update prepare failed: " . $conn->error); }
        $update_stmt->bind_param("i", $userid);
        $update_stmt->execute();
        $update_stmt->close();
        $conn->close();

        // --- CRITICAL SESSION UPDATE ---
        $_SESSION['otp_verified'] = true;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['role'] = $role; // Ensure the role is set correctly after verification

        // --- ROLE-BASED REDIRECTION LOGIC ---
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard/index.php");
        } elseif ($_SESSION['role'] === 'staff') {
            header("Location: ../staff/income/index.php"); // Redirect staff to their dashboard
        } else {
            // Fallback for any other roles or unexpected values
            header("Location: ../../index.php?error=unknownrole");
        }
        exit();
    } else {
        // ... (your existing code for incorrect OTP attempts)
        $attempts++;
        if ($attempts >= 3) {
            // LOCK ACCOUNT
            $lock_stmt = $conn->prepare("UPDATE users SET is_locked = 1 WHERE userid = ?");
            if (!$lock_stmt) { $conn->close(); die("Lock prepare failed: " . $conn->error); }
            $lock_stmt->bind_param("i", $userid);
            $lock_stmt->execute();
            $lock_stmt->close();
            $conn->close();
            
            header("Location: otp.php?error=otplocked");
            exit();
        } else {
            // UPDATE ATTEMPTS
            $update_stmt = $conn->prepare("UPDATE users SET otp_attempts = ? WHERE userid = ?");
            if (!$update_stmt) { $conn->close(); die("Attempts update prepare failed: " . $conn->error); }
            $update_stmt->bind_param("ii", $attempts, $userid);
            $update_stmt->execute();
            $update_stmt->close();
            $conn->close();
            
            header("Location: otp.php?error=wrongotp");
            exit();
        }
    }
} else {
    // If a non-POST request comes to this file, redirect back to the OTP page.
    header("Location: otp.php");
    exit();
}
?>