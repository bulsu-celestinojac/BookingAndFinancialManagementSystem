<?php
require '../../db_config.php';

// ----------------------------------------------------------------
// CRITICAL SECURITY ENFORCEMENT: (REMOVED)
/*
if (
    !isset($_SESSION['budget_approved']) || 
    $_SESSION['budget_approved'] < time()   
) {
    echo "<script>alert('Modification privilege expired or denied. Please re-enter the admin password.');window.location.href='index.php';</script>";
    exit();
}
*/
// ----------------------------------------------------------------

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM budget WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Revoke the temporary privilege immediately after successful operation (REMOVED)
        // unset($_SESSION['budget_approved']);
        // unset($_SESSION['approved_by_name']);
        
        header("Location: index.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    $stmt->close();

} else {
    echo "Invalid request. No ID specified.";
}
?>