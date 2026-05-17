<?php 
// FILE: admin/add_user/add_user_form.php
session_start();

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../../login/index.php"); 
    exit();
}

// REVISION: Removed 'manager' from the security role check since the UI only offers 'admin' and 'staff'.
if ($_SESSION['role'] !== 'admin') { 
    header("Location: ../../admin/dashboard/index.php?error=unauthorized"); 
    exit();
}

// Function to safely display messages (if redirected back with errors/success)
$message = $_GET['message'] ?? '';
$status = $_GET['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User & Employee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-accent: #2563eb; 
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-accent { background-color: var(--color-accent); }
        .text-accent { color: var(--color-accent); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    
    <div class="bg-white p-6 sm:p-8 max-w-xl w-full rounded-xl shadow-2xl">
        <h2 class="text-2xl sm:text-3xl font-bold mb-2 text-dark-text text-center">New Employee Setup</h2>
        <p class="text-center text-light-text mb-6 text-sm sm:text-base">Create system login and employee payroll record automatically.</p>

        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-lg text-sm text-center 
                <?= $status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="process_add_user.php" method="POST" class="space-y-4">
            <h3 class="text-lg font-semibold text-dark-text pt-4 border-t border-gray-200">System Account</h3>
            
            <div>
                <label for="fullname" class="block mb-1 font-semibold text-light-text">Full Name</label>
                <input type="text" id="fullname" name="fullname" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Ex: Juan Dela Cruz">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block mb-1 font-semibold text-light-text">Username (Login)</label>
                    <input type="text" id="username" name="username" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Ex: jdelacruz">
                </div>
                <div>
                    <label for="email" class="block mb-1 font-semibold text-light-text">Email Address</label>
                    <input type="email" id="email" name="email" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="user@example.com">
                </div>
            </div>

            <div>
                <label for="role" class="block mb-1 font-semibold text-light-text">System Role</label>
                <select id="role" name="role" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
                    <option value="staff">Staff (Limited Access)</option>
                    <option value="admin">Admin (Full Access)</option>
                </select>
            </div>
            
            <h3 class="text-lg font-semibold text-dark-text pt-6 border-t border-gray-200">Payroll Details</h3>

            <div>
                <label for="position" class="block mb-1 font-semibold text-light-text">Position Title</label>
                <input type="text" id="position" name="position" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Ex: Front Desk Staff, Housekeeping">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="hired_date" class="block mb-1 font-semibold text-light-text">Hire Date</label>
                    <input type="date" id="hired_date" name="hired_date" value="<?= date('Y-m-d') ?>" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
                </div>
                
                <div>
                    <label for="daily_rate" class="block mb-1 font-semibold text-light-text">Daily Rate (₱)</label>
                    <input type="number" id="daily_rate" name="daily_rate" class="w-full border p-3 rounded-lg bg-gray-50 text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Ex: 537.00" step="0.01" min="0">
                </div>
            </div>

            <input type="hidden" name="password_default" value="password123">
            <p class="text-xs text-center text-light-text pt-2">Default password set to: **password123**</p>
            
            <div class="flex justify-center sm:justify-end pt-6">
                <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-accent text-white rounded-full hover:opacity-90 font-semibold shadow-md transition-colors duration-200">
                    <i class="fas fa-user-plus mr-2"></i> Create User & Employee
                </button>
            </div>
        </form>
    </div>

</body>
</html>