<?php
// FILE: index.php (Data Approval Center)

// Start the session at the very beginning of the script
session_start();

// --- SECURITY LOGIC ---
// 1. Check if the user is authenticated (logged in)
if (!isset($_SESSION['userid'])) {
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

// 2. Check if the authenticated user has the correct role for this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php?error=permissiondenied");
    exit();
}
// --- END OF SECURITY LOGIC ---

// Now that security is handled, continue with the rest of your page logic
require '../../db_config.php';

// --- UPDATED DATABASE QUERY ---
// The SELECT statements must have the same number of columns with the same aliases.
// Query for pending expenses
// We use 'paid_by' and alias it as 'submitted_by' to match the table display logic.
$expensesQuery = "SELECT id, 'Expense' AS type, date, category, amount, description, paid_by AS submitted_by, proof_file FROM expenses WHERE approval_status = 'pending'";

// Query for pending income
// We use 'source' and alias it as 'category' and a blank string for 'submitted_by' to align columns.
$incomesQuery = "SELECT id, 'Income' AS type, date, source AS category, amount, description, '' AS submitted_by, NULL AS proof_file FROM income WHERE approval_status = 'pending'";

// Combine only expenses and income queries using UNION ALL
$fullQuery = "($expensesQuery) UNION ALL ($incomesQuery) ORDER BY date DESC";

$result = $conn->query($fullQuery);
$pendingRecords = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pendingRecords[] = $row;
    }
} else {
    // Provide a more descriptive error message to the developer, but not the user
    die("Error fetching pending records: " . $conn->error);
}

$logoPath = 'aleinahslogo.png';
$logoExists = file_exists($logoPath);

$status = $_GET['status'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Approval - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-approval: #2563eb;
            --color-accent: #f59e42;
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-main-bg { background-color: var(--color-main-bg); }
        .bg-card-bg { background-color: var(--color-card-bg); }
        .bg-sidebar-bg { background-color: var(--color-sidebar-bg); }
        .text-dark-text { color: var(--color-dark-text); }
        .text-light-text { color: var(--color-light-text); }
        .active-approval-link {
            color: var(--color-approval);
            background-color: #dbeafe;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .active-notif-link {
            color: var(--color-accent);
            background-color: #fff0d6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">

    <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="fixed top-4 left-4 z-50 p-3 rounded-full bg-blue-600 text-white lg:hidden shadow-lg transition-all duration-300">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <aside id="sidebar" class="w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0 z-40 transform -translate-x-full lg:translate-x-0">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
            <?php if ($logoExists): ?>
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" class="h-12 lg:h-16">
            <?php endif; ?>
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        
        <nav class="mt-8 px-2 lg:px-5 h-[calc(100vh-160px)] overflow-y-auto">
            <ul class="space-y-4">
                <li><a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../payroll/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg active-approval-link transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-red-50 transition-colors duration-200">
                    <i id="notificationBell" class="fas fa-bell text-lg w-8 text-center"></i>
                    <span class="ml-4 font-medium hidden lg:inline">Notifications</span>
                    <span id="notificationBadge" class="absolute lg:relative lg:ml-2 px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full hidden">0</span>
                </a></li>
                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
                <li class="pt-4 border-t border-gray-300 mt-4">
                    <a href="../../login/logout.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-red-600 hover:bg-red-100 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="mb-8">
            <h2 class="text-3xl font-bold text-dark-text">Data Approval Center</h2>
            <p class="text-light-text mt-1">Review and authorize pending financial transactions submitted by staff.</p>
        </header>
        
        <?php if ($status): ?>
            <div class="mb-4 p-4 rounded-lg <?= $status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <p class="font-semibold"><i class="fas <?= $status === 'success' ? 'fa-check-circle' : 'fa-times-circle' ?> mr-2"></i><?= htmlspecialchars(urldecode($msg)) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text border-b pb-2">Pending Records (<?= htmlspecialchars(count($pendingRecords)) ?>)</h3>
            
            <?php if (empty($pendingRecords)): ?>
                <div class="p-5 text-center text-light-text">No records require approval at this time.</div>
            <?php else: ?>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-sidebar-bg">
                            <tr>
                                <th class="p-3 text-left font-semibold text-dark-text">Date</th>
                                <th class="p-3 text-left font-semibold text-dark-text">Type</th>
                                <th class="p-3 text-left font-semibold text-dark-text">Category</th>
                                <th class="p-3 text-left font-semibold text-dark-text">Amount</th>
                                <th class="p-3 text-left font-semibold text-dark-text">Submitted By</th>
                                <th class="p-3 text-left font-semibold text-dark-text">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRecords as $record): ?>
                            <tr class='border-b border-gray-200 hover:bg-gray-50'>
                                <td class='p-3'><?= htmlspecialchars($record['date']) ?></td>
                                <td class='p-3 font-medium'>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?= $record['type'] === 'Income' ? 'bg-green-100 text-green-700' : ($record['type'] === 'Budget' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                                        <?= htmlspecialchars($record['type']) ?>
                                    </span>
                                </td>
                                <td class='p-3'><?= htmlspecialchars($record['category']) ?></td>
                                <td class='p-3 font-bold text-lg'>₱<?= number_format($record['amount'], 2) ?></td>
                                <td class='p-3 text-light-text'><?= htmlspecialchars($record['submitted_by']) ?></td>
                                <td class='p-3 space-x-2'>
                                    <form action="process_approval.php" method="POST" class="inline-block">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($record['id']) ?>">
                                        <input type="hidden" name="record_type" value="<?= htmlspecialchars($record['type']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-xs font-semibold">
                                            Approve
                                        </button>
                                    </form>
                                    <form action="process_approval.php" method="POST" class="inline-block">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($record['id']) ?>">
                                        <input type="hidden" name="record_type" value="<?= htmlspecialchars($record['type']) ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs font-semibold" onclick="return confirm('Rejecting this record will permanently delete it. Are you sure?')">
                                            Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr class='bg-gray-50'><td colspan='6' class='p-2 text-xs text-light-text border-b'>Description: <?= htmlspecialchars($record['description']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden space-y-4">
                    <?php foreach ($pendingRecords as $record): ?>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4
                        <?= $record['type'] === 'Income' ? 'border-green-500' : ($record['type'] === 'Budget' ? 'border-amber-500' : 'border-red-500') ?>">
                        
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-3 py-1 rounded-full text-sm font-bold
                                <?= $record['type'] === 'Income' ? 'bg-green-100 text-green-700' : ($record['type'] === 'Budget' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') ?>">
                                <?= htmlspecialchars($record['type']) ?>
                            </span>
                            <div class="text-right">
                                <span class="text-xs text-light-text block">Date: <?= htmlspecialchars($record['date']) ?></span>
                                <div class="text-xl font-extrabold text-dark-text">₱<?= number_format($record['amount'], 2) ?></div>
                            </div>
                        </div>

                        <p class="text-sm font-medium text-dark-text">Category: <?= htmlspecialchars($record['category']) ?></p>
                        <p class="text-xs text-light-text mb-2">Submitted By: <?= htmlspecialchars($record['submitted_by']) ?></p>
                        <p class="text-xs italic text-light-text border-t border-gray-100 pt-2">Desc: <?= htmlspecialchars($record['description']) ?></p>
                        
                        <div class="flex justify-start gap-3 mt-4">
                            <form action="process_approval.php" method="POST" class="inline-block w-1/2">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($record['id']) ?>">
                                <input type="hidden" name="record_type" value="<?= htmlspecialchars($record['type']) ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="w-full bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600 text-sm font-semibold">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form action="process_approval.php" method="POST" class="inline-block w-1/2">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($record['id']) ?>">
                                <input type="hidden" name="record_type" value="<?= htmlspecialchars($record['type']) ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="w-full bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 text-sm font-semibold" onclick="return confirm('Rejecting this record will permanently delete it. Are you sure?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <script src="../notifications/notifications.js"></script>
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