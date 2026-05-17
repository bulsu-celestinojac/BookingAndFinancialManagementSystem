<?php
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Expense_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli("localhost", "root", "", "resort_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT DATE_FORMAT(date, '%M %Y') AS month, amount FROM expenses ORDER BY date ASC";
$result = $conn->query($sql);

$labels = [];
$expenses = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['month'];
    $expenses[] = (float)$row['amount'];
}

// Detect anomalies
$mean = array_sum($expenses) / count($expenses);
$variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $expenses)) / count($expenses);
$std_dev = sqrt($variance);

$anomalies = [];
foreach ($expenses as $i => $value) {
    $z_score = ($value - $mean) / ($std_dev ?: 1);
    if (abs($z_score) > 2) {
        $anomalies[] = $i;
    }
}

// Prediction
$last3 = array_slice($expenses, -3);
$prediction = array_sum($last3) / count($last3);
$labels[] = "Next Month";
$expenses[] = $prediction;

// Generate table for Excel
echo "Month\tExpense (₱)\tStatus\n";
for ($i = 0; $i < count($labels); $i++) {
    $status = "Normal";
    if ($i == count($labels) - 1) {
        $status = "Predicted";
    } elseif (in_array($i, $anomalies)) {
        $status = "Anomaly";
    }
    echo $labels[$i] . "\t" . number_format($expenses[$i], 2) . "\t" . $status . "\n";
}

$conn->close();
?>
