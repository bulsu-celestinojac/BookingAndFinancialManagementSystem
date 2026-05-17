<?php
// FILE: financial-overview/financial_data.php

require_once '../../db_config.php';

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$data = [
    'labels' => [],
    'income' => [],
    'expense' => [],
    'budget' => []
];

// Loop through all 12 months
for ($m = 1; $m <= 12; $m++) {
    $monthName = date('F', mktime(0, 0, 0, $m, 10));
    $data['labels'][] = $monthName;

    // --- 1. Fetch Total Income ---
    $stmtIncome = $conn->prepare("SELECT SUM(amount) as total FROM income WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmtIncome->bind_param("ii", $m, $year);
    $stmtIncome->execute();
    $resultIncome = $stmtIncome->get_result();
    $rowIncome = $resultIncome->fetch_assoc();
    $incomeTotal = $rowIncome['total'] ? floatval($rowIncome['total']) : 0;
    $data['income'][] = $incomeTotal;
    $stmtIncome->close();

    // --- 2. Fetch Total Expense ---
    $stmtExpense = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmtExpense->bind_param("ii", $m, $year);
    $stmtExpense->execute();
    $resultExpense = $stmtExpense->get_result();
    $rowExpense = $resultExpense->fetch_assoc();
    $expenseTotal = $rowExpense['total'] ? floatval($rowExpense['total']) : 0;
    $data['expense'][] = $expenseTotal;
    $stmtExpense->close();

    // --- 3. Fetch Total Budget ---
    $stmtBudget = $conn->prepare("SELECT SUM(amount) as total FROM budget WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmtBudget->bind_param("ii", $m, $year);
    $stmtBudget->execute();
    $resultBudget = $stmtBudget->get_result();
    $rowBudget = $resultBudget->fetch_assoc();
    $budgetTotal = $rowBudget['total'] ? floatval($rowBudget['total']) : 0;
    $data['budget'][] = $budgetTotal;
    $stmtBudget->close();
}

header('Content-Type: application/json');
echo json_encode($data);
?>