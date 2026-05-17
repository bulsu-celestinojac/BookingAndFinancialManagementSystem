<?php
require '../../db_config.php';

// ----------------------------------------------------------------
// CRITICAL SECURITY ENFORCEMENT: (REMOVED)
/*
if (
    !isset($_SESSION['budget_approved']) || // No approval token
    $_SESSION['budget_approved'] < time()   // Token has expired
) {
    echo "<script>alert('Modification privilege expired or denied. Please re-enter the admin password.');window.location.href='index.php';</script>";
    exit();
}
*/
// ----------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $approved_by = $_POST['approved_by'];

    $month = date('n', strtotime($date));
    $year = date('Y', strtotime($date));

    // Only one budget per category per month
    $stmt = $conn->prepare("SELECT id FROM budget WHERE category=? AND MONTH(date)=? AND YEAR(date)=?");
    $stmt->bind_param("sii", $category, $month, $year);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo "<script>alert('A budget for this category already exists for this month.');window.location.href='index.php?month=$month&year=$year';</script>";
        exit();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO budget (date, category, amount, description, approved_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $date, $category, $amount, $description, $approved_by);
        $stmt->execute();
        $stmt->close();
        
        // Revoke the temporary privilege immediately after successful operation (REMOVED)
        // unset($_SESSION['budget_approved']);
        // unset($_SESSION['approved_by_name']);

        echo "<script>alert('Budget added successfully!');window.location.href='index.php?month=$month&year=$year';</script>";
        exit();
    }
}
?>