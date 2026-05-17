<?php
// FILE: delete_employee.php
// Use require for critical files and correct path
require '../../db_config.php';

$error = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Soft delete: Change status to Inactive
    $stmt = $conn->prepare("UPDATE employees SET status = 'Inactive' WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Employee record successfully deactivated.";
    } else {
        $error = "Error deactivating record: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

} else {
    $error = "Invalid request. No ID specified.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deactivation Status</title>
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
  <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full text-center transform transition-all duration-300">
    <div class="mb-6">
      <?php if ($error): ?>
        <i class="fas fa-exclamation-triangle text-5xl text-red-500"></i>
      <?php else: ?>
        <i class="fas fa-check-circle text-5xl text-green-500"></i>
      <?php endif; ?>
    </div>
    
    <h2 class="text-2xl font-bold mb-4 <?= $error ? 'text-red-600' : 'text-green-600' ?>">
      <?= $error ? 'Action Failed' : 'Employee Deactivated' ?>
    </h2>
    
    <p class="mb-6 text-gray-700 text-base">
      <?= $error ? htmlspecialchars($error) : "The employee's status has been set to Inactive." ?>
    </p>
    
    <button onclick="window.location.href='index.php'" class="bg-accent text-white px-6 py-2 rounded-full hover:opacity-80 font-semibold transition-colors duration-200">
      Back to Employee List
    </button>
  </div>
</body>
</html>