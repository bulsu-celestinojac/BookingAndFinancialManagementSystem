<?php
// FILE: restock.php
require '../../db_config.php'; // Using centralized config
require '../notifications/notification_trigger.php'; // Include notification function

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'] ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0); 
    // $restocked_by is removed

    if (empty($date) || $item_id <= 0 || $amount <= 0) {
        $error = "Error: Please fill out all fields correctly and ensure the amount is greater than zero.";
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            // 1. Get current stock level and details (Using FOR UPDATE for concurrency safety)
            $stmt = $conn->prepare("SELECT name, unit, stock_level, category FROM inventory WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            $stmt->close();

            if (!$item) {
                throw new Exception("Error: Item not found.");
            }

            // 2. Update stock level (Addition)
            $new_stock = $item['stock_level'] + $amount;
            $stmt = $conn->prepare("UPDATE inventory SET stock_level = ? WHERE id = ?");
            $stmt->bind_param("di", $new_stock, $item_id); // 'd' for double/float type
            $stmt->execute();
            $stmt->close();

            // 3. Record the restock history 
            // NOTE: Assumes you have a 'supply_restock' table
            $stmt = $conn->prepare("INSERT INTO supply_restock (item_id, date, amount_added) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $item_id, $date, $amount);
            $stmt->execute();
            $stmt->close();

            // 4. Commit Transaction
            $conn->commit();
            
            // 5. Fire Notification (Restock Confirmation)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $script_path = dirname($_SERVER['PHP_SELF']); 
            $admin_path = dirname($script_path); 
            $base_url = "{$protocol}://{$host}{$admin_path}";
            
            $type = "Inventory Restock";
            $message = "Stock of " . $item['name'] . " successfully increased by +" . number_format($amount, 2) . " " . $item['unit'] . ". New stock: " . number_format($new_stock, 0) . ".";
            $link = "{$base_url}/inventory/index.php?category=" . urlencode($item['category']);
            fireNotification($conn, $type, $message, $link);
            
            // ----------------------------------------
            
            // CRITICAL FIX: Add a unique timestamp (cache buster) to the redirect URL
            $timestamp = time();
            header("Location: index.php?success=restocked&item=" . urlencode($item['name']) . "&t=" . $timestamp);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Display simple error message
if ($error) {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Error</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-gray-100 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded-lg shadow-lg max-w-sm w-full text-center'><h2 class='text-xl font-bold text-red-600 mb-4'>Transaction Failed</h2><p class='text-gray-700 mb-6'>$error</p><button onclick='window.location.href=\"index.php\"' class='bg-red-600 text-white px-6 py-2 rounded-full hover:bg-red-700 font-semibold'>Go Back</button></div></body></html>";
} else {
    header("Location: index.php");
    exit();
}
?>