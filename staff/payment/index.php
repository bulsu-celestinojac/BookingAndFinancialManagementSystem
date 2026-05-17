<?php
// FILE: payment/index.php

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
// 1. Check if the user is authenticated and has the correct role
if (!is_admin_or_staff()) {
    header("Location: ../../index.php?error=permissiondenied");
    exit();
}
// --- END OF SECURITY LOGIC ---

require_once '../../db_config.php';

// NOTE: It's highly recommended to store API keys in a more secure way,
// e.g., in a file outside the web root or as environment variables.
// For now, this placeholder is used for demonstration.
define('PAYMONGO_SECRET_KEY', "HIDEN_FOR_PORTFOLIO"); // Replace with your actual PayMongo Secret Key

// Utility function for currency formatting (Philippines Pesos)
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// --- Part 1: Handle synchronization status message from sync_paymongo.php redirect ---
$syncMessage = '';
if (isset($_GET['sync_status']) && $_GET['sync_status'] === 'success') {
    $count = (int)($_GET['count'] ?? 0);
    if ($count > 0) {
        $syncMessage = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">' . $count . ' PAID PayMongo payments were successfully imported into the local Income Record.</span>
        </div>';
    } else {
        $syncMessage = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">No New Payments Synced.</strong>
            <span class="block sm:inline">No new "PAID" payments were found to import from the displayed list.</span>
        </div>';
    }
}

// --- Part 2: Fetch and display data directly from PayMongo ---
$paymongoPayments = [];
$url = "https://api.paymongo.com/v1/payments?limit=20"; // Fetch up to 20 payments for display

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    // CRITICAL: Use the same API Key constant defined in the other file
    "Authorization: Basic " . base64_encode(PAYMONGO_SECRET_KEY . ":")
]);

$response = curl_exec($ch);
curl_close($ch);

$paymentsData = json_decode($response, true);

if (isset($paymentsData['data'])) {
    foreach ($paymentsData['data'] as $payment) {
        $attributes = $payment['attributes'];
        
        // Determine the payment method robustly
        $paymentMethod = $attributes['source']['type'] ?? ($attributes['payments'][0]['attributes']['source']['type'] ?? 'N/A');
        
        $paymongoPayments[] = [
            'id' => $payment['id'],
            'amount' => $attributes['amount'] / 100, // PayMongo amount is in cents, convert to pesos
            'email' => $attributes['billing']['email'] ?? 'N/A',
            'name' => $attributes['billing']['name'] ?? 'N/A',
            'status' => $attributes['status'],
            'payment_method' => $paymentMethod,
            'created_at' => date('Y-m-d H:i:s', $attributes['created_at'])
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow">
    <title>Payment Record - Aleinah's Resort Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">

    <button id="sidebarToggle" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="fixed top-4 left-4 z-50 p-3 rounded-full bg-accent text-white lg:hidden shadow-lg transition-all duration-300">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <aside id="sidebar" class="w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0 z-40 transform -translate-x-full lg:translate-x-0">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
            <img src="../dashboard/aleinahslogo.png" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5 flex flex-col h-[calc(100vh-160px)]">
            <ul class="space-y-4 flex-1 overflow-y-auto">
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-bell text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Notifications</span></a></li>
                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
                <li class="mt-auto pt-4 border-t border-gray-300">
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
                <h2 class="text-3xl font-bold text-dark-text">Payment Record</h2>
                <p class="text-light-text mt-1">External payment transactions fetched live from PayMongo.</p>
            </div>
            <?php if (is_admin()): ?>
            <button id="syncButton" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-5 rounded-lg shadow-lg transition-colors duration-200">
                <i class="fas fa-sync-alt mr-2"></i> Sync & Import Payments
            </button>
            <?php endif; ?>
        </header>

        <?php if (!empty($syncMessage)): ?>
            <?= $syncMessage ?>
        <?php endif; ?>

        <div class="bg-card-bg rounded-xl shadow-xl p-6 mb-8 overflow-x-auto">
            <h3 class="text-xl font-semibold mb-4 text-dark-text border-b pb-2 flex items-center"><i class="fas fa-credit-card mr-2 text-accent"></i>PayMongo Transaction Feed</h3>
            
            <table id="paymongoTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">Customer Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-light-text uppercase tracking-wider">Created At</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($paymongoPayments)): ?>
                        <?php foreach ($paymongoPayments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-dark-text"><?= htmlspecialchars($payment['id']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dark-text"><?= htmlspecialchars($payment['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dark-text"><?= htmlspecialchars($payment['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-dark-text"><?= formatCurrency($payment['amount']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-dark-text"><?= htmlspecialchars(strtoupper($payment['payment_method'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $status = strtolower($payment['status']);
                                        $class = 'status-other bg-gray-100 text-gray-800';
                                        if ($status === 'paid') {
                                            $class = 'status-paid bg-green-100 text-green-800';
                                        } elseif ($status === 'pending') {
                                            $class = 'status-pending bg-yellow-100 text-yellow-800';
                                        } elseif ($status === 'failed' || $status === 'voided') {
                                            $class = 'status-failed bg-red-100 text-red-800';
                                        }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $class ?>">
                                        <?= htmlspecialchars(ucfirst($status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-light-text"><?= htmlspecialchars($payment['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-light-text">No PayMongo transactions found. Check your API key and connection.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script src="paymongo_sync.js"></script>
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