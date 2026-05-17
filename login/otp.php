<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php"); // Changed to index.php for better user flow
    exit();
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
$message = '';

switch ($error) {
    case 'wrongotp':
        $message = 'Incorrect OTP. Please try again.';
        break;
    case 'otplocked':
        $message = 'Account locked due to too many incorrect attempts.';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP | Aleinah's Private Resort</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #303F9F 0%, #1A237E 100%);
        }
        input[name="otp"] {
            letter-spacing: 12px;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-8 sm:p-12 lg:p-16 w-full max-w-md text-center">
        <h2 class="text-4xl font-bold text-gray-800 mb-2">Verify OTP</h2>
        <p class="text-gray-500 mb-8">Enter the 6-digit code sent to your email.</p>
        
        <?php if (!empty($message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form action="otp_verify.php" method="POST" class="space-y-6">
            <input type="text" name="otp" maxlength="6" pattern="\d{6}" required placeholder="______" autocomplete="off" 
                   class="w-full px-4 py-3 rounded-xl border border-gray-300 text-center font-mono text-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200" />
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-blue-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500">
                Verify
            </button>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="otp"]').focus();
        });
    </script>
</body>
</html>