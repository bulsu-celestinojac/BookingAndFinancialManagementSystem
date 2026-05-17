<?php
// FILE: notifications/index.php

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

// --- 1. Fetch all notifications (read and unread) ---
$query = "
    SELECT
        id, type, message, link, is_read, created_at
    FROM notifications
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($query);
$notifications = [];

if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
$stmt->close();

// --- 2. LOGIC FOR MARKING AS READ (Handles clicks from other pages) ---
$readId = $_GET['mark_read'] ?? null;
if ($readId) {
    // --- CORRECTED: Use the existing $conn object from db_config.php ---
    if ($readId === 'all') {
        $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    } else {
        $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $readId);
    }
    
    $update_stmt->execute();
    $update_stmt->close();
    
    // Redirect to clean the URL after the update (removes the mark_read parameter)
    header("Location: index.php");
    exit();
}

$conn->close(); // Close connection after all logic

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>System Notifications - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Consistent Resort Palette */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #f59e42; /* Main accent color */
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-main-bg { background-color: var(--color-main-bg); }
        .bg-card-bg { background-color: var(--color-card-bg); }
        .bg-sidebar-bg { background-color: var(--color-sidebar-bg); }
        .text-dark-text { color: var(--color-dark-text); }
        .text-light-text { color: var(--color-light-text); }
        .text-accent { color: var(--color-accent); }
        
        /* Highlight for new notifications */
        .notif-unread {
            background-color: #fffbeb; /* Yellow-50 */
            border-left: 4px solid #f59e42; /* Accent border */
            font-weight: 600;
        }
        /* Custom sidebar class for Notifications module */
        .sidebar-active-notif {
            color: var(--color-accent);
            background-color: #fff0d6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-main-bg font-sans antialiased text-dark-text flex">

    <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="fixed top-4 left-4 z-50 p-3 rounded-full bg-accent text-white lg:hidden shadow-lg transition-all duration-300">
        <i class="fas fa-bars text-xl"></i>
    </button>
    
<aside id="sidebar" class="w-64 flex-shrink-0 bg-sidebar-bg text-dark-text shadow-xl transition-transform duration-300 ease-in-out h-screen fixed top-0 left-0 z-40 transform -translate-x-full lg:translate-x-0">
        <div class="p-8 lg:p-10 border-b border-gray-300 text-center flex items-center justify-center">
            <img src="../dashboard/aleinahslogo.png" alt="Aleinah's Resort Logo" class="h-12 lg:h-16">
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        
        <nav class="mt-8 px-2 lg:px-5 flex flex-col h-[calc(100vh-160px)]">
            <ul class="space-y-4 flex-1 overflow-y-auto">
                <li><a href="../dashboard/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-home text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Dashboard</span></a></li>
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../budget/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-wallet text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Budget Record</span></a></li>
                <li><a href="../financial-overview/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-chart-line text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Financial Overview</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../payroll/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-users text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payroll System</span></a></li>
                <li><a href="../approval/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-check-double text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Data Approval</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li>
                <a href="../notifications/index.php"
                    class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg sidebar-active-notif">
                    
                    <div class="relative w-8 text-center">
                        <i id="notificationBell" class="fas fa-bell text-lg"></i>
                        
                        <span id="notificationBadge"
                                 class="absolute top-0 right-0 transform translate-x-1/2 -translate-y-1/2
                                        px-1 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full hidden">0</span>
                    </div>
                    
                    <span class="ml-4 font-medium hidden lg:inline">Notifications</span>
                </a>
                </li>

                <li><a href="../transaction/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-history text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Transaction History</span></a></li>
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

    <main class="flex-1 p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-dark-text">System Notifications History</h2>
                <p class="text-light-text mt-1">Review all system events, alerts, and transaction statuses.</p>
            </div>
            <button onclick="window.location.href='index.php?mark_read=all'" class="bg-gray-500 text-white px-4 py-2 rounded-full hover:bg-gray-600 transition-colors text-sm font-semibold">
                <i class="fas fa-check-double mr-2"></i>Mark All As Read
            </button>
        </header>
        
        <div class="bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text border-b pb-2">Alert History (<?= count($notifications) ?> Total)</h3>
            
            <div class="space-y-3">
                <?php if (empty($notifications)): ?>
                    <div class="p-5 text-center text-light-text">The notification log is currently empty.</div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <?php
                            $is_read_class = $notif['is_read'] == 0 ? 'notif-unread' : 'bg-white';
                            $icon_class = 'text-gray-500';
                            if (strpos($notif['type'], 'Approval') !== false) {
                                $icon_class = 'text-blue-500';
                            } elseif (strpos($notif['type'], 'Stock') !== false) {
                                $icon_class = 'text-yellow-500';
                            } elseif (strpos($notif['type'], 'Error') !== false || strpos($notif['type'], 'Failed') !== false) {
                                $icon_class = 'text-red-500';
                            }
                            
                            // Link includes the mark_read parameter to automatically update status when clicked
                            $target_link = htmlspecialchars($notif['link']) . (strpos($notif['link'], '?') === false ? '?' : '&') . 'mark_read=' . $notif['id'];
                        ?>
                        <a href="<?= $target_link ?>" class="block p-4 rounded-lg shadow-sm <?= $is_read_class ?> hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-circle text-xs <?= $icon_class ?>"></i>
                                    <span class="font-bold text-dark-text"><?= htmlspecialchars($notif['type']) ?></span>
                                    <?php if ($notif['is_read'] == 0): ?>
                                        <span class="text-xs font-bold text-red-600">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-light-text"><?= date('M j, g:i A', strtotime($notif['created_at'])) ?></span>
                            </div>
                            <p class="mt-1 text-sm text-dark-text ml-6"><?= htmlspecialchars($notif['message']) ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar close on link click (UX)
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