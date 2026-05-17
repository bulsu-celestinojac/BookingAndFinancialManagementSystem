<?php
// File: dashboard/data_fetch.php - MODIFIED WITH PREDICTION FUNCTION

// CRITICAL SECURITY: Include the database config from the project root (two levels up)
require_once '../../db_config.php'; 

/**
 * Calculates a simple prediction based on the 3-month rolling average of completed months.
 * @global mysqli $conn The active database connection.
 * @return array An associative array containing 'predictedIncome' and 'predictedExpense'.
 */
function getFinancialPrediction() {
    global $conn;

    // Use current date as the reference point
    $currentDate = date('Y-m-d');

    // Calculate the start date (4 months ago, to get 3 full prior months)
    $startDate = date('Y-m-d', strtotime('-4 months', strtotime($currentDate)));

    $prediction = [
        'predictedIncome' => 0.00,
        'predictedExpense' => 0.00
    ];

    if (!$conn || $conn->connect_error) {
        return $prediction;
    }

    // --- Fetch Income for the last 3 months ---
    // Exclude the current month's transactions (date < ?) and limit history (date >= ?)
    $sqlIncome = "
        SELECT SUM(amount) AS total_amount, YEAR(date) AS yr, MONTH(date) AS mo
        FROM income 
        WHERE date < ? AND date >= ? 
        GROUP BY yr, mo
    ";
    $stmtIncome = $conn->prepare($sqlIncome);
    $stmtIncome->bind_param("ss", $currentDate, $startDate); 
    $stmtIncome->execute();
    $resultIncome = $stmtIncome->get_result();
    
    $totalIncome = 0;
    $monthCountIncome = $resultIncome->num_rows;

    while ($row = $resultIncome->fetch_assoc()) {
        $totalIncome += floatval($row['total_amount']);
    }
    $stmtIncome->close();

    // --- Fetch Expenses for the last 3 months ---
    $sqlExpense = "
        SELECT SUM(amount) AS total_amount, YEAR(date) AS yr, MONTH(date) AS mo
        FROM expenses 
        WHERE date < ? AND date >= ? 
        GROUP BY yr, mo
    ";
    $stmtExpense = $conn->prepare($sqlExpense);
    $stmtExpense->bind_param("ss", $currentDate, $startDate);
    $stmtExpense->execute();
    $resultExpense = $stmtExpense->get_result();
    
    $totalExpense = 0;
    $monthCountExpense = $resultExpense->num_rows;

    while ($row = $resultExpense->fetch_assoc()) {
        $totalExpense += floatval($row['total_amount']);
    }
    $stmtExpense->close();

    // Calculate Averages (3-Month Rolling Average)
    if ($monthCountIncome > 0) {
        $prediction['predictedIncome'] = round($totalIncome / $monthCountIncome, 2);
    }
    if ($monthCountExpense > 0) {
        $prediction['predictedExpense'] = round($totalExpense / $monthCountExpense, 2);
    }

    return $prediction;
}


/**
 * Gathers all key performance indicators (KPIs) from the database tables.
 * @global mysqli $conn The active database connection.
 * @return array An associative array containing all dashboard metrics.
 */
function getDashboardKPIs() {
    global $conn;

    if (!$conn || $conn->connect_error) {
        return [
            'totalIncome' => 0, 'totalExpense' => 0, 'netProfit' => 0,
            'totalBudget' => 0, 'budgetUtilization' => 0, 'totalInventoryItems' => 0,
            'lowStockCount' => 0, 'outOfStockCount' => 0, 'activeEmployees' => 0,
            'lastPayrollTotal' => 0, 'lastPayrollDate' => 'N/A'
        ];
    }
    
    $currentMonth = date('n');
    $currentYear = date('Y');
    $data = [];

    // --- A. Total Income (Current Month) ---
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM income WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->bind_param("ii", $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $data['totalIncome'] = $result['total'] ? floatval($result['total']) : 0;
    $stmt->close();

    // --- B. Total Expenses (Current Month) ---
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->bind_param("ii", $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $data['totalExpense'] = $result['total'] ? floatval($result['total']) : 0;
    $stmt->close();

    $data['netProfit'] = $data['totalIncome'] - $data['totalExpense'];

    // --- C. Total Budget and Utilization (Current Month) ---
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM budget WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->bind_param("ii", $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $data['totalBudget'] = $result['total'] ? floatval($result['total']) : 0;
    $stmt->close();

    if ($data['totalBudget'] > 0) {
        $data['budgetUtilization'] = ($data['totalExpense'] / $data['totalBudget']) * 100;
    } else {
        $data['budgetUtilization'] = 0;
    }

    // --- Other KPIs ---
    $data['lowStockCount'] = $conn->query("SELECT COUNT(id) FROM inventory WHERE stock_level <= low_stock_threshold AND stock_level > 0")->fetch_row()[0];
    $data['outOfStockCount'] = $conn->query("SELECT COUNT(id) FROM inventory WHERE stock_level = 0")->fetch_row()[0];
    $data['activeEmployees'] = $conn->query("SELECT COUNT(id) FROM employees WHERE status = 'Active'")->fetch_row()[0];
    
    $latestDateResult = $conn->query("SELECT MAX(end_date) FROM payroll_records");
    $latestDate = $latestDateResult ? $latestDateResult->fetch_row()[0] : null;
    $data['lastPayrollDate'] = $latestDate ?? 'N/A';

    if ($latestDate) {
        $stmt = $conn->prepare("SELECT SUM(net_pay) as total FROM payroll_records WHERE end_date = ?");
        $stmt->bind_param("s", $latestDate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $data['lastPayrollTotal'] = $result['total'] ? floatval($result['total']) : 0;
        $stmt->close();
    } else {
        $data['lastPayrollTotal'] = 0;
    }
    
    return $data;
}
?>