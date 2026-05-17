<?php
// FILE: payroll_calculates.php
require '../../db_config.php';

$error = null;
$success_message = '';
$start_date = '';
$end_date = '';

// Explanation of Deductions for the success message
$deduction_explanation = "Note: Deductions are simplified for this system to include estimated SSS, PhilHealth, and Pag-IBIG contributions, calculated at a flat rate of ₱50.00 per workday.";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Simple server-side validation
    if (empty($start_date) || empty($end_date)) {
        $error = "Please define both the Start Date and End Date.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
         $error = "End Date must be equal to or after the Start Date.";
    }

    if (!$error) {
        $conn->begin_transaction();
        $processed_count = 0;
        $total_net = 0;
        
        try {
            // 1. Calculate the number of actual workdays (Monday to Saturday)
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $end = $end->modify('+1 day'); // Include the end date in the period

            $days_worked = 0;
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            foreach($period as $dt) {
                // Get the day of the week (1=Mon, 7=Sun)
                $dayOfWeek = $dt->format('N');
                
                // If day is NOT Sunday (7), count it as a workday.
                if ($dayOfWeek < 7) { 
                    $days_worked++;
                }
            }

            if ($days_worked === 0) {
                 throw new Exception("The selected period contains zero workdays (Mon-Sat). Please check the dates.");
            }
            // -------------------------------------------------------------

            // 2. Fetch all Active Employees
            $employees_result = $conn->query("SELECT id, daily_rate FROM employees WHERE status = 'Active'");
            
            if ($employees_result === false) {
                throw new Exception("Error fetching active employees: " . $conn->error);
            }
            
            if ($employees_result->num_rows === 0) {
                 throw new Exception("No active employees found to process payroll.");
            }

            // 3. Prepare the INSERT statement for payroll records
            $stmt = $conn->prepare("INSERT INTO payroll_records (employee_id, start_date, end_date, days_worked, gross_pay, deductions, net_pay, processed_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            while ($employee = $employees_result->fetch_assoc()) {
                // COMPUTATION: Use the calculated workdays
                $employee_days_worked = $days_worked; 
                $gross_pay = $employee['daily_rate'] * $employee_days_worked;
                
                // COMPUTATION: REALISTIC DEDUCTION (₱50.00 per day worked)
                // This simulates the employee share of SSS, PhilHealth, Pag-IBIG.
                $deductions = $employee_days_worked * 50.00;
                $net_pay = $gross_pay - $deductions;
                
                // Bind and Execute
                $stmt->bind_param("issdddd", 
                    $employee['id'], 
                    $start_date, 
                    $end_date, 
                    $employee_days_worked, 
                    $gross_pay, 
                    $deductions, 
                    $net_pay
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Database execution failed for employee ID " . $employee['id'] . ": " . $stmt->error);
                }
                
                $processed_count++;
                $total_net += $net_pay; 
            }
            
            $stmt->close();
            $conn->commit();
            $success_message = "Successfully processed payroll for $processed_count employees, covering $days_worked workdays.\nTotal Net Pay: ₱" . number_format($total_net, 2) . "\n\n$deduction_explanation";

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Payroll failed: " . $e->getMessage();
        }
    }
} else {
    // If not a POST request, redirect back
    header("Location: payroll_index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payroll Processing Status</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
      :root {
          --color-accent: #2563eb; 
      }
      .bg-accent { background-color: var(--color-accent); }
  </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
  <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full text-center transform transition-all duration-300">
    <div class="mb-6">
      <?php if ($error): ?>
        <i class="fas fa-exclamation-triangle text-5xl text-red-500"></i>
      <?php else: ?>
        <i class="fas fa-check-circle text-5xl text-green-500"></i>
      <?php endif; ?>
    </div>
    
    <h2 class="text-2xl font-bold mb-4 <?= $error ? 'text-red-600' : 'text-green-600' ?>">
      <?= $error ? 'Processing Error!' : 'Payroll Complete!' ?>
    </h2>
    
    <p class="mb-6 text-gray-700 text-base whitespace-pre-wrap text-left">
      <?= $error ? htmlspecialchars($error) : nl2br(htmlspecialchars($success_message)) ?>
      <br>
      <span class="font-bold">Period:</span> **<?= htmlspecialchars($start_date) ?>** to **<?= htmlspecialchars($end_date) ?>**
    </p>
    
    <button onclick="window.location.href='payroll_index.php'" class="bg-accent text-white px-6 py-2 rounded-full hover:opacity-80 font-semibold transition-colors duration-200">
      View Payroll History
    </button>
  </div>
</body>
</html>