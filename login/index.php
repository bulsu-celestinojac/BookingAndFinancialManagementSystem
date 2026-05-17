<?php
// Check for error messages from URL (GET parameters)
$error = isset($_GET['error']) ? $_GET['error'] : '';
$message = '';

switch ($error) {
    case 'wrongpass':
        $message = 'Incorrect password. Please try again.';
        break;
    case 'nouser':
        $message = 'User not found.';
        break;
    case 'locked':
        $message = 'Your account is locked. Please contact the admin.';
        break;
    case 'wrongotp':
        $message = 'Incorrect OTP. Please try again.';
        break;
    case 'otplocked':
        $message = 'Account locked due to too many incorrect OTP attempts.';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Employee Login | Aleinah's Private Resort</title>
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

    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row">
        
        <!-- Left Side - Image Section with bg.png -->
        <div class="hidden md:flex md:w-1/2 bg-cover bg-center" style="background-image: url('bg.png');">
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full md:w-1/2 p-8 sm:p-12 lg:p-16 flex flex-col justify-center">
            <div class="text-center md:text-left">
                <h2 class="text-4xl font-bold text-gray-800 mb-2">Welcome Back!</h2>
                <p class="text-gray-500 mb-8">Sign in to your account.</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">Username</label>
                    <input type="text" name="username" id="username" required 
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                           placeholder="Enter your username">
                </div>

                <div>
                    <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <input type="password" name="password" id="password" required 
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                           placeholder="Enter your password">
                </div>

                <div class="flex items-center justify-between">
                    <a href="forgot_password.php" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-blue-700 transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-500">
                    Login
                </button>
            </form>

            <div class="text-center text-sm text-gray-400 mt-8">
                <p>Don't have an account? Only admin can create one.</p>
            </div>
        </div>
    </div>
</body>
</html>
