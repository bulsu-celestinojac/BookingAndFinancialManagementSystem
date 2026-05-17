<?php
// FILE: unlock_account.php

// CRITICAL SECURITY: Protect this page with session checks!
// Assuming db_config.php handles session_start() safely:
require 'db_config.php'; 

// CRITICAL SECURITY CHECK (Ensure only Admins can access this page)
// Adjust the path to redirect to the login index if session is invalid
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') { 
    header("Location: index.php?error=unauthorized"); 
    exit();
}

$conn = new mysqli("localhost", "u205310066_admin", "@7BF100Rl", "u205310066_aleinahsresort");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$status = ''; // 'success', 'error'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);

    // Check if the user exists first (optional, but better UX)
    $check_stmt = $conn->prepare("SELECT userid FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    $user_exists = $check_stmt->num_rows > 0;
    $check_stmt->close();
    
    if (!$user_exists) {
        $status = 'error';
        $message = "User '{$username}' not found in the system.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_locked = 0, otp_attempts = 0 WHERE username = ?");
        $stmt->bind_param("s", $username);

        if ($stmt->execute()) {
            $status = 'success';
            $message = "Account for '{$username}' has been successfully unlocked and OTP attempts reset.";
        } else {
            $status = 'error';
            $message = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Unlock User | Aleinah's Resort</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #f59e42; /* A warning orange color */
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-primary { background-color: var(--color-primary); }
        .text-primary { color: var(--color-primary); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans p-4">

    <div class="bg-white p-6 sm:p-8 max-w-md w-full rounded-xl shadow-2xl text-center">
        <div class="mb-6">
            <i class="fas fa-unlock-alt text-5xl text-primary"></i>
        </div>
        <h2 class="text-2xl font-bold mb-2 text-dark-text">Unlock User Account</h2>
        <p class="text-light-text mb-6 text-sm">Reset OTP attempts and unlock accounts blocked by security.</p>

        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-lg text-sm text-center 
                <?= $status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <i class="fas <?= $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="username" class="block mb-1 font-semibold text-light-text text-left">Username to Unlock</label>
                <input type="text" name="username" id="username" required 
                       class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-primary" 
                       placeholder="Enter username (e.g., jdelacruz)">
            </div>
            
            <button type="submit" class="w-full bg-primary text-white font-bold py-3 px-4 rounded-xl hover:opacity-90 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-primary">
                <i class="fas fa-key mr-2"></i> Unlock Account & Reset OTP
            </button>
        </form>
        
        <div class="mt-6 text-sm text-gray-500">
            <a href="login/index.php" class="text-blue-600 hover:underline">← Back to Login</a>
        </div>
    </div>
</body>
</html>