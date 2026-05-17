<?php
// PHP to check for error or success messages from the URL
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sent') {
        $message = 'If an account with that email exists, a password reset link has been sent.';
    } elseif ($_GET['status'] == 'invalid_token') {
        $message = 'The password reset link is invalid or has expired.';
    } elseif ($_GET['status'] == 'updated') {
        $message = 'Your password has been updated successfully. You can now log in.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password | Aleinah's Private Resort</title>
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
        <h2 class="text-4xl font-bold text-gray-800 mb-2">Forgot Password?</h2>
        <p class="text-gray-500 mb-8">Enter your email address to receive a password reset link.</p>
        
        <?php if (!empty($message)): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form action="send_reset_link.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2 text-left">Email Address</label>
                <input type="email" name="email" id="email" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                       placeholder="Enter your email">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-blue-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500">
                Send Reset Link
            </button>
        </form>

        <div class="text-center text-sm text-gray-400 mt-8">
            <p>Remember your password? <a href="index.php" class="text-blue-600 hover:underline">Log in here.</a></p>
        </div>
    </div>
</body>
</html>
