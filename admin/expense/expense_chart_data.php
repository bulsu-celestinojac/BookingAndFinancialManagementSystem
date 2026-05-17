<?php
// File: expense_chart_data.php
// PURPOSE: Provides JSON data for the monthly expense chart.

require_once '../../db_config.php'; // Corrected secure path (two levels up)

header('Content-Type: application/json'); // Set header first

// Get filter parameters, defaulting to current year
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : null; 

$labels = [];
$amounts = [];
$chartType = 'monthly'; // FIX: Default chartType is set here

// Check for connection error
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['labels' => [], 'amounts' => [], 'error' => 'Database connection failed.']);
    exit;
}

// Prepare the statement once outside the loop for efficiency and security
$stmt = $conn->prepare("SELECT SUM(amount) FROM expenses WHERE MONTH(date)=? AND YEAR(date)=?");

for ($m = 1; $m <= 12; $m++) {
    $labels[] = date('F', mktime(0, 0, 0, $m, 1));
    
    // Bind parameters for the current month and year
    $stmt->bind_param("ii", $m, $year);
    $stmt->execute();
    
    // Bind the result variable
    $stmt->bind_result($sum);
    $stmt->fetch();
    
    // Store the summed amount (0 if NULL/no records found)
    $amounts[] = $sum ? floatval($sum) : 0;
}
$stmt->close();


// FIX: Included 'chartType' in the final JSON output
echo json_encode([
    'labels' => $labels,
    'amounts' => $amounts,
    'chartType' => $chartType // CRITICAL: This line was missing or misplaced
]);
?>