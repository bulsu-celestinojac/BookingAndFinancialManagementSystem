<?php
$conn = new mysqli("localhost", "u205310066_admin", "Aleinahsprivate00.", "u205310066_aleinahsresort");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$error = '';

if (empty($token)) {
    header("Location: forgot_password.php?status=invalid_token");
    exit();
}

// Validate the token from the database
$stmt = $conn->prepare("SELECT userid, token_expiry FROM users WHERE password_reset_token = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($userid, $token_expiry);

// Check if a row was found before fetching.
if ($stmt->num_rows === 0) {
    $stmt->close();
    header("Location: forgot_password.php?status=invalid_token");
    exit();
}

$stmt->fetch();
$stmt->close();

// Check if the token is expired
if (strtotime($token_expiry) < time()) {
    header("Location: forgot_password.php?status=invalid_token");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password | Aleinah's Private Resort</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1A237E 0%, #303F9F 100%);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="bg-white rounded-3xl shadow-2xl p-8 sm:p-12 lg:p-16 w-full max-w-md text-center">
        <h2 class="text-4xl font-bold text-gray-800 mb-2">Reset Password</h2>
        <p class="text-gray-500 mb-8">Enter your new password below.</p>
        
        <form action="update_password.php" method="POST" class="space-y-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div>
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2 text-left">New Password</label>
                <input type="password" name="password" id="password" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                       placeholder="Enter new password">
            </div>

            <div>
                <label for="confirm_password" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                       placeholder="Confirm new password">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-blue-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500">
                Update Password
            </button>
        </form>
    </div>
</body>
</html>
