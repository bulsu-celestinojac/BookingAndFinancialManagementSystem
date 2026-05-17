<?php
// FILE: payroll_index.php (Payroll History)
require '../../db_config.php';

// Search is removed. Data loads all records.
$where = "WHERE 1";

$query = "
    SELECT 
        pr.*, e.fullname, e.position 
    FROM payroll_records pr
    JOIN employees e ON pr.employee_id = e.id
    $where
    ORDER BY pr.processed_date DESC, pr.end_date DESC, e.fullname ASC
";

$stmt = $conn->prepare($query);

$result = null;
$recordCount = 0;
$totalNetPay = 0;

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $recordCount = $result->num_rows;
    
    // Calculate Total Net Pay for the displayed results
    $data_for_sum = $result->fetch_all(MYSQLI_ASSOC);
    foreach ($data_for_sum as $row) {
        $totalNetPay += $row['net_pay'];
    }
    
    // Reset pointer for display loop
    if ($recordCount > 0) {
        $result->data_seek(0);
    }
} else {
    die("Error executing query: " . $stmt->error);
}

$stmt->close();

// --- Default Dates for Form ---
$default_start_date = date('Y-m-d', strtotime('-7 days'));
$default_end_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payroll Processing & History</title>
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

        /* Custom style for error message visibility */
        #dateError {
            display: none;
            color: #ef4444; /* Red-500 */
            font-size: 0.875rem; /* text-sm */
            margin-top: 0.5rem;
        }
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
        <nav class="mt-8 px-2 lg:px-5">
            <ul class="space-y-4">
                <li><a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="flex-1 p-4 lg:p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="flex flex-col md:flex-row items-center justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-dark-text">Payroll History & Processing</h2>
                <p class="text-light-text mt-1">Define period and calculate wages for active employees.</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <a href="index.php" class="w-full sm:w-auto flex items-center px-4 py-2 bg-gray-600 text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm justify-center">
                    <i class="fas fa-user-friends mr-2"></i>Employee List
                </a>
            </div>
        </header>

        <div class="bg-card-bg rounded-xl shadow-xl p-6 mb-8 border-t-4 border-accent">
            <h3 class="text-xl font-bold text-dark-text mb-4 flex items-center"><i class="fas fa-play-circle mr-2 text-red-500"></i>New Payroll Run</h3>
            <form action="payroll_calculates.php" method="POST" class="space-y-4 md:space-y-0 md:flex md:gap-4 md:items-end" id="payrollForm">
                <div class="flex-1">
                    <label for="start_date" class="block mb-1 font-semibold text-light-text">Start Date (Period Begins)</label>
                    <input type="date" name="start_date" id="start_date" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required value="<?= htmlspecialchars($default_start_date) ?>">
                </div>
                <div class="flex-1">
                    <label for="end_date" class="block mb-1 font-semibold text-light-text">End Date (Period Ends)</label>
                    <input type="date" name="end_date" id="end_date" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required value="<?= htmlspecialchars($default_end_date) ?>">
                    <p id="dateError">End Date must be equal to or after the Start Date.</p>
                </div>
                <div class="w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto px-6 py-3 bg-red-600 text-white rounded-full hover:bg-red-700 font-semibold transition-colors shadow-lg">
                        <i class="fas fa-sync-alt mr-2"></i>Calculate & Record
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Payroll History</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                 <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-accent">
                    <h4 class="text-lg font-semibold text-dark-text">Total Records Displayed</h4>
                    <p class="text-3xl font-bold text-accent mt-1"><?= $recordCount ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500 col-span-1 lg:col-span-2">
                    <h4 class="text-lg font-semibold text-dark-text">Total Net Payroll</h4>
                    <p class="text-3xl font-bold text-green-600 mt-1">₱<?= number_format($totalNetPay, 2) ?></p>
                </div>
            </div>
            
            <div class="hidden md:block overflow-x-auto">
                <table id="payrollTable" class="w-full table-auto min-w-[900px]">
                    <thead class="bg-card-bg">
                        <tr>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">Employee</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Pay Period</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Days Worked</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Gross Pay</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Deductions</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Net Pay</th>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tr-lg">Processed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $result->data_seek(0); // Reset after sum ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class='border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150'>
                                <td class='p-3 font-medium text-dark-text'><?= htmlspecialchars($row['fullname']) ?></td>
                                <td class='p-3 text-light-text text-xs'><?= date('M j, Y', strtotime(htmlspecialchars($row['start_date']))) ?> to <?= date('M j, Y', strtotime(htmlspecialchars($row['end_date']))) ?></td>
                                <td class='p-3 text-dark-text'><?= htmlspecialchars($row['days_worked']) ?></td>
                                <td class='p-3 text-amber-600'>₱<?= number_format($row['gross_pay'], 2) ?></td>
                                <td class='p-3 text-red-500'>-₱<?= number_format($row['deductions'], 2) ?></td>
                                <td class='p-3 font-bold text-green-600'>₱<?= number_format($row['net_pay'], 2) ?></td>
                                <td class='p-3 text-light-text text-xs'><?= date('Y-m-d', strtotime(htmlspecialchars($row['processed_date']))) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                             <tr><td colspan="7" class="p-3 text-center text-light-text">No payroll records found.</td></tr>
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
                                <div class='text-lg font-bold text-dark-text'><?= htmlspecialchars($row['fullname']) ?></div>
                                <span class='text-xs text-light-text'><?= htmlspecialchars($row['position']) ?></span>
                            </div>
                            <div class="text-right">
                                <span class='text-xs font-medium text-light-text'>Net Pay</span>
                                <div class='text-xl font-bold text-green-600'>₱<?= number_format($row['net_pay'], 2) ?></div>
                            </div>
                        </div>
                        <div class='text-xs text-light-text border-t border-gray-100 pt-2 flex justify-between'>
                            <p>Period: <?= date('M j', strtotime(htmlspecialchars($row['start_date']))) ?> - <?= date('M j, Y', strtotime(htmlspecialchars($row['end_date']))) ?></p>
                            <p class='font-semibold'>Worked: <?= htmlspecialchars($row['days_worked']) ?> days</p>
                        </div>
                        <div class="text-xs text-light-text mt-1 flex justify-between">
                            <p>Gross: ₱<?= number_format($row['gross_pay'], 2) ?></p>
                            <p class="text-red-500">Deductions: -₱<?= number_format($row['deductions'], 2) ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-light-text bg-white rounded-lg shadow-sm">No payroll records found.</div>
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

            const payrollForm = document.getElementById('payrollForm');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const dateError = document.getElementById('dateError');

            if (payrollForm && startDateInput && endDateInput) {
                payrollForm.addEventListener('submit', function(e) {
                    const startDate = new Date(startDateInput.value);
                    const endDate = new Date(endDateInput.value);

                    // Validate: End Date must be greater than or equal to Start Date
                    if (endDate < startDate) {
                        e.preventDefault();
                        dateError.style.display = 'block';
                        endDateInput.focus();
                    } else {
                        dateError.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>