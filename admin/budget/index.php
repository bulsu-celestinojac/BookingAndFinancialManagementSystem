<?php
// FILE: index.php (Budget Records)

// Start the session to access session variables.
// It is a best practice to ensure session_start() is called at the very top.
session_start();

// CRITICAL SECURITY: Include the database config from the project root (two levels up)
require '../../db_config.php';

// --- SECURITY LOGIC ---

// 1. Check if the user is authenticated at all.
// The session variable 'userid' is what you used in your login_process.php
if (!isset($_SESSION['userid'])) {
    // If not logged in, redirect them to the main login page
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

// 2. Check if the authenticated user has the correct role for this page.
// This page should only be accessible by administrators.
if ($_SESSION['role'] !== 'admin') {
    // If the role is not 'admin', deny access and redirect
    header("Location: ../../index.php?error=permissiondenied");
    exit();
}

// --- END OF SECURITY LOGIC ---

$currentMonth = date('n');
$currentYear = date('Y');

if (!isset($_GET['month']) && !isset($_GET['year'])) {
    header("Location: index.php?month=$currentMonth&year=$currentYear");
    exit();
}

$selectedMonth = (isset($_GET['month']) && trim($_GET['month']) !== '') ? intval($_GET['month']) : '';
$selectedYear = (isset($_GET['year']) && trim($_GET['year']) !== '') ? intval($_GET['year']) : '';
$categories = ['Utilities', 'Salaries', 'Supplies', 'Maintenance', 'Others'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Budget Records - Aleinah's Resort</title>
    <meta name="description" content="Manage and track all budget records for Aleinah's Resort, including payments, reservations, and other revenue streams." />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #f59e42;
            --color-accent-light: #fff0d6;
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
        .table-responsive { overflow-x: auto; }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">

    <div class="lg:hidden fixed top-0 left-0 p-4 z-30">
        <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="text-dark-text bg-sidebar-bg p-2 rounded-md">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <aside id="sidebar" class="w-16 lg:w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0 transform -translate-x-full lg:translate-x-0 z-20">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
            <img src="aleinahslogo.png" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5 h-[calc(100vh-160px)] overflow-y-auto">
            <ul class="space-y-4">
                <li><a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../payroll/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-bell text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Notifications</span></a></li>
                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
                <li class="pt-4 border-t border-gray-300 mt-4">
                    <a href="../../login/logout.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-red-600 hover:bg-red-100 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt text-lg w-8 text-center"></i>
                        <span class="ml-4 font-medium hidden lg:inline">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-4 lg:p-6 lg:ml-64 overflow-y-auto">
        <header class="flex flex-col md:flex-row items-start justify-between mb-6 lg:mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-2xl lg:text-3xl font-bold text-dark-text">Budget Records</h2>
                <p class="text-light-text mt-1 text-sm">Track all your budget allocations here.</p>
            </div>
        </header>

        <div class="flex flex-col gap-4 md:flex-row justify-between items-start md:items-center mb-6">
            
            <form method="GET" class="flex flex-wrap gap-2 items-center w-full md:w-auto" id="filterForm" autocomplete="off">
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
                    for ($y = $currentYear; $y >= 2020; $y--) {
                        $selected = ($selectedYear == $y) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>
            </form>
            
            <div class="flex flex-wrap gap-2 w-full justify-start md:justify-end">
                <button onclick="exportBudgetTable()" class="flex items-center px-3 py-2 text-sm bg-white rounded-full text-dark-text hover:bg-gray-200 transition-colors duration-200 shadow-sm export-hide">
                    <i class="fas fa-file-export mr-2 text-accent"></i>Export
                </button>
                <?php foreach ($categories as $cat):
                    $stmt = $conn->prepare("SELECT id FROM budget WHERE category=? AND MONTH(date)=? AND YEAR(date)=?");
                    $stmt->bind_param("sii", $cat, $selectedMonth, $selectedYear);
                    $stmt->execute();
                    $stmt->store_result();
                    $alreadyAdded = $stmt->num_rows > 0;
                    $stmt->close();
                ?>
                    <button
                        onclick="openBudgetModal('<?php echo $cat; ?>', 'Admin')"
                        class="flex items-center px-3 py-2 text-sm rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm
                        <?php echo $alreadyAdded ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-accent text-white'; ?>"
                        <?php if ($alreadyAdded) echo 'disabled'; ?>
                    >
                        <i class="fas fa-plus mr-1"></i><span class="hidden sm:inline">Add</span> <?php echo $cat; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
            $where = [];
            if ($selectedMonth !== '') $where[] = "MONTH(date) = $selectedMonth";
            if ($selectedYear !== '') $where[] = "YEAR(date) = $selectedYear";
            $filterSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $totalResult = $conn->query("SELECT SUM(amount) as total FROM budget $filterSQL");
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
            <h3 class="text-lg font-semibold text-dark-text">Total Budget</h3>
            <p class="text-4xl font-bold text-accent mt-2">₱<?php echo number_format($total, 2); ?></p>
            <p class="text-sm text-light-text mt-1">
                <?php echo $filterDescription; ?>
            </p>
        </div>

        <div class="overflow-x-auto bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Detailed Records</h3>
            <table id="budgetTable" class="w-full table-auto min-w-[700px]">
                <thead class="bg-card-bg">
                    <tr>
                        <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">Date</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Category</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Amount</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Description</th>
                        <th class="p-3 text-left font-semibold text-dark-text">Approved By</th>
                        <th class="p-3 text-left font-semibold text-dark-text rounded-tr-lg">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM budget $filterSQL ORDER BY date DESC";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr class='border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150'>
                                <td class='p-3'>" . htmlspecialchars($row['date']) . "</td>
                                <td class='p-3'>" . htmlspecialchars($row['category']) . "</td>
                                <td class='p-3'>₱" . number_format($row['amount'], 2) . "</td>
                                <td class='p-3'>" . htmlspecialchars($row['description']) . "</td>
                                <td class='p-3'>" . htmlspecialchars($row['approved_by']) . "</td>
                                <td class='p-3 space-x-2'>
                                    <a href='edit_budget.php?id=" . $row['id'] . "' class='text-accent hover:underline text-sm font-medium'>Edit</a>
                                    <a href='delete_budget.php?id=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this budget record?');\" class='text-red-500 hover:underline text-sm font-medium'>Delete</a>
                                </td>
                            </tr>";
                    }
                    $result->close();
                    ?>
                </tbody>
            </table>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-center text-dark-text">
                Budget Allocation by Category for
                <?php
                    echo ($selectedMonth ? date('F', mktime(0,0,0,$selectedMonth,1)) : 'All Months') . ' ' .
                        ($selectedYear ? htmlspecialchars($selectedYear) : 'All Years');
                ?>
            </h3>
            <div class="relative overflow-hidden w-full h-96">
                <canvas id="budgetPieChart"></canvas>
            </div>
        </div>

        <?php foreach ($categories as $cat): ?>
        <div id="budgetModal-<?php echo $cat; ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-card-bg p-8 rounded-xl shadow-lg w-full max-w-md">
                <h2 class="text-2xl font-semibold mb-4 text-dark-text">Add Budget: <?php echo $cat; ?></h2>
                <form class="space-y-4" action="add_budget.php" method="POST">
                    <input type="hidden" name="category" value="<?php echo $cat; ?>">
                    <input
                        type="date"
                        id="budgetDate-<?php echo $cat; ?>"
                        name="date"
                        class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent"
                        required
                    >
                    <input
                        type="number"
                        name="amount"
                        class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent"
                        required
                        placeholder="Enter Amount"
                    >
                    <input
                        type="text"
                        name="description"
                        class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent"
                        placeholder="Enter Description"
                    >
                    <input
                        type="text"
                        id="approvedBy-<?php echo $cat; ?>"
                        name="approved_by"
                        class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent"
                        placeholder="Approved By"
                        required
                    >
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeBudgetModal('<?php echo $cat; ?>')" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/localforage/1.7.3/localforage.min.js"></script>
    
    <script src="budget_record.js"></script>
    <script>
        // Set budget modal date fields to first day of selected month/year
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($categories as $cat): ?>
                document.getElementById('budgetDate-<?php echo $cat; ?>').value =
                    "<?php echo $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '-01'; ?>";
            <?php endforeach; ?>
        });
    </script>
</body>
</html>