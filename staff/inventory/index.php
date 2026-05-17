<?php
// FILE: index.php (Supply Inventory)

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

require '../../db_config.php'; // Using centralized config

$selectedCategory = $_GET['category'] ?? '';

// --- Fetch Data for Filters and Cards ---
$categoriesResult = $conn->query("SELECT DISTINCT category FROM inventory ORDER BY category ASC");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row['category'];
}
$categoriesResult->close();

// --- SQL Filtering (Using Prepared Statements for security) ---
$where = [];
$params = [];
$types = "";

if (!empty($selectedCategory)) {
    $where[] = "category = ?";
    $params[] = $selectedCategory;
    $types .= "s";
}

$filterSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// --- Prepare the main query with ordering ---
$query = "SELECT * FROM inventory $filterSQL ORDER BY
            CASE WHEN stock_level <= low_stock_threshold THEN 0 ELSE 1 END,
            stock_level ASC, name ASC"; // Prioritize low stock items

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- Status Counts (for cards) ---
$lowStockCount = $conn->query("SELECT COUNT(*) FROM inventory WHERE stock_level > 0 AND stock_level <= low_stock_threshold")->fetch_row()[0];
$outOfStockCount = $conn->query("SELECT COUNT(*) FROM inventory WHERE stock_level = 0")->fetch_row()[0];

// --- Fetch items for Usage/Restock Modal Dropdown (Keep results open until end) ---
$itemsResult = $conn->query("SELECT id, name, unit, stock_level FROM inventory ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow">
    <title>Supply Inventory - Aleinah's Resort</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Light & Modern Color Palette for Private Resort - Consistent */
        :root {
            --color-main-bg: #ffffff;
            --color-card-bg: #f8fafc;
            --color-sidebar-bg: #e2e8f0;
            --color-accent: #14b8a6; /* A clean teal for Inventory */
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
            <h1 class="text-2xl font-extrabold text-dark-text hidden lg:block ml-4">ALEINAH'S RESORT</h1>
        </div>
        <nav class="mt-8 px-2 lg:px-5 flex flex-col h-[calc(100vh-160px)]">
            <ul class="space-y-4 flex-1 overflow-y-auto">
                <li><a href="../income/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-coins text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Income Record</span></a></li>
                <li><a href="../expense/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-chart-pie text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Expense Record</span></a></li>
                <li><a href="index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg text-accent bg-accent-light shadow-md transition-colors duration-200"><i class="fas fa-boxes text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Supply Inventory</span></a></li>
                <li><a href="../payment/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-money-check-alt text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Payment Record</span></a></li>
                <li><a href="../notifications/index.php" class="flex items-center justify-center lg:justify-start px-2 py-3 rounded-lg hover:bg-accent-light transition-colors duration-200"><i class="fas fa-bell text-lg w-8 text-center"></i><span class="ml-4 font-medium hidden lg:inline">Notifications</span></a></li>
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

    <main class="flex-1 p-4 lg:p-6 transition-all duration-300 ease-in-out lg:ml-64">
        <header class="flex flex-col md:flex-row items-start justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h2 class="text-3xl font-bold text-dark-text">Supply Inventory</h2>
                <p class="text-light-text mt-1">Manage all resort supplies and track stock levels.</p>
            </div>
            <div class="flex flex-wrap gap-2 justify-end w-full md:w-auto">
                <?php if (is_admin()): ?>
                <button onclick="document.getElementById('restockModal').classList.remove('hidden')" class="flex items-center px-4 py-2 bg-accent text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Record Restock
                </button>
                <?php endif; ?>
                <button onclick="document.getElementById('usageModal').classList.remove('hidden')" class="flex items-center px-4 py-2 bg-yellow-600 text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm">
                    <i class="fas fa-minus-circle mr-2"></i>Record Usage
                </button>
                <?php if (is_admin()): ?>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="flex items-center px-4 py-2 bg-accent text-white rounded-full hover:opacity-80 transition-colors duration-200 shadow-sm">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
                <?php endif; ?>
            </div>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-card-bg rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-dark-text">Total Items</h3>
                <p class="text-4xl font-bold text-accent mt-2"><?php echo $conn->query("SELECT COUNT(*) FROM inventory")->fetch_row()[0]; ?></p>
                <p class="text-sm text-light-text mt-1">Unique supply items tracked.</p>
            </div>
            <div class="bg-card-bg rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                <h3 class="text-lg font-semibold text-dark-text">Low Stock Alert</h3>
                <p class="text-4xl font-bold text-yellow-500 mt-2"><?php echo $lowStockCount; ?></p>
                <p class="text-sm text-light-text mt-1">Items below threshold.</p>
            </div>
            <div class="bg-card-bg rounded-xl shadow-md p-6 border-l-4 border-red-500">
                <h3 class="text-lg font-semibold text-dark-text">Out of Stock</h3>
                <p class="text-4xl font-bold text-red-500 mt-2"><?php echo $outOfStockCount; ?></p>
                <p class="text-sm text-light-text mt-1">Items that need immediate reorder.</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <form method="GET" class="flex flex-wrap gap-2 mb-4 md:mb-0 items-center" id="filterForm" autocomplete="off">
                <label for="categorySelect" class="sr-only">Category</label>
                <select id="categorySelect" name="category" class="px-4 py-2 rounded-full border bg-white focus:outline-none focus:ring-2 focus:ring-accent text-dark-text">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($selectedCategory == $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="usage_record.php" class="text-accent hover:underline text-sm font-medium mt-2 md:mt-0">
                <i class="fas fa-history mr-1"></i>View Supply Usage History
            </a>
        </div>

        <div class="bg-card-bg rounded-xl shadow-md p-4 mb-6">
            <h3 class="text-xl font-semibold mb-4 text-dark-text">Detailed Inventory</h3>
            
            <div class="hidden md:block overflow-x-auto">
                <table id="inventoryTable" class="w-full table-auto">
                    <thead class="bg-card-bg">
                        <tr>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tl-lg">Item Name</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Category</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Stock Level</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Unit</th>
                            <th class="p-3 text-left font-semibold text-dark-text">Threshold</th>
                            <?php if (is_admin()): ?>
                            <th class="p-3 text-left font-semibold text-dark-text rounded-tr-lg">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()):
                            $statusClass = 'text-green-600';
                            if ($row['stock_level'] <= 0) {
                                $statusClass = 'text-red-600 font-bold';
                            } elseif ($row['stock_level'] <= $row['low_stock_threshold']) {
                                $statusClass = 'text-yellow-600 font-medium';
                            }
                        ?>
                        <tr class='border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150'>
                            <td class='p-3 font-medium'><?= htmlspecialchars($row['name']) ?></td>
                            <td class='p-3 text-light-text'><?= htmlspecialchars($row['category']) ?></td>
                            <td class='p-3 <?= $statusClass ?>'><?= htmlspecialchars($row['stock_level']) ?></td>
                            <td class='p-3 text-light-text'><?= htmlspecialchars($row['unit']) ?></td>
                            <td class='p-3 text-light-text'><?= htmlspecialchars($row['low_stock_threshold']) ?></td>
                            <?php if (is_admin()): ?>
                            <td class='p-3 space-x-2'>
                                <a href='edit_supply.php?id=<?= $row['id'] ?>' class='text-accent hover:underline text-sm font-medium'>Edit</a>
                                <a href='delete.php?id=<?= $row['id'] ?>' onclick='return confirm("Are you sure you want to delete this supply?")' class='text-red-500 hover:underline text-sm font-medium'>Delete</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; $result->data_seek(0); // Reset for card view ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-4">
                <?php while ($row = $result->fetch_assoc()):
                    $statusClass = 'text-green-600';
                    $statusIcon = 'fas fa-check-circle';
                    if ($row['stock_level'] <= 0) {
                        $statusClass = 'text-red-600 font-bold';
                        $statusIcon = 'fas fa-times-circle';
                    } elseif ($row['stock_level'] <= $row['low_stock_threshold']) {
                        $statusClass = 'text-yellow-600 font-medium';
                        $statusIcon = 'fas fa-exclamation-triangle';
                    }
                ?>
                <div class='bg-white p-4 rounded-lg shadow-sm border border-gray-200'>
                    <div class='flex justify-between items-start mb-2'>
                        <span class='text-md font-bold text-dark-text'><?= htmlspecialchars($row['name']) ?></span>
                        <span class='text-xs font-semibold uppercase text-light-text'><?= htmlspecialchars($row['category']) ?></span>
                    </div>
                    <div class='text-2xl font-bold <?= $statusClass ?> mb-2 flex items-center'>
                        <i class='<?= $statusIcon ?> text-lg mr-2'></i>
                        <?= htmlspecialchars($row['stock_level']) ?> <span class='text-base font-normal ml-1'><?= htmlspecialchars($row['unit']) ?></span>
                    </div>
                    <div class='flex justify-between items-center text-xs text-light-text pt-2 border-t border-gray-100'>
                        <span class='font-medium'>Threshold: <?= htmlspecialchars($row['low_stock_threshold']) ?></span>
                        <?php if (is_admin()): ?>
                        <div class='space-x-3'>
                            <a href='edit_supply.php?id=<?= $row['id'] ?>' class='text-accent hover:underline font-semibold'>Edit</a>
                            <a href='delete.php?id=<?= $row['id'] ?>' onclick='return confirm("Are you sure?")' class='text-red-500 hover:underline font-semibold'>Delete</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <?php if (is_admin()): ?>
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-card-bg p-8 rounded-xl shadow-lg w-full max-w-md">
            <h2 class="text-2xl font-semibold mb-6 text-dark-text text-center">Add New Supply</h2>
            <form class="space-y-4" action="add_supply.php" method="POST">
                <input type="text" name="name" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Item Name">
                <select name="category" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
                    <option value="">Select Category</option>
                    <option value="Cleaning">Cleaning</option>
                    <option value="Food & Beverage">Food & Beverage</option>
                    <option value="Linens">Linens</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Office">Office</option>
                    <option value="Other">Other</option>
                </select>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" name="stock_level" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Current Stock" min="0">
                    <input type="text" name="unit" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Unit (e.g., pcs, liters)">
                </div>
                <input type="number" name="low_stock_threshold" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Low Stock Threshold" min="1">
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">Save Supply</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="restockModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-card-bg p-8 rounded-xl shadow-lg w-full max-w-md transform transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-dark-text text-center">Record Supply Restock</h2>
            <form class="space-y-4" action="restock.php" method="POST">
                <div>
                    <label for="restock_date" class="block mb-1 font-semibold text-light-text">Date of Restock</label>
                    <input type="date" id="restock_date" name="date" value="<?= date('Y-m-d') ?>" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
                </div>
                <div>
                    <label for="restock_item_id" class="block mb-1 font-semibold text-light-text">Supply Item</label>
                    <select id="restock_item_id" name="item_id" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
                        <option value="">Select Supply Item</option>
                        <?php
                        // Reset the result pointer for the items dropdown
                        $itemsResult->data_seek(0);
                        while ($item = $itemsResult->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($item['id']) ?>"
                                data-unit="<?= htmlspecialchars($item['unit']) ?>"
                                data-stock="<?= htmlspecialchars($item['stock_level']) ?>">
                                <?= htmlspecialchars($item['name']) ?> (Current: <?= htmlspecialchars($item['stock_level']) ?> <?= htmlspecialchars($item['unit']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="restock_amount" class="block mb-1 font-semibold text-light-text">Amount Added</label>
                    <input type="number" id="restock_amount" name="amount" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Enter quantity restocked" min="1" step="any">
                    <p id="restock_unit_display" class="text-sm text-light-text mt-1 hidden">Unit: <span class="font-bold"></span></p>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('restockModal').classList.add('hidden')" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">Confirm Restock</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div id="usageModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-card-bg p-8 rounded-xl shadow-lg w-full max-w-md transform transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-dark-text text-center">Record Supply Usage</h2>
            <form class="space-y-4" action="add_usage.php" method="POST">
                
                <div>
                    <label for="usage_date" class="block mb-1 font-semibold text-light-text">Date of Usage</label>
                    <input type="date" id="usage_date" name="date" value="<?= date('Y-m-d') ?>" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-yellow-600" required>
                </div>

                <div>
                    <label for="item_id" class="block mb-1 font-semibold text-light-text">Supply Item</label>
                    <select id="item_id" name="item_id" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-yellow-600" required>
                        <option value="">Select Supply Item</option>
                        <?php
                        $itemsResult->data_seek(0);
                        while ($item = $itemsResult->fetch_assoc()):
                        ?>
                            <option
                                value="<?= htmlspecialchars($item['id']) ?>"
                                data-unit="<?= htmlspecialchars($item['unit']) ?>"
                                data-stock="<?= htmlspecialchars($item['stock_level']) ?>">
                                <?= htmlspecialchars($item['name']) ?> (Stock: <?= htmlspecialchars($item['stock_level']) ?> <?= htmlspecialchars($item['unit']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="usage_amount" class="block mb-1 font-semibold text-light-text">Amount Used</label>
                    <input type="number" id="usage_amount" name="amount" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-yellow-600" required placeholder="Enter quantity used" min="0.01" step="any">
                    <p id="unit_display" class="text-sm text-light-text mt-1 hidden">Unit: <span class="font-bold"></span></p>
                </div>

                <div>
                    <label for="used_by" class="block mb-1 font-semibold text-light-text">Used By/Area</label>
                    <select id="used_by" name="used_by" class="w-full border p-3 rounded-lg bg-white text-dark-text focus:outline-none focus:ring-2 focus:ring-yellow-600" required>
                        <option value="">Select Area/Department</option>
                        <option value="Guest Rooms">Guest Rooms</option>
                        <option value="Kitchen/Restaurant">Kitchen/Restaurant</option>
                        <option>Pool/Outdoor Area</option>
                        <option>Office/Admin</option>
                        <option>Maintenance</option>
                        <option>Other</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="document.getElementById('usageModal').classList.add('hidden')" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-full hover:opacity-80 font-semibold">Record & Deduct</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../notifications/notifications.js"></script>
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
            document.getElementById('categorySelect')?.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            // Usage Modal Logic (Display unit dynamically)
            const itemSelect = document.getElementById('item_id');
            const unitDisplay = document.getElementById('unit_display');
            const unitSpan = unitDisplay?.querySelector('span');

            const restockItemSelect = document.getElementById('restock_item_id');
            const restockUnitDisplay = document.getElementById('restock_unit_display');
            const restockUnitSpan = restockUnitDisplay?.querySelector('span');


            if (itemSelect) {
                itemSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const unit = selectedOption.getAttribute('data-unit');
                    const stock = parseFloat(selectedOption.getAttribute('data-stock'));
                    const amountInput = document.getElementById('usage_amount');

                    if (unit && unitDisplay) {
                        unitSpan.textContent = unit;
                        unitDisplay.classList.remove('hidden');
                    } else if (unitDisplay) {
                        unitDisplay.classList.add('hidden');
                    }

                    // Set max attribute to prevent over-deduction (basic client-side check)
                    if (amountInput) {
                        amountInput.setAttribute('max', stock);
                        amountInput.placeholder = `Max: ${stock} ${unit || ''}`;
                    }
                });
            }

            // Restock Modal Logic (Display unit dynamically)
            if (restockItemSelect) {
                restockItemSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const unit = selectedOption.getAttribute('data-unit');
                    // const stock = parseFloat(selectedOption.getAttribute('data-stock')); // Not needed for restock
                    const amountInput = document.getElementById('restock_amount');

                    if (unit && restockUnitDisplay) {
                        restockUnitSpan.textContent = unit;
                        restockUnitDisplay.classList.remove('hidden');
                    } else if (restockUnitDisplay) {
                        restockUnitDisplay.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>