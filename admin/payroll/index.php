<?php
// FILE: index.php (Employee List)

// Start the session at the very beginning of the script
session_start();

// --- SECURITY LOGIC ---
// 1. Check if the user is authenticated (logged in)
if (!isset($_SESSION['userid'])) {
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

// 2. Check if the authenticated user has the correct role for this page.
// This page should only be accessible by administrators.
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php?error=permissiondenied");
    exit();
}
// --- END OF SECURITY LOGIC ---

require '../../db_config.php';

// Data filters by status='Active'
$where = "WHERE status = 'Active'";
$query = "SELECT * FROM employees $where ORDER BY fullname ASC";
$stmt = $conn->prepare($query);

$result = null;
$employeeCount = 0;

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $employeeCount = $result->num_rows;
} else {
    die("Error executing query: " . $stmt->error);
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payroll System - Employee List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ... (CSS Styles remain unchanged) ... */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #2563eb; /* Blue for Payroll */
            --color-accent-light: #dbeafe;
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-main-bg { background-color: var(--color-main-bg); }
        .bg-card-bg { background-color: var(--color-card-bg); }
        .bg-sidebar-bg { background-color: var(--color-sidebar-bg); }
        .bg-accent { background-color: var(--color-accent); }
        .bg-accent-light { background-color: var(--color-accent-light); }
        .text-dark-text { color: var(--color-dark-text); }
        .text-light-text { color: var(--color-light-text); }
        .text-accent { color: var(--color-accent); }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">

    <button id="sidebarToggle" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="fixed top-4 left-4 z-50 p-3 rounded-full bg-accent text-white lg:hidden shadow-lg transition-all duration-300">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <aside id="sidebar" class="w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0 z-40 transform -translate-x-full lg:translate-x-0">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
            <img src="aleinahslogo.png" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5 flex flex-col h-[calc(100vh-160px)]">
            <ul class="space-y-4 flex-1 overflow-y-auto">
                <li><a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-bell text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Notifications</span></a></li>
                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
            </ul>
            <ul class="space-y-4 pt-4 border-t border-gray-300 mt-auto">
                <li>
                    <a href="../../login/logout.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-red-600 hover:bg-red-100 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-4 lg:p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="flex flex-col md:flex-row items-center justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-dark-text">Employee Management</h2>
                <p class="text-light-text mt-1">Manage employee records and payroll processes.</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <a href="payroll_index.php" class="w-full sm:w-auto flex items-center px-4 py-2 bg-green-600 text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm justify-center">
                    <i class="fas fa-calculator mr-2"></i>Process Payroll
                </a>
                <a href="../add_employee/add_user_form.php" class="w-full sm:w-auto flex items-center px-4 py-2 bg-accent text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm justify-center">
                    <i class="fas fa-user-plus mr-2"></i>Add Employee
                </a>
            </div>
        </header>

        <div class="flex justify-start items-center mb-6">
            <p class="text-light-text text-lg font-semibold"><?= $employeeCount ?> Active Employee<?= $employeeCount == 1 ? '' : 's' ?></p>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Employee Records</h3>
            
            <div class="hidden md:block overflow-x-auto">
                <table id="employeeTable" class="w-full table-auto min-w-[700px]">
                    <thead class="bg-card-bg">
                        <tr>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">ID</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Full Name</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Position</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Daily Rate</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Hired Date</th>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tr-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class='border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150'>
                                <td class='p-3 font-medium text-dark-text'><?= htmlspecialchars($row['employee_id']) ?></td>
                                <td class='p-3'><?= htmlspecialchars($row['fullname']) ?></td>
                                <td class='p-3 text-light-text'><?= htmlspecialchars($row['position']) ?></td>
                                <td class='p-3 font-semibold text-green-600'>₱<?= number_format($row['daily_rate'], 2) ?></td>
                                <td class='p-3 text-light-text'><?= date('Y-m-d', strtotime(htmlspecialchars($row['hired_date']))) ?></td>
                                <td class='p-3 space-x-2'>
                                    <a href='employee_form.php?id=<?= $row['id'] ?>' class='text-accent hover:underline text-sm font-medium'>Edit</a>
                                    <a href='delete_employee.php?id=<?= $row['id'] ?>' onclick='return confirm("Are you sure you want to deactivate this employee?")' class='text-red-500 hover:underline text-sm font-medium'>Deactivate</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="p-3 text-center text-light-text">No active employees found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-4">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $result->data_seek(0); // Reset pointer for card view ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div class='bg-white p-4 rounded-lg shadow-sm border border-gray-200'>
                        <div class='flex justify-between items-start mb-2'>
                            <div>
                                <span class='text-xs font-semibold uppercase text-light-text'>#<?= htmlspecialchars($row['employee_id']) ?></span>
                                <div class='text-lg font-bold text-dark-text'><?= htmlspecialchars($row['fullname']) ?></div>
                                <span class='text-sm text-accent'><?= htmlspecialchars($row['position']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class='text-sm font-medium text-light-text'>Rate</span>
                                <div class='text-xl font-bold text-green-600'>₱<?= number_format($row['daily_rate'], 2) ?></div>
                            </div>
                        </div>
                        <div class='flex justify-between items-center text-xs text-light-text pt-2 border-t border-gray-100'>
                            <span class='font-medium'>Hired: <?= date('Y-m-d', strtotime(htmlspecialchars($row['hired_date']))) ?></span>
                            <div class='space-x-3'>
                                <a href='employee_form.php?id=<?= $row['id'] ?>' class='text-accent hover:underline font-semibold'>Edit</a>
                                <a href='delete_employee.php?id=<?= $row['id'] ?>' onclick='return confirm("Deactivate this employee?")' class='text-red-500 hover:underline font-semibold'>Deactivate</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-light-text bg-white rounded-lg shadow-sm">No active employees found.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar close on link click
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 1024) {
                            sidebar.classList.add('-translate-x-full');
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>