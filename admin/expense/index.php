<?php
// File: index.php (Main Expense Records Page)

// Start the session to access session variables.
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

require_once '../../db_config.php';

$currentMonth = date('n');
$currentYear = date('Y');

// Redirect to default if both are not set
if (!isset($_GET['month']) && !isset($_GET['year'])) {
    header("Location: index.php?month=$currentMonth&year=$currentYear");
    exit();
}

$selectedMonth = (isset($_GET['month']) && trim($_GET['month']) !== '') ? intval($_GET['month']) : '';
$selectedYear = (isset($_GET['year']) && trim($_GET['year']) !== '') ? intval($_GET['year']) : '';

// Get a list of all years with expense data for the filter dropdown
$yearsResult = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM expenses ORDER BY year DESC");
$availableYears = [];
while ($row = $yearsResult->fetch_assoc()) {
    $availableYears[] = $row['year'];
}
$yearsResult->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Expense Records - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styles remain the same */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #e24e42; /* A soft, modern red for expenses */
            --color-accent-light: #f5d6d6;
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

        /* Fix: Ensures table allows horizontal scroll on small screens */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">

    <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')"
            class="fixed top-4 left-4 z-30 p-2 rounded-md bg-sidebar-bg lg:hidden">
        <i class="fas fa-bars"></i>
    </button>
    
    <div id="offline-banner"
         class="fixed top-0 left-0 right-0 z-[1000] p-3 text-center text-sm font-semibold bg-red-600 text-white shadow-lg hidden">
        <i class="fas fa-plug-na mr-2"></i>
        OFFLINE MODE: You are currently disconnected. Data will sync when network is restored.
    </div>


    <aside id="sidebar" class="w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl
                             transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0
                             transform -translate-x-full lg:translate-x-0 z-20">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
        <img src="aleinahslogo.png" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
        <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5 h-[calc(100vh-160px)] overflow-y-auto">
            <ul class="space-y-4">
                <li>
                    <a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-home text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-coins text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Income Record</span>
                    </a>
                </li>
                <li>
                    <a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200">
                        <i class="fas fa-chart-pie text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Expense Record</span>
                    </a>
                </li>
                <li>
                    <a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-wallet text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Budget Record</span>
                    </a>
                </li>
                <li>
                    <a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-chart-line text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Financial Overview</span>
                    </a>
                </li>
                <li>
                    <a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-boxes text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="../payroll/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-users text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Payroll System</span>
                    </a>
                </li>
                <li>
                    <li>
                    <a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-check-double text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Data Approval</span>
                    </a>
                    </li>
                </li>
                <li>
                    <a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-money-check-alt text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Payment Record</span>
                    </a>
                </li>
                <li>
                    <a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-bell text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Notifications</span>
                    </a>
                <li>
                    <a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200">
                        <i class="fas fa-history text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Transaction History</span>
                    </a>
                </li>
                <li class="pt-4 border-t border-gray-300 mt-4">
                    <a href="../../login/logout.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-red-600 hover:bg-red-100 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-6 pt-16 lg:pt-6 lg:ml-64 overflow-y-auto">
        <header class="flex flex-col md:flex-row items-center justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-dark-text">Expense Records</h2>
                <p class="text-light-text mt-1">Track all your expenses here.</p>
            </div>
        </header>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <form method="GET" class="flex flex-wrap gap-2 mb-4 md:mb-0 items-center" id="filterForm" autocomplete="off">
                <label for="monthSelect" class="sr-only">Month</label>
                <select id="monthSelect" name="month" class="px-4 py-2 rounded-full border bg-white focus:outline-none focus:ring-2 focus:ring-accent text-dark-text">
                    <option value="">All Months</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $monthName = date('F', mktime(0, 0, 0, $m, 10));
                        $selected = ($selectedMonth == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>$monthName</option>";
                    }
                    ?>
                </select>
                <label for="yearSelect" class="sr-only">Year</label>
                <select id="yearSelect" name="year" class="px-4 py-2 rounded-full border bg-white focus:outline-none focus:ring-2 focus:ring-accent text-dark-text">
                    <option value="">All Years</option>
                    <?php
                    foreach ($availableYears as $year) {
                        $selected = ($selectedYear == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </form>
            <div class="flex gap-2">
                <button onclick="exportExpenseTable()" class="flex items-center px-4 py-2 bg-white rounded-full text-dark-text hover:bg-gray-200 transition-colors duration-200 shadow-sm export-hide">
                    <i class="fas fa-file-export mr-2 text-accent"></i>Export
                </button>
                <button onclick="document.getElementById('expenseModal').classList.remove('hidden')" class="flex items-center px-4 py-2 bg-accent text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm">
                    <i class="fas fa-plus mr-2"></i>Add Expense
                </button>
            </div>
        </div>

        <?php
        // --- CRITICAL: DISPLAY ERROR/SUCCESS MESSAGE FROM REDIRECT ---
        $statusMessage = '';
        if (isset($_GET['success']) && $_GET['success'] == 'added') {
            $statusMessage = '<div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-center font-medium">Expense added successfully!</div>';
        } elseif (isset($_GET['error'])) {
            $errorMessage = htmlspecialchars(urldecode($_GET['error']));
            $statusMessage = '<div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-center font-medium">Error: ' . $errorMessage . '</div>';
        }
        echo $statusMessage;
        // -----------------------------------------------------------


        $totalResult = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE 1=1 " . (!empty($selectedMonth) ? "AND MONTH(date) = $selectedMonth" : "") . (!empty($selectedYear) ? " AND YEAR(date) = $selectedYear" : ""));
        $totalRow = $totalResult->fetch_assoc();
        $total = $totalRow['total'] ?? 0;

        $filterDescription = '';
        if (!empty($selectedMonth) && !empty($selectedYear)) {
            $filterDescription = date('F', mktime(0, 0, 0, $selectedMonth, 10)) . ' ' . $selectedYear;
        } elseif (!empty($selectedMonth)) {
            $filterDescription = 'for the month of ' . date('F', mktime(0, 0, 0, $selectedMonth, 10));
        } elseif (!empty($selectedYear)) {
            $filterDescription = 'for the year ' . $selectedYear;
        } else {
            $filterDescription = 'for all years';
        }
        ?>
        <div class="bg-card-bg rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-dark-text">Total Expenses</h3>
            <p class="text-4xl font-bold text-accent mt-2">₱<?php echo number_format($total, 2); ?></p>
            <p class="text-sm text-light-text mt-1">
                <?php echo $filterDescription; ?>
            </p>
        </div>

        <div class="table-responsive bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Detailed Records</h3>
            <table id="expenseTable" class="w-full min-w-[700px] table-auto">
                <thead class="bg-card-bg">
                    <tr>
                        <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">Date</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Category</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Amount</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Description</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Paid By</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Proof</th>
                        <th class="p-3 text-left font-semibold text-dark-text rounded-tr-lg">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where = [];
                    if ($selectedMonth !== '') $where[] = "MONTH(date) = $selectedMonth";
                    if ($selectedYear !== '') $where[] = "YEAR(date) = $selectedYear";
                    $filterSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                    $query = "SELECT * FROM expenses $filterSQL ORDER BY date DESC";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150'>
                                <td class='p-3'>" . htmlspecialchars($row['date']) . "</td>
                                <td class='p-3'>" . htmlspecialchars($row['category']) . "</td>
                                <td class='p-3'>₱" . number_format($row['amount'], 2) . "</td>
                                <td class='p-3'>" . htmlspecialchars($row['description']) . "</td>
                                <td class='p-3'>" . htmlspecialchars($row['paid_by']) . "</td>
                                <td class='p-3'>";
                        if ($row['proof_file']) {
                            echo "<a href='" . htmlspecialchars($row['proof_file']) . "' target='_blank' class='text-accent hover:underline text-sm font-medium'>View</a>";
                        } else {
                            echo "-";
                        }
                        echo "</td>
                                <td class='p-3 space-x-2'>
                                    <a href='edit_expense.php?id=" . urlencode($row['id']) . "' class='text-accent hover:underline text-sm font-medium'>Edit</a>
                                    <a href='delete_expense.php?id=" . urlencode($row['id']) . "' class='text-red-500 hover:underline text-sm font-medium' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                                </td>
                            </tr>";
                    }
                    $result->close();
                    ?>
                </tbody>
            </table>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-center text-dark-text" id="chart-title">
                Monthly Expense Overview
            </h3>
            <div class="relative overflow-hidden w-full h-96">
                <canvas id="yearlyExpenseChart"></canvas>
            </div>
        </div>

        <div id="expenseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-card-bg p-8 rounded-xl shadow-lg w-full max-w-md">
                <h2 class="text-2xl font-semibold mb-4 text-dark-text">Add New Expense</h2>
                <form id="expenseAddForm" action="add_expense.php" class="space-y-4" method="POST" enctype="multipart/form-data">
                    <input type="date" name="date" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Select Date">
                    <select name="category" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
                        <option value="">Select Category</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Salaries">Salaries</option>
                        <option value="Supplies">Supplies</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Others">Others</option>
                    </select>
                    <input type="number" name="amount" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Enter Amount" min="0" step="0.01">
                    <input type="text" name="description" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" placeholder="Enter Description">
                    <input type="text" name="paid_by" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Paid By">
                    <div class="flex flex-col space-y-2">
                        <label for="proof_file" class="block text-sm font-medium text-light-text">Proof of Purchase (Required)</label>
                        <input type="file" name="proof_file" id="proof_file" class="w-full text-dark-text file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent-light file:text-accent hover:file:bg-accent-light/80" accept="image/*,application/pdf" required>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('expenseModal').classList.add('hidden')" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script src="/capsfilesv2/sw-register.js"></script>
    
    <script src="expense_record.js"></script>
</body>
</html>