<?php
require '../../db_config.php'; // Using centralized config

$selectedMonth = $_GET['month'] ?? date('n');
$selectedYear = $_GET['year'] ?? date('Y');

// Fetch all usage records for the selected period
$query = "
    SELECT 
        su.id, su.date, su.amount_used, su.used_by,
        i.name as item_name, i.unit
    FROM supply_usage su
    JOIN inventory i ON su.item_id = i.id
    WHERE MONTH(su.date) = ? AND YEAR(su.date) = ?
    ORDER BY su.date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $selectedMonth, $selectedYear);
$stmt->execute();
$result = $stmt->get_result();

// Get available years for filter
$yearsResult = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM supply_usage ORDER BY year DESC");
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
    <title>Supply Usage Records - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Light & Modern Color Palette for Private Resort - Consistent */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #14b8a6; /* Teal for highlight */
            --color-accent-light: #ccfbf1;
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
            <h1 class="text-xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5">
             <ul class="space-y-4">
                <li><a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../payroll/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-4 lg:p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="flex flex-col md:flex-row items-start justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-dark-text">Supply Usage History</h2>
                <p class="text-light-text mt-1">Review where and when supplies were consumed.</p>
            </div>
            <div class="flex items-center space-x-4 mt-2 md:mt-0">
                <a href="index.php" class="flex items-center px-4 py-2 bg-yellow-600 text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
                </a>
            </div>
        </header>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <form method="GET" class="flex flex-wrap gap-2 mb-4 md:mb-0 items-center" id="filterForm" autocomplete="off">
                <label for="monthSelect" class="sr-only">Month</label>
                <select id="monthSelect" name="month" class="px-4 py-2 rounded-full border bg-white focus:outline-none focus:ring-2 focus:ring-accent text-dark-text">
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
                    <?php
                    foreach ($availableYears as $year) {
                        $selected = ($selectedYear == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Usage Records for <?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) ?></h3>
            
            <div class="hidden md:block overflow-x-auto">
                <table id="usageTable" class="w-full table-auto min-w-[600px]">
                    <thead>
                        <tr class="bg-card-bg">
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">Date</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Item Name</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Amount Used</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Used By/Area</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class='border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150'>
                                <td class='p-3'><?= htmlspecialchars($row['date']) ?></td>
                                <td class='p-3 font-medium text-dark-text'><?= htmlspecialchars($row['item_name']) ?></td>
                                <td class='p-3 text-red-500 font-bold'>-<?= htmlspecialchars($row['amount_used']) ?> <?= htmlspecialchars($row['unit']) ?></td>
                                <td class='p-3 text-light-text'><?= htmlspecialchars($row['used_by']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="p-3 text-center text-light-text">No supply usage recorded for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-4">
                <?php $result->data_seek(0); ?>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div class='bg-white p-4 rounded-lg shadow-sm border border-gray-200'>
                        <div class='flex justify-between items-start mb-2'>
                            <span class='text-md font-bold text-dark-text'><?= htmlspecialchars($row['item_name']) ?></span>
                            <span class='text-sm font-medium text-dark-text'><?= htmlspecialchars($row['date']) ?></span>
                        </div>
                        <div class='text-2xl font-bold text-red-500 mb-2 flex items-center'>
                            <i class='fas fa-minus-circle text-lg mr-2'></i>
                            -<?= htmlspecialchars($row['amount_used']) ?> <span class='text-base font-normal ml-1'><?= htmlspecialchars($row['unit']) ?></span>
                        </div>
                        <div class='flex justify-between items-center text-xs text-light-text pt-2 border-t border-gray-100'>
                            <span class='font-medium'>Area: <?= htmlspecialchars($row['used_by']) ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-light-text bg-white rounded-lg shadow-sm">No supply usage recorded for this period.</div>
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
            
            // Filter form auto-submit
            document.getElementById('monthSelect')?.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
            document.getElementById('yearSelect')?.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
    </script>
</body>
</html>