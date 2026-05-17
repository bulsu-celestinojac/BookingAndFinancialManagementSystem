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

// Handle form submission for updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $approved_by = $_POST['approved_by'];

    // Using $conn from required file
    $stmt = $conn->prepare("UPDATE budget SET date=?, category=?, amount=?, description=?, approved_by=? WHERE id=?");
    $stmt->bind_param("ssdssi", $date, $category, $amount, $description, $approved_by, $id);

    if ($stmt->execute()) {
        // Revoke the temporary privilege immediately after successful operation (REMOVED)
        // unset($_SESSION['budget_approved']);
        // unset($_SESSION['approved_by_name']);
        
        header("Location: index.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $stmt->close();

} elseif (isset($_GET['id'])) {
    // Fetch the record to be edited
    $id = $_GET['id'];
    // Using $conn from required file
    $stmt = $conn->prepare("SELECT * FROM budget WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();

    if (!$budget) {
        die("Record not found.");
    }
} else {
    die("Invalid request. No ID specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Budget Record</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
      /* Light & Modern Color Palette for Private Resort */
      :root {
          --color-main-bg: #ffffff;
          --color-card-bg: #f8fafc;
          --color-accent: #f59e42;
          --color-dark-text: #1f2937;
          --color-light-text: #6b7280;
      }
      .bg-main-bg { background-color: var(--color-main-bg); }
      .bg-card-bg { background-color: var(--color-card-bg); }
      .bg-accent { background-color: var(--color-accent); }
      .text-dark-text { color: var(--color-dark-text); }
      .text-light-text { color: var(--color-light-text); }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
  <div class="bg-white p-8 max-w-lg w-full rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-6 text-dark-text text-center">Edit Budget Record</h2>
    <form action="edit_budget.php" method="POST" class="space-y-4">
      <input type="hidden" name="id" value="<?= htmlspecialchars($budget['id']) ?>">
      <div>
        <label class="block mb-1 font-semibold text-light-text">Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($budget['date']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
      </div>
      <div>
        <label class="block mb-1 font-semibold text-light-text">Category</label>
        <input type="text" name="category" value="<?= htmlspecialchars($budget['category']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text" readonly>
      </div>
      <div>
        <label class="block mb-1 font-semibold text-light-text">Amount (₱)</label>
        <input type="number" name="amount" value="<?= htmlspecialchars($budget['amount']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required min="0" step="0.01">
      </div>
      <div>
        <label class="block mb-1 font-semibold text-light-text">Description</label>
        <input type="text" name="description" value="<?= htmlspecialchars($budget['description']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" placeholder="Optional">
      </div>
      <div>
        <label class="block mb-1 font-semibold text-light-text">Approved By</label>
        <input type="text" name="approved_by" value="<?= htmlspecialchars($budget['approved_by']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
      </div>
      <div class="flex justify-end gap-2 mt-6">
        <a href="index.php" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</a>
        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">Update</button>
      </div>
    </form>
  </div>
</body>
</html>