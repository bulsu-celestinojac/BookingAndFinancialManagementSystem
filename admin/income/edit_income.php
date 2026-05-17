<?php
// File: edit_income.php

require_once '../../db_config.php'; // FIX: Corrected secure path (two levels up)

$income = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = $_POST['id'] ?? null;
  $date = $_POST['date'] ?? null;
  $source = $_POST['source'] ?? null;
  $amount = $_POST['amount'] ?? null;
  $description = $_POST['description'] ?? null;
  $payment_method = $_POST['payment_method'] ?? null;
  
  // 1. BASIC VALIDATION
  if (empty($id) || empty($date) || empty($source) || empty($amount) || !is_numeric($amount) || $amount < 0) {
    $error = "Please fill out all required fields correctly. Amount must be a positive number.";
    // Re-fetch record data to pre-populate the form with current values before displaying error
    $stmt = $conn->prepare("SELECT * FROM income WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $income = $result->fetch_assoc();
    $stmt->close();
  } else {
      // 2. EXECUTE UPDATE
      $stmt = $conn->prepare("UPDATE income SET date=?, source=?, amount=?, description=?, payment_method=? WHERE id=?");
      $stmt->bind_param("ssdssi", $date, $source, $amount, $description, $payment_method, $id);

      if ($stmt->execute()) {
        header("Location: index.php?success=2"); // Use '2' for Update success
        exit();
      } else {
        $error = "Error updating record: " . htmlspecialchars($conn->error);
        $stmt->close();
        // Re-fetch record data for error display
        $stmt = $conn->prepare("SELECT * FROM income WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $income = $result->fetch_assoc();
        $stmt->close();
      }
  }
} 
// If this is a GET request (for loading the edit form)
elseif (isset($_GET['id'])) {
  $id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM income WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $income = $result->fetch_assoc();
  $stmt->close();

  if (!$income) {
      die("Error: Record not found.");
  }
} else {
  die("Invalid request: No ID specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Income Record #<?= htmlspecialchars($income['id'] ?? 'N/A') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
      /* Style to define the accent color from the main dashboard */
      :root { --color-accent: #56b4d3; }
      .bg-accent { background-color: var(--color-accent); }
      .hover\:opacity-80:hover { opacity: 0.8; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 max-w-lg w-full rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-2 text-accent text-center">Edit Income Record</h2>
    <p class="mb-6 text-gray-500 text-center">Update the details of your income entry below.</p>
    
    <?php if (isset($error)): ?>
      <div class="mb-4 text-red-600 text-center bg-red-100 p-2 rounded-md"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="edit_income.php" method="POST" class="space-y-5">
      <input type="hidden" name="id" value="<?= htmlspecialchars($income['id'] ?? '') ?>">
      
      <div>
        <label class="block mb-1 font-semibold text-gray-700">Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($income['date'] ?? '') ?>" class="w-full border p-2 rounded bg-gray-50 text-gray-700" required>
      </div>
      
      <div>
        <label class="block mb-1 font-semibold text-gray-700">Source</label>
        <select name="source" class="w-full border p-2 rounded bg-gray-50 text-gray-700" required>
          <?php $currentSource = $income['source'] ?? ''; ?>
          <option value="Full Payment" <?= $currentSource == 'Full Payment' ? 'selected' : '' ?>>Full Payment</option>
          <option value="Down Payment" <?= $currentSource == 'Down Payment' ? 'selected' : '' ?>>Down Payment</option>
          <option value="Reservation Fee" <?= $currentSource == 'Reservation Fee' ? 'selected' : '' ?>>Reservation Fee</option>
          <option value="Time Extension" <?= $currentSource == 'Time Extension' ? 'selected' : '' ?>>Time Extension</option>
          <option value="Others" <?= $currentSource == 'Others' ? 'selected' : '' ?>>Others</option>
        </select>
      </div>
      
      <div>
        <label class="block mb-1 font-semibold text-gray-700">Amount (₱)</label>
        <input type="number" name="amount" value="<?= htmlspecialchars($income['amount'] ?? '') ?>" class="w-full border p-2 rounded bg-gray-50 text-gray-700" required min="0" step="0.01">
      </div>
      
      <div>
        <label class="block mb-1 font-semibold text-gray-700">Description</label>
        <input type="text" name="description" value="<?= htmlspecialchars($income['description'] ?? '') ?>" class="w-full border p-2 rounded bg-gray-50 text-gray-700" placeholder="Optional">
      </div>
      
      <div>
        <label class="block mb-1 font-semibold text-gray-700">Payment Method</label>
        <select name="payment_method" class="w-full border p-2 rounded bg-gray-50 text-gray-700" required>
          <?php $currentPayment = $income['payment_method'] ?? ''; ?>
          <option value="Cash" <?= $currentPayment == 'Cash' ? 'selected' : '' ?>>Cash</option>
          <option value="Bank Transfer" <?= $currentPayment == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
          <option value="GCash" <?= $currentPayment == 'GCash' ? 'selected' : '' ?>>GCash</option>
        </select>
      </div>
      
      <div class="flex justify-end gap-2 mt-6">
        <a href="index.php" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 text-gray-700 font-semibold">Cancel</a>
        
        <button type="submit" class="px-4 py-2 bg-accent text-white rounded hover:opacity-80 font-semibold">Update</button>
      </div>
    </form>
  </div>
</body>
</html>