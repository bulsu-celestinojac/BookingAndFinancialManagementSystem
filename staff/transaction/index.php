<?php
// File: index.php (Main Transaction Records Page)

// Start the session at the very beginning of the script
session_start();

// Function to check if the user has admin or staff privileges
function is_admin_or_staff() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff');
}

// Function to check if the user is an admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// --- SECURITY LOGIC ---
if (!is_admin_or_staff()) {
    header("Location: ../../index.php?error=permissiondenied");
    exit();
}
// --- END OF SECURITY LOGIC ---

require_once '../../db_config.php';

$currentMonth = date('n');
$currentYear = date('Y');

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$transactionHistory = [];
try {
    $stmt = $conn->prepare("
        SELECT 'Income' as type, date, source as category, description, amount
        FROM income
        WHERE approval_status = 'approved' AND date BETWEEN ? AND ?
        UNION ALL
        SELECT 'Expense' as type, date, category, description, -amount as amount
        FROM expense
        WHERE approval_status = 'approved' AND date BETWEEN ? AND ?
        UNION ALL
        SELECT 'Payroll' as type, date, 'Payroll' as category, description, -amount as amount
        FROM payroll
        WHERE approval_status = 'approved' AND date BETWEEN ? AND ?
        ORDER BY date DESC
    ");
    $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactionHistory[] = $row;
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $transactionHistory = [];
    error_log("Transaction history query failed: " . $e->getMessage());
}
$conn->close();

$netChange = array_sum(array_column($transactionHistory, 'amount'));
$filterDescription = "Transactions from " . date('F j, Y', strtotime($startDate)) . " to " . date('F j, Y', strtotime($endDate));
$logoPath = '../dashboard/aleinahslogo.png';
$logoExists = file_exists($logoPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow">
    <title>Transaction History - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #22c55e;
            --color-accent-light: #dcfce7;
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-main-bg { background-color: var(--color-main-bg); }
        .bg-card-bg { background-color: var(--color-card-bg); }
        .bg-sidebar-bg { background-color: var(--color-sidebar-bg); }
        .text-dark-text { color: var(--color-dark-text); }
        .text-light-text { color: var(--color-light-text); }
        .text-accent { color: var(--color-accent); }
        .bg-accent { background-color: var(--color-accent); }
        .bg-accent-light { background-color: var(--color-accent-light); }
        
        .sidebar-active {
            color: var(--color-accent);
            background-color: var(--color-accent-light);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .type-Income { background-color: #dcfce7; color: #15803d; }
        .type-Expense { background-color: #fee2e2; color: #dc2626; }
        .type-Payroll { background-color: #dbeafe; color: #2563eb; }
        .type-Budget { background-color: #fffbe6; color: #b45309; }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">
    <div id="offline-banner"
        class="fixed top-0 left-0 right-0 z-[1000] p-3 text-center text-sm font-semibold bg-red-600 text-white shadow-lg hidden">
        <i class="fas fa-plug-na mr-2"></i>
        OFFLINE MODE: You are currently disconnected. Data will sync when network is restored.
    </div>
    
    <div class="lg:hidden fixed top-0 left-0 p-4 z-30">
        <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="text-dark-text bg-sidebar-bg p-2 rounded-md">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <aside id="sidebar" class="w-16 lg:w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0 transform -translate-x-full lg:translate-x-0 z-20">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
            <?php if ($logoExists): ?>
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
            <?php endif; ?>
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5 flex flex-col h-[calc(100vh-160px)]">
            <ul class="space-y-4 flex-1 overflow-y-auto">
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-bell text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Notifications</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg sidebar-active transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
                <li class="pt-4 border-t border-gray-300 mt-auto">
                    <a href="../../login/logout.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-red-600 hover:bg-red-100 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="flex flex-col md:flex-row items-center justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-dark-text">Consolidated Transaction History</h2>
                <p class="text-light-text mt-1">View all financial movements (Income, Expense, Payroll, Budget) in one list.</p>
            </div>
        </header>
        
        <div class="bg-card-bg rounded-xl shadow-md p-6 mb-8 border-t-4 border-accent">
            <h3 class="text-xl font-bold text-dark-text mb-4">Filter Period</h3>
            <form method="GET" class="space-y-4 md:space-y-0 md:flex md:gap-4 md:items-end" id="filterForm" autocomplete="off">
                <div class="flex-1">
                    <label for="start_date" class="block mb-1 font-semibold text-light-text">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="flex-1">
                    <label for="end_date" class="block mb-1 font-semibold text-light-text">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto px-6 py-3 bg-accent text-white rounded-full hover:bg-green-700 font-semibold transition-colors shadow-lg">
                        <i class="fas fa-filter mr-2"></i>Apply Filter
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-card-bg rounded-xl shadow-md p-6 mb-8 border-l-4
            <?= $netChange >= 0 ? 'border-green-500' : 'border-red-500' ?>">
            <h3 class="text-lg font-semibold text-dark-text">Net Financial Change (Profit/Loss)</h3>
            <p class="text-4xl font-bold mt-2
                <?= $netChange >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                ₱<?= number_format(abs($netChange), 2) ?>
            </p>
            <p class="text-sm text-light-text mt-1">
                <?= $filterDescription ?>
            </p>
        </div>

        <div class="overflow-x-auto bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Transaction Details (Newest First)</h3>
            
            <div class="hidden md:block overflow-x-auto">
                <table id="transactionTable" class="w-full table-auto min-w-[700px]">
                    <thead class="bg-sidebar-bg">
                        <tr>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">Date</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Type</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Category/Source</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Description</th>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tr-lg">Amount (Net)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactionHistory)): ?>
                            <?php foreach ($transactionHistory as $transaction): ?>
                            <tr class='border-b hover:bg-gray-50'>
                                <td class='p-3'><?= htmlspecialchars($transaction['date']) ?></td>
                                <td class='p-3 font-medium'>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?= $transaction['type'] === 'Income' ? 'type-Income' : ($transaction['type'] === 'Expense' ? 'type-Expense' : ($transaction['type'] === 'Payroll' ? 'type-Payroll' : 'type-Budget')) ?>">
                                        <?= htmlspecialchars($transaction['type']) ?>
                                    </span>
                                </td>
                                <td class='p-3 text-sm text-gray-600'><?= htmlspecialchars($transaction['category']) ?></td>
                                <td class='p-3 text-sm text-gray-600'><?= htmlspecialchars($transaction['description']) ?></td>
                                <td class='p-3 font-bold
                                    <?= $transaction['amount'] < 0 ? 'text-red-600' : 'text-green-600' ?>'>
                                    ₱<?= number_format(abs($transaction['amount']), 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="p-5 text-center text-light-text">No transactions found for the selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-4">
                <?php if (!empty($transactionHistory)): ?>
                    <?php foreach ($transactionHistory as $transaction): ?>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200
                        <?= $transaction['amount'] < 0 ? 'border-l-4 border-red-500' : 'border-l-4 border-green-500' ?>">
                        
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-3 py-1 rounded-full text-sm font-bold
                                <?= $transaction['type'] === 'Income' ? 'type-Income' : ($transaction['type'] === 'Expense' ? 'type-Expense' : ($transaction['type'] === 'Payroll' ? 'type-Payroll' : 'type-Budget')) ?>">
                                <?= htmlspecialchars($transaction['type']) ?>
                            </span>
                            <div class="text-right">
                                <span class="text-sm text-light-text block">Amount (Net)</span>
                                <div class="text-xl font-extrabold
                                    <?= $transaction['amount'] < 0 ? 'text-red-600' : 'text-green-600' ?>">
                                    ₱<?= number_format(abs($transaction['amount']), 2) ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-xs text-light-text border-t border-gray-100 pt-2 mt-2">
                            <p class="font-medium text-dark-text">Category: <?= htmlspecialchars($transaction['category']) ?></p>
                            <p>Date: <?= htmlspecialchars($transaction['date']) ?></p>
                        </div>
                        
                        <p class="text-xs italic text-light-text mt-1">
                            Description: <?= htmlspecialchars($transaction['description']) ?>
                        </p>

                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>