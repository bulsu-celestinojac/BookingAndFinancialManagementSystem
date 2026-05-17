<?php
require_once '../../db_config.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // Optionally, delete the proof file from the server
    $stmt = $conn->prepare("SELECT proof_file FROM expenses WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($proof_file);
    $stmt->fetch();
    $stmt->close();

    if ($proof_file && file_exists($proof_file)) {
        unlink($proof_file);
    }

    $stmt = $conn->prepare("DELETE FROM expenses WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: expense_record.php");
exit();
?>