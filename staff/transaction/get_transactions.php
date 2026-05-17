<?php
// Filename: get_transactions.php

// CRITICAL FIX: Use require and the correct path for centralized configuration
require '../../db_config.php'; 

/**
 * Fetches and consolidates income, expense, payroll, and budget transactions 
 * for a specified date range, adjusting the end date for DATETIME accuracy.
 *
 * @global mysqli $conn The active database connection.
 * @param string $start_date The start date (YYYY-MM-DD).
 * @param string $end_date The end date (YYYY-MM-DD).
 * @return array An array of consolidated and sorted transaction records.
 */
function getConsolidatedTransactions($conn, $start_date, $end_date) {
    
    // Adjust the end date to include the entire last day (up to 23:59:59).
    $inclusive_end_date = $end_date . ' 23:59:59';
    
    $transactions = [];
    $types = 'ss';

    // --- 1. Fetch INCOME Records ---
    $query_income = "
        SELECT 
            date, amount, 'Income' AS type, source AS category, description 
        FROM income 
        WHERE date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query_income);
    $stmt->bind_param($types, $start_date, $inclusive_end_date); 
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();


    // --- 2. Fetch EXPENSE Records ---
    $query_expense = "
        SELECT 
            date, amount, 'Expense' AS type, category, description 
        FROM expenses 
        WHERE date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query_expense);
    $stmt->bind_param($types, $start_date, $inclusive_end_date); 
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Negate amount to represent financial outflow
        $row['amount'] *= -1; 
        $transactions[] = $row;
    }
    $stmt->close();


    // --- 3. Fetch PAYROLL Expense Records ---
    // Payroll's end_date is likely just a DATE type, so we use the original end_date.
    $query_payroll = "
        SELECT 
            pr.end_date AS date, pr.net_pay AS amount, 'Payroll' AS type, 
            'Salaries' AS category, CONCAT('Net Pay for ', e.fullname) AS description 
        FROM payroll_records pr
        JOIN employees e ON pr.employee_id = e.id
        WHERE pr.end_date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query_payroll);
    $stmt->bind_param($types, $start_date, $end_date); // Use date-only end date
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Negate amount to represent financial outflow
        $row['amount'] *= -1; 
        $transactions[] = $row;
    }
    $stmt->close();


    // --- 4. Fetch BUDGET Allocations (NEW INTEGRATION) ---
    // Type: Budget (Outflow for allocation purposes)
    $query_budget = "
        SELECT 
            date, amount, 'Budget' AS type, category, CONCAT('Allocation: ', description) AS description
        FROM budget 
        WHERE date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query_budget);
    $stmt->bind_param($types, $start_date, $inclusive_end_date); 
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Negate amount to represent allocation/outflow
        $row['amount'] *= -1; 
        $transactions[] = $row;
    }
    $stmt->close();
    
    
    // --- 5. Sort the consolidated array by date (most recent first) ---
    usort($transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    return $transactions;
}

// --- Script Execution ---

// Set default dates if not provided via GET request
$default_start = date('Y-m-01');
$default_end = date('Y-m-d');

// Safely get dates from URL
$startDate = $_GET['start_date'] ?? $default_start;
$endDate = $_GET['end_date'] ?? $default_end;

$transactionHistory = getConsolidatedTransactions($conn, $startDate, $endDate);

// Close the main database connection
$conn->close();

// The variable $transactionHistory is now ready.
?>