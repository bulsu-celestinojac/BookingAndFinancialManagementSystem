<?php
// FILE: delete.php (Deletes inventory item)
require '../../db_config.php'; // Using centralized config

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php?success=deleted");
        exit();
    } else {
        // Simple error handling for mobile view
        echo "<script>alert('Error deleting record: " . htmlspecialchars($conn->error) . "'); window.location.href='index.php';</script>";
    }

    $stmt->close();

} else {
    header("Location: index.php?error=no_id");
    exit();
}
?>