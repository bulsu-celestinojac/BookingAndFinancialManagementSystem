<?php
// FILE: add_expense.php
require_once '../../db_config.php';
// CRITICAL: Include the notification trigger function (assuming notifications is sibling to expense)
require '../notifications/notification_trigger.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $category = $_POST['category'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];
    $paid_by = $_POST['paid_by'];
    $proof_file = '';
    
    $month = date('n', strtotime($date));
    $year = date('Y', strtotime($date));

    // Start a transaction to ensure atomic operations
    $conn->begin_transaction();

    try {
        // --- 1. HANDLE FILE UPLOAD ---
        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = basename($_FILES['proof_file']['name']);
            // Use a clean filename to prevent injection/path traversal
            $safeFileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $fileName); 
            $targetFile = $targetDir . time() . "_" . $safeFileName;
            
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $targetFile)) {
                $proof_file = $targetFile;
            } else {
                throw new Exception("File upload failed.");
            }
        } else {
            throw new Exception("Proof of Purchase file is required.");
        }

        // --- 2. INSERT EXPENSE RECORD ---
        $stmt = $conn->prepare("INSERT INTO expenses (date, category, amount, description, paid_by, proof_file) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsss", $date, $category, $amount, $description, $paid_by, $proof_file);
        
        if (!$stmt->execute()) {
             throw new Exception("Database error inserting expense: " . $stmt->error);
        }
        $stmt->close();

        // --- 3. CHECK FOR BUDGET OVERRUN ---
        
        // Fetch Budget for this Category/Month/Year
        $budgetStmt = $conn->prepare("SELECT amount FROM budget WHERE category=? AND MONTH(date)=? AND YEAR(date)=?");
        $budgetStmt->bind_param("sii", $category, $month, $year);
        $budgetStmt->execute();
        $budgetResult = $budgetStmt->get_result();
        $budgetRow = $budgetResult->fetch_assoc();
        $monthlyBudget = $budgetRow['amount'] ?? 0;
        $budgetStmt->close();

        if ($monthlyBudget > 0) {
            // Calculate Total Expenses for this Category/Month/Year AFTER the new insertion
            $expenseStmt = $conn->prepare("SELECT SUM(amount) AS total_spent FROM expenses WHERE category=? AND MONTH(date)=? AND YEAR(date)=?");
            $expenseStmt->bind_param("sii", $category, $month, $year);
            $expenseStmt->execute();
            $expenseResult = $expenseStmt->get_result();
            $expenseRow = $expenseResult->fetch_assoc();
            $totalSpent = $expenseRow['total_spent'] ?? 0;
            $expenseStmt->close();

            // Compare Total Spent vs. Budget
            if ($totalSpent >= $monthlyBudget) {
                $overrunAmount = $totalSpent - $monthlyBudget;
                
                // --- CRITICAL: FIRE NOTIFICATION ---
                $type = "BUDGET OVERRUN ALERT";
                $message = "❌ Critical: Spending in **{$category}** is over budget by ₱" . number_format($overrunAmount, 2) . " for " . date('F Y', strtotime($date)) . ".";
                // Use a reliable link to the Expense page
                $link = "../expense/index.php?month={$month}&year={$year}"; 
                fireNotification($conn, $type, $message, $link);
            }
        }

        // --- 4. COMMIT TRANSACTION ---
        $conn->commit();

        // Final redirect on success
        header("Location: index.php?success=added");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // If file upload failed, proof_file is empty, otherwise unlink the file if the DB failed after upload
        if (!empty($proof_file) && file_exists($proof_file)) {
             unlink($proof_file);
        }
        
        // Redirect with error message
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    header("Location: index.php");
    exit();
}
?>