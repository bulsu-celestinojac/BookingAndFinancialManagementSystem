<?php
require '../../db_config.php'; // Using centralized config
require '../notifications/notification_trigger.php'; // CRITICAL: Include the notification trigger function

$error = null;

// --- CRITICAL FIX: DYNAMIC BASE URL ---
// This ensures the link works regardless of the server path (localhost/caps/flesvw2/admin)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Calculate the base path (e.g., /caps/flesvw2/admin/) dynamically from the current script location
$script_path = dirname($_SERVER['PHP_SELF']); 
// Since add_usage.php is in /inventory/, we go up one level to /admin/
$admin_path = dirname($script_path); 
$base_url = "{$protocol}://{$host}{$admin_path}";
// ---------------------------------------


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'] ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0); // Use float for step="any"
    $used_by = $_POST['used_by'] ?? '';

    if (empty($date) || $item_id <= 0 || $amount <= 0 || empty($used_by)) {
        $error = "Error: Please fill out all fields correctly and ensure the amount is greater than zero.";
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            // 1. Get current item details (Using FOR UPDATE for concurrency safety)
            $stmt = $conn->prepare("SELECT name, unit, stock_level, low_stock_threshold, category FROM inventory WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            $stmt->close();

            if (!$item) {
                throw new Exception("Error: Item not found.");
            }

            if ($amount > $item['stock_level']) {
                throw new Exception("Error: Insufficient stock. Current stock is " . number_format($item['stock_level'], 2) . ".");
            }

            // 2. Update stock level and calculate the new stock
            $new_stock = $item['stock_level'] - $amount;
            $stmt = $conn->prepare("UPDATE inventory SET stock_level = ? WHERE id = ?");
            $stmt->bind_param("di", $new_stock, $item_id);
            $stmt->execute();
            $stmt->close();

            // 3. Record the usage history
            $stmt = $conn->prepare("INSERT INTO supply_usage (item_id, date, amount_used, used_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $item_id, $date, $amount, $used_by);
            $stmt->execute();
            $stmt->close();

            // 4. Commit Transaction
            $conn->commit();
            
            // --- Notification Trigger Section ---
            
            // Determine month/year for usage_record link
            $link_month = date('n', strtotime($date));
            $link_year = date('Y', strtotime($date));
            
            // 5. Fire General Usage Notification
            $type = "Supply Usage";
            $message = "Usage recorded: -" . number_format($amount, 2) . " " . $item['unit'] . " of " . $item['name'] . " used by " . $used_by . ".";
            
            // FIXED LINK: Using Full Dynamic URL
            $link = "{$base_url}/inventory/usage_record.php?month={$link_month}&year={$link_year}";
            fireNotification($conn, $type, $message, $link);
            
            // 6. Fire Status Alert Notification (Out of Stock OR Low Stock)
            if ($new_stock <= 0) {
                 // ** OUT OF STOCK ALERT **
                 $type = "STOCKOUT ALERT";
                 $message = "🚨 CRITICAL: " . $item['name'] . " is now **OUT OF STOCK** and needs immediate reorder!";
                 // FIXED LINK: Using Full Dynamic URL
                 $link = "{$base_url}/inventory/index.php?category=" . urlencode($item['category']);
                 fireNotification($conn, $type, $message, $link);
            } elseif ($new_stock <= $item['low_stock_threshold']) {
                 // ** LOW STOCK ALERT **
                 $type = "Low Stock Alert";
                 $message = $item['name'] . " is running low! Stock is at " . number_format($new_stock, 0) . " " . $item['unit'] . ".";
                 // FIXED LINK: Using Full Dynamic URL
                 $link = "{$base_url}/inventory/index.php?category=" . urlencode($item['category']);
                 fireNotification($conn, $type, $message, $link);
            }
            // ----------------------------------------
            
            header("Location: index.php?success=usage_recorded&item=" . urlencode($item['name']));
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Display simple error message (mobile-friendly)
if ($error) {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Error</title><script src='https://cdn.tailwindcss.com'></script><style>.bg-red-600 {background-color: #dc2626;}.text-red-600 {color: #dc2626;}</style></head><body class='bg-gray-100 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded-lg shadow-lg max-w-sm w-full text-center'><h2 class='text-xl font-bold text-red-600 mb-4'>Transaction Failed</h2><p class='text-gray-700 mb-6'>$error</p><button onclick='window.location.href=\"index.php\"' class='bg-red-600 text-white px-6 py-2 rounded-full hover:bg-red-700 font-semibold'>Go Back</button></div></body></html>";
} else {
    header("Location: index.php");
    exit();
}
?>