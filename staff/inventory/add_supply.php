<?php
require '../../db_config.php'; // Using centralized config

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    // Ensure stock_level and low_stock_threshold are treated as integers safely
    $stock_level = intval($_POST['stock_level'] ?? 0);
    $unit = $_POST['unit'] ?? '';
    $low_stock_threshold = intval($_POST['low_stock_threshold'] ?? 1);

    // Basic validation
    if (empty($name) || empty($category) || empty($unit) || $low_stock_threshold < 1) {
        header("Location: index.php?error=missing_fields");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO inventory (name, category, stock_level, unit, low_stock_threshold) VALUES (?, ?, ?, ?, ?)");
    // Use 'i' for integer types
    $stmt->bind_param("ssisi", $name, $category, $stock_level, $unit, $low_stock_threshold);

    if ($stmt->execute()) {
        header("Location: index.php?success=added");
        exit();
    } else {
        // Simple error handling for mobile view
        echo "<script>alert('Error adding supply: " . htmlspecialchars($conn->error) . "'); window.location.href='index.php';</script>";
    }

    $stmt->close();
} else {
    header("Location: index.php");
    exit();
}
?>