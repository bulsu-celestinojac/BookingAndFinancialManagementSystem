<?php
// FILE: financial-overview/index.php
// NOTE: Assuming db_config.php is in the parent directory of financial-overview

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

// Fetch the year from the URL or default to the current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Financial Overview - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Light & Modern Color Palette for Private Resort - Consistent */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #2dd4bf; /* Teal for Financial Overview */
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

        /* Custom style for active comparison button */
        .btn-compare.active {
            background-color: var(--color-accent);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">
    
    <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="fixed top-4 left-4 z-50 p-3 rounded-full bg-sidebar-bg text-dark-text lg:hidden shadow-lg transition-all duration-300">
        <i class="fas fa-bars text-xl"></i>
    </button>
    
    <aside id="sidebar" class="w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl h-screen fixed top-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
       <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
        <img src="aleinahslogo.png" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
        <h1 class="text-2xl font-extrabold text-dark-text ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-4 h-[calc(100vh-160px)] overflow-y-auto">
            <ul class="space-y-4">
                <li><a href="../dashboard/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium">Budget Record</span></a></li>
                <li><a href="index.php" class="flex items-center px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium">Supply Inventory</span></a></li>
                <li><a href="../payroll/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-bell text-lg w-8 text-center"></i><span class="ml-4 font-medium">Notifications</span></a></li>
                <li><a href="../transaction/index.php" class="flex items-center px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium">Transaction History</span></a></li>
                <li class="pt-4 border-t border-gray-300 mt-4">
                <a href="../../login/logout.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-red-600 hover:bg-red-100 transition-colors duration-200">
                <i class="fas fa-sign-out-alt text-lg w-8 text-center"></i>
                <span class="ml-4 font-medium hidden lg:inline">Logout</span>
            </a>
        </li>
            </ul>
        </nav>
    </aside>
    
    <main id="main-content" class="flex-1 p-4 lg:p-6 ml-0 lg:ml-64 w-full">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-dark-text">Financial Overview</h2>
            <div class="flex items-center space-x-4">
            </div>
        </header>

        <div class="mb-6">
            <form id="filterForm" action="index.php" method="GET" class="flex items-center gap-4">
                <div>
                    <label for="yearSelect" class="block text-sm font-medium text-light-text mb-1">Select Year</label>
                    <select id="yearSelect" name="year" class="w-full sm:w-auto border rounded-lg p-2 bg-main-bg">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear + 1; $y >= 2020; $y--) {
                            $selected = ($selectedYear == $y) ? 'selected' : '';
                            echo "<option value=\"$y\" $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-6 mb-8 lg:hidden">
            <h3 class="text-lg font-semibold text-dark-text mb-2">Net Profit/Loss (Total)</h3>
            <p id="netProfitAmount" class="text-4xl font-bold text-gray-700 mt-2"></p>
            <p id="netProfitStatus" class="text-sm mt-1"></p>
        </div>

        <div class="flex flex-wrap gap-3 mb-6">
            <button id="btn-all" onclick="compareData('all')" class="btn-compare px-3 py-2 text-sm rounded-lg border border-gray-300 text-dark-text hover:bg-gray-100 active">
                All 3
            </button>
            <button id="btn-income-expense" onclick="compareData('income_expense')" class="btn-compare px-3 py-2 text-sm rounded-lg border border-gray-300 text-dark-text hover:bg-gray-100">
                Inc. vs Exp.
            </button>
            <button id="btn-income-budget" onclick="compareData('income_budget')" class="btn-compare px-3 py-2 text-sm rounded-lg border border-gray-300 text-dark-text hover:bg-gray-100">
                Inc. vs Bud.
            </button>
            <button id="btn-budget-expense" onclick="compareData('budget_expense')" class="btn-compare px-3 py-2 text-sm rounded-lg border border-gray-300 text-dark-text hover:bg-gray-100">
                Bud. vs Exp.
            </button>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-6 mb-8">
            <h3 class="text-xl font-bold mb-4 text-center text-dark-text" id="chart-title"></h3>
            <div class="h-96 sm:h-[500px]">
                <canvas id="financialComparisonChart"></canvas>
            </div>
        </div>
        
    </main>

    <script>
        // JS for Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
            
            // Optional: Add a temporary overlay when sidebar is open on mobile (not required by the styles but good UX)
            // document.body.classList.toggle('overflow-hidden', !sidebar.classList.contains('-translate-x-full'));
        }
    </script>
    <script src="overview.js"></script>
</body>
</html>