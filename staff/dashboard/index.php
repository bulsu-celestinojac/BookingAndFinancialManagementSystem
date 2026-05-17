<?php
// Start the session at the very beginning of the script
session_start();

// CRITICAL SECURITY: Include the database config from the project root (two levels up)
require_once '../../db_config.php';

// --- NEW SECURITY LOGIC ---
// 1. Check if the user is authenticated at all.
if (!isset($_SESSION['userid'])) {
    // If not logged in, redirect them to the main login page
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

// 2. Check if the authenticated user has the correct role for this page.
if ($_SESSION['role'] !== 'admin') {
    // If the role is not 'admin', deny access and redirect
    header("Location: ../../index.php?error=permissiondenied");
    exit();
}

// --- END OF NEW SECURITY LOGIC ---

// Include the data-fetching functions file
include 'data_fetch.php';

// Call the function to populate the $data array
$data = getDashboardKPIs();

$currentMonthName = date('F');
$currentYear = date('Y');

// Utility function for currency formatting (Philippines Pesos)
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Determine Net Profit Color and Icon
$netProfitClass = $data['netProfit'] >= 0 ? 'text-green-600' : 'text-red-600';
$netProfitIcon = $data['netProfit'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';

// Determine Budget Utilization Color
$budgetUtilClass = $data['budgetUtilization'] < 100 ? 'text-green-600' : 'text-red-600';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Aleinah's Resort Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        /* Light & Modern Color Palette for Private Resort - Consistent */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #f59e42; /* Orange for Dashboard highlight */
            --color-accent-light: #fef3c7;
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

        /* FIX: Prevents the Chart.js canvas from causing horizontal overflow */
        .chart-container {
            width: 100%;
            height: 100%;
            min-width: 0; /* Ensures container can shrink in a flex context */
        }
    /* ... (Existing Tailwind and Custom CSS) ... */

    /* FIX: Prevents the Chart.js canvas from causing horizontal overflow */
    .chart-container {
        width: 100%;
        height: 100%;
        min-width: 0; /* Ensures container can shrink in a flex context */
    }

    /* --- NEW AI TYPING INDICATOR CSS --- */
    .dot-flashing {
        /* This whole block creates the animated dots effect */
        position: relative;
        text-align: center;
        width: 15px; /* Control space */
        height: 10px;
        display: inline-block;
        font-size: 20px;
        font-weight: bold;
        color: var(--color-dark-text);
        margin-right: 15px;
    }

    .dot-flashing::before, .dot-flashing::after {
        content: '.';
        position: absolute;
        animation: dotFlashing 1s infinite linear alternate;
        top: -6px; 
    }

    .dot-flashing::before {
        left: -15px;
        animation-delay: 0s;
    }

    .dot-flashing::after {
        left: 0px;
        animation-delay: 0.2s;
    }
    
    /* Third dot uses the main element */
    .dot-flashing {
        left: 15px;
        animation-delay: 0.4s;
    }
    
    @keyframes dotFlashing {
        0% { opacity: 0.2; }
        50% { opacity: 1; }
        100% { opacity: 0.2; }
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
        <nav class="mt-8 px-2 lg:px-5 h-[calc(100vh-160px)] overflow-y-auto">
            <ul class="space-y-4">
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../payroll/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
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

    <main class="flex-1 p-6 transition-all duration-300 ease-in-out lg:ml-64 overflow-x-hidden">
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-dark-text">Resort Dashboard</h2>
                <p class="text-light-text mt-1">Key performance indicators and operational status for **<?= $currentMonthName ?> <?= $currentYear ?>**.</p>
            </div>
        </header>

        <h3 class="text-xl font-semibold mb-4 border-b pb-2 text-dark-text flex items-center"><i class="fas fa-chart-area mr-2 text-accent"></i>Financial Summary</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-[#56b4d3]">
                <p class="text-sm font-medium text-light-text">Total Income (Current Mo.)</p>
                <p class="text-3xl font-bold text-dark-text mt-2"><?= formatCurrency($data['totalIncome']) ?></p>
                <div class="text-sm mt-3 text-[#56b4d3]"><i class="fas fa-coins mr-1"></i> View Income Records</div>
            </div>

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-red-500">
                <p class="text-sm font-medium text-light-text">Total Expenses (Current Mo.)</p>
                <p class="text-3xl font-bold text-dark-text mt-2"><?= formatCurrency($data['totalExpense']) ?></p>
                <div class="text-sm mt-3 text-red-500"><i class="fas fa-chart-pie mr-1"></i> View Expense Records</div>
            </div>

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-gray-400">
                <p class="text-sm font-medium text-light-text">Net Profit (Current Mo.)</p>
                <p class="text-3xl font-bold <?= $netProfitClass ?> mt-2">
                    <i class="fas <?= $netProfitIcon ?> text-lg mr-2"></i><?= formatCurrency(abs($data['netProfit'])) ?>
                </p>
                <div class="text-sm mt-3 text-light-text">Profitability Check</div>
            </div>

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-amber-500">
                <p class="text-sm font-medium text-light-text">Budget Utilization</p>
                <p class="text-3xl font-bold <?= $budgetUtilClass ?> mt-2">
                    <?= number_format($data['budgetUtilization'], 1) ?>%
                </p>
                <div class="text-sm mt-3 text-amber-500"><i class="fas fa-wallet mr-1"></i> Go to Budget Record</div>
            </div>
        </div>

        <div class="bg-card-bg rounded-xl shadow-xl p-6 mb-8">
            <h3 id="chart-title" class="text-xl font-semibold mb-4 text-dark-text">Yearly Financial Trend (Income vs. Expense)</h3>
            <div class="h-96 chart-container">
                <canvas id="financialTrendChart"></canvas>
            </div>
        </div>

        <h3 class="text-xl font-semibold mb-4 border-b pb-2 text-dark-text flex items-center"><i class="fas fa-cogs mr-2 text-accent"></i>Operational Status</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
                <p class="text-sm font-medium text-light-text">Low Stock Alert</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">
                    <?= $data['lowStockCount'] ?> Items
                </p>
                <div class="text-sm mt-3 text-yellow-600"><i class="fas fa-exclamation-triangle mr-1"></i> Reorder Soon</div>
            </div>

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-red-700">
                <p class="text-sm font-medium text-light-text">Out of Stock</p>
                <p class="text-3xl font-bold text-red-700 mt-2">
                    <?= $data['outOfStockCount'] ?> Items
                </p>
                <div class="text-sm mt-3 text-red-700"><i class="fas fa-times-circle mr-1"></i> Critical Shortage</div>
            </div>

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
                <p class="text-sm font-medium text-light-text">Active Employees</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">
                    <?= $data['activeEmployees'] ?> Staff
                </p>
                <div class="text-sm mt-3 text-blue-600"><i class="fas fa-users mr-1"></i> Go to Payroll System</div>
            </div>

            <div class="bg-card-bg p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                <p class="text-sm font-medium text-light-text">Last Payroll (Net)</p>
                <p class="text-3xl font-bold text-green-600 mt-2">
                    <?= formatCurrency($data['lastPayrollTotal']) ?>
                </p>
                <div class="text-sm mt-3 text-green-600"><i class="fas fa-calendar-check mr-1"></i> Date: <?= htmlspecialchars($data['lastPayrollDate']) ?></div>
            </div>
        </div>
    </main>
    
    <div id="ai-chat-container" class="z-[9999]">
        <button id="aiChatButton" 
                class="fixed bottom-6 right-6 z-50 p-4 rounded-full shadow-2xl bg-accent text-white hover:bg-orange-600 transition-all duration-300 transform hover:scale-110">
            <i class="fas fa-robot text-2xl"></i> 
        </button>

        <div id="aiChatWindow" 
             class="fixed bottom-20 right-6 w-80 h-96 bg-card-bg rounded-xl shadow-2xl border border-gray-300 flex flex-col hidden z-50">
            
            <div class="p-4 bg-accent text-white rounded-t-xl flex justify-between items-center">
                <h4 class="font-semibold">AI Assistant</h4>
                <button onclick="document.getElementById('aiChatWindow').classList.add('hidden')" 
                        class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="chatMessages" class="flex-1 p-4 overflow-y-auto space-y-3">
                <div class="text-light-text text-sm">Hello! I'm your assistant. How can I help manage Aleinah's Resort today?</div>
            </div>

            <div class="p-3 border-t border-gray-200 flex">
                <input type="text" id="chatInput" 
                        placeholder="Ask me anything..." 
                        class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
                <button id="chatSendButton" 
                        class="ml-2 px-4 py-2 bg-accent text-white rounded-lg hover:bg-orange-600">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    <script src="dashboard.js"></script>
    <script>
        // Mobile sidebar close on link click (UX improvement)
        document.addEventListener('DOMContentLoaded', function() {
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