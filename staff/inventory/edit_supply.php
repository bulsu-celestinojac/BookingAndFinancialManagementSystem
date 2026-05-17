<?php
require '../../db_config.php'; // Using centralized config

// Handle POST request for updating
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $stock_level = intval($_POST['stock_level']); // Ensure int
    $unit = $_POST['unit'];
    $low_stock_threshold = intval($_POST['low_stock_threshold']); // Ensure int

    // Use 'i' for integer types
    $stmt = $conn->prepare("UPDATE inventory SET name=?, category=?, stock_level=?, unit=?, low_stock_threshold=? WHERE id=?");
    $stmt->bind_param("ssisii", $name, $category, $stock_level, $unit, $low_stock_threshold, $id);

    if ($stmt->execute()) {
        header("Location: index.php?success=updated");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }

    $stmt->close();
} 
// Handle GET request for fetching the record
elseif (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supply = $result->fetch_assoc();
    $stmt->close();

    if (!$supply) {
        die("Record not found.");
    }
} else {
    die("Invalid request. No ID specified.");
}

// Define categories for dropdown
$categories = ['Cleaning', 'Food & Beverage', 'Linens', 'Maintenance', 'Office', 'Other'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Supply Record</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
      /* Light & Modern Color Palette for Private Resort - Consistent */
      :root {
          --color-card-bg: #f8fafc;
          --color-accent: #14b8a6; /* A clean teal for Inventory */
          --color-dark-text: #1f2937;
          --color-light-text: #6b7280;
      }
      .bg-card-bg { background-color: var(--color-card-bg); }
      .bg-accent { background-color: var(--color-accent); }
      .text-dark-text { color: var(--color-dark-text); }
      .text-light-text { color: var(--color-light-text); }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
  <div class="bg-white p-8 max-w-lg w-full rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-6 text-dark-text text-center">Edit Supply Record</h2>
    <?php if (isset($error)): ?>
        <div class="mb-4 text-red-600 text-sm text-center bg-red-100 p-2 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="edit_supply.php" method="POST" class="space-y-4">
      <input type="hidden" name="id" value="<?= htmlspecialchars($supply['id']) ?>">
      <div>
        <label class="block mb-1 font-semibold text-light-text">Item Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($supply['name']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
      </div>
      <div>
        <label class="block mb-1 font-semibold text-light-text">Category</label>
        <select name="category" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= ($supply['category'] == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block mb-1 font-semibold text-light-text">Current Stock</label>
            <input type="number" name="stock_level" value="<?= htmlspecialchars($supply['stock_level']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required min="0">
        </div>
        <div>
            <label class="block mb-1 font-semibold text-light-text">Unit</label>
            <input type="text" name="unit" value="<?= htmlspecialchars($supply['unit']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
        </div>
      </div>
      <div>
        <label class="block mb-1 font-semibold text-light-text">Low Stock Threshold</label>
        <input type="number" name="low_stock_threshold" value="<?= htmlspecialchars($supply['low_stock_threshold']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required min="1">
      </div>
      <div class="flex justify-end gap-2 mt-6">
        <a href="index.php" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</a>
        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">Update Record</button>
      </div>
    </form>
  </div>
</body>
</html>