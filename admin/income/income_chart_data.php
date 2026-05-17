<?php
// File: income_chart_data.php
// PURPOSE: Provides JSON data for the income chart.

require_once '../../db_config.php'; // FIX: Corrected secure path (two levels up)

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : '';
$labels = [];
$amounts = [];
$chartType = 'monthly'; // Default chart type

// 1. SHOW MONTHLY DATA FOR A SPECIFIC YEAR (Default View)
if ($year !== '') {
    $chartType = 'monthly';
    // Query each month from 1 to 12
    for ($m = 1; $m <= 12; $m++) {
        $labels[] = date('F', mktime(0, 0, 0, $m, 1));
        
        $stmt = $conn->prepare("SELECT SUM(amount) FROM income WHERE MONTH(date)=? AND YEAR(date)=?");
        $stmt->bind_param("ii", $m, $year);
        $stmt->execute();
        $stmt->bind_result($sum);
        $stmt->fetch();
        $amounts[] = $sum ? floatval($sum) : 0;
        $stmt->close();
    }
}
// 2. SHOW YEARLY DATA (Only if ALL YEARS are selected)
else {
    $chartType = 'yearly';
    // Get all years with income data
    $yearsResult = $conn->query("SELECT DISTINCT YEAR(date) AS year FROM income ORDER BY year ASC");
    $years = [];
    while ($row = $yearsResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
    $yearsResult->close();

    // Fetch total income per year
    foreach ($years as $yr) {
        $labels[] = $yr;
        $stmt = $conn->prepare("SELECT SUM(amount) FROM income WHERE YEAR(date)=?");
        $stmt->bind_param("i", $yr);
        $stmt->execute();
        $stmt->bind_result($sum);
        $stmt->fetch();
        $amounts[] = $sum ? floatval($sum) : 0;
        $stmt->close();
    }
}

header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'amounts' => $amounts,
    'chartType' => $chartType
]);
?>