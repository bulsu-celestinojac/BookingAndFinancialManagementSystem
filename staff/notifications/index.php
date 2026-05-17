<?php
// FILE: notifications/index.php

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

require '../../db_config.php';

$loggedInUserId = $_SESSION['userid'];

// --- 1. Fetch all notifications (read and unread) for the CURRENT user ---
// The query is updated to filter by user_id and select necessary columns
$query = "
    SELECT
        id, message, record_id, record_type, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $loggedInUserId);
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
    if ($readId === 'all') {
        // Mark all notifications for the current user as read
        $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $update_stmt->bind_param("i", $loggedInUserId);
    } elseif (is_numeric($readId)) {
        // Mark a single notification as read, but only if it belongs to the current user
        $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("ii", $readId, $loggedInUserId);
    }
    
    if (isset($update_stmt)) {
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Redirect to clean the URL after the update (removes the mark_read parameter)
    header("Location: index.php");
    exit();
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow">
    <title>System Notifications - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #f59e42;
            --color-dark-text: #1f2937;
            --color-light-text: #6b7280;
        }
        .bg-main-bg { background-color: var(--color-main-bg); }
        .bg-card-bg { background-color: var(--color-card-bg); }
        .bg-sidebar-bg { background-color: var(--color-sidebar-bg); }
        .text-dark-text { color: var(--color-dark-text); }
        .text-light-text { color: var(--color-light-text); }
        .text-accent { color: var(--color-accent); }
        
        .notif-unread {
            background-color: #fffbeb;
            border-left: 4px solid #f59e42;
            font-weight: 600;
        }
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
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="../inventory/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
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
        <header class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-dark-text">System Notifications History</h2>
                <p class="text-light-text mt-1">Review all system events, alerts, and transaction statuses.</p>
            </div>
            <?php if (is_admin()): ?>
            <button onclick="window.location.href='index.php?mark_read=all'" class="bg-gray-500 text-white px-4 py-2 rounded-full hover:bg-gray-600 transition-colors text-sm font-semibold">
                <i class="fas fa-check-double mr-2"></i>Mark All As Read
            </button>
            <?php endif; ?>
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
                            // This logic needs to be updated to handle the new notification messages
                            if (strpos($notif['message'], 'approved') !== false) {
                                $icon_class = 'text-green-500'; // Approved messages are green
                            } elseif (strpos($notif['message'], 'declined') !== false) {
                                $icon_class = 'text-red-500'; // Declined messages are red
                            } elseif (strpos($notif['message'], 'Stock') !== false) {
                                $icon_class = 'text-yellow-500';
                            }
                            
                            $link = '';
                            if (!empty($notif['record_id']) && !empty($notif['record_type'])) {
                                $link = "../" . strtolower($notif['record_type']) . "/view.php?id=" . urlencode($notif['record_id']);
                            }
                            $target_link = $link ? $link . (strpos($link, '?') === false ? '?' : '&') . 'mark_read=' . $notif['id'] : 'index.php?mark_read=' . $notif['id'];
                        ?>
                        <a href="<?= htmlspecialchars($target_link) ?>" class="block p-4 rounded-lg shadow-sm <?= $is_read_class ?> hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-circle text-xs <?= $icon_class ?>"></i>
                                    <span class="font-bold text-dark-text">Notification</span>
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