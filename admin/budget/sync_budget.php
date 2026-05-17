<?php
// FILE: sync_budget.php (Handles Batch Insertion of Offline Records)
require '../../db_config.php';

// 1. Check method and content type
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request format.']);
    exit();
}

// 2. Decode JSON payload
$json_data = file_get_contents('php://input');
$records = json_decode($json_data, true);

if (!is_array($records) || empty($records)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No records provided for sync.']);
    exit();
}

$conn->begin_transaction();
$successful_keys = [];

try {
    // Prepare the SQL statement for insertion
    $stmt = $conn->prepare("INSERT INTO budget (date, category, amount, description, approved_by) VALUES (?, ?, ?, ?, ?)");

    foreach ($records as $key => $record) {
        // Simple data validation
        if (empty($record['date']) || empty($record['category']) || empty($record['amount']) || empty($record['approved_by'])) {
            error_log("Skipping invalid record during sync: " . json_encode($record));
            continue; 
        }

        // Bind parameters
        $stmt->bind_param("ssdss", 
            $record['date'], 
            $record['category'], 
            $record['amount'], 
            $record['description'], 
            $record['approved_by']
        );
        
        if ($stmt->execute()) {
            $successful_keys[] = $key;
        } else {
            error_log("DB error during sync for key $key: " . $stmt->error);
        }
    }

    $conn->commit();
    $stmt->close();
    
    // Respond with keys that were successfully inserted 
    echo json_encode(['success' => true, 'synced_keys' => $successful_keys]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error during transaction.']);
    exit();
}
?>