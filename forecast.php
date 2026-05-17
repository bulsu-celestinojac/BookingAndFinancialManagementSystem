<?php
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

// Prediction (next month = average of last 3 months)
$last3 = array_slice($expenses, -3);
$prediction = array_sum($last3) / count($last3);
$labels[] = "Next Month";
$expenses[] = $prediction;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Forecast</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { margin-bottom: 10px; }
        .btn-print {
            padding: 8px 15px;
            margin: 5px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-print:hover { background: #0056b3; }
        .btn-excel { background: green; }
        table { border-collapse: collapse; width: 80%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
        .anomaly { background: #f8d7da; }
        .predicted { background: #d1ecf1; }
    </style>
</head>
<body>

    <h2>Aleinah's Private Resort – Expense Forecast</h2>

    <!-- Chart -->
    <canvas id="expenseChart" width="600" height="300"></canvas>

    <!-- Buttons -->
    <br>
    <button class="btn-print" onclick="window.print()">🖨️ Print Report</button>
    <a href="export_excel.php">
        <button class="btn-print btn-excel">⬇️ Export to Excel</button>
    </a>

    <!-- Table -->
    <table>
        <tr>
            <th>Month</th>
            <th>Expense (₱)</th>
            <th>Status</th>
        </tr>
        <?php
        for ($i = 0; $i < count($labels); $i++) {
            $status = "Normal";
            $class = "";
            if ($i == count($labels) - 1) {
                $status = "Predicted";
                $class = "predicted";
            } elseif (in_array($i, $anomalies)) {
                $status = "Anomaly";
                $class = "anomaly";
            }
            echo "<tr class='$class'><td>{$labels[$i]}</td><td>" . number_format($expenses[$i], 2) . "</td><td>$status</td></tr>";
        }
        ?>
    </table>

    <script>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Expenses',
                    data: <?php echo json_encode($expenses); ?>,
                    borderColor: 'blue',
                    backgroundColor: 'rgba(0,123,255,0.2)',
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                }
            }
        });
    </script>

</body>
</html>
