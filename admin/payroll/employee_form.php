<?php
// FILE: payroll/employee_form.php (ONLY FOR EDITING EXISTING RECORDS)
require '../../db_config.php';

$id = $_GET['id'] ?? null;
$title = "Edit Employee Record";
$error = '';

// CRITICAL: Force REDIRECT if no ID is provided, guiding the user to the centralized creation module.
if (!$id) {
    header("Location: ../add_employee/add_user_form.php");
    exit();
}

// Fetch existing employee data (Required for editing)
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$fetched_employee = $result->fetch_assoc();
$stmt->close();

if (!$fetched_employee) {
    die("Employee record not found.");
}
$employee = $fetched_employee;
$title = "Edit Employee: " . htmlspecialchars($employee['fullname']);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;
    $employee_id = $_POST['employee_id'];
    $fullname = $_POST['fullname'];
    $position = $_POST['position'];
    $daily_rate = $_POST['daily_rate'];
    $hired_date = $_POST['hired_date'];

    // ONLY UPDATE LOGIC REMAINS
    $stmt = $conn->prepare("UPDATE employees SET employee_id=?, fullname=?, position=?, daily_rate=?, hired_date=? WHERE id=?");
    $stmt->bind_param("sssdsi", $employee_id, $fullname, $position, $daily_rate, $hired_date, $id);

    if ($stmt->execute()) {
        header("Location: index.php?success=updated");
        exit();
    } else {
        $error = "Error saving record: " . htmlspecialchars($conn->error);
        if (strpos($conn->error, 'Duplicate entry') !== false) {
             $error = "Error: Employee ID already exists.";
        }
    }
    $stmt->close();
}

$positions = [
    'Manager', 'Assistant Manager', 'Front Desk Staff', 'Housekeeping', 
    'Chef', 'Cook', 'Kitchen Staff', 'Maintenance', 'Lifeguard', 'Security'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
      :root {
          --color-card-bg: #f8fafc;
          --color-accent: #2563eb; 
          --color-dark-text: #1f2937;
          --color-light-text: #6b7280;
      }
      .bg-card-bg { background-color: var(--color-card-bg); }
      .bg-accent { background-color: var(--color-accent); }
      .text-dark-text { color: var(--color-dark-text); }
      .text-light-text { color: var(--color-light-text); }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans p-4">
  <div class="bg-white p-8 max-w-lg w-full rounded-lg shadow-xl">
    <h2 class="text-2xl font-bold mb-6 text-dark-text text-center"><?= $title ?></h2>
    <?php if ($error): ?>
        <div class="mb-4 text-red-600 text-sm text-center bg-red-100 p-3 rounded-lg border border-red-300"><?= $error ?></div>
    <?php endif; ?>
    <form action="employee_form.php?id=<?= htmlspecialchars($id) ?>" method="POST" class="space-y-4">
      <input type="hidden" name="id" value="<?= htmlspecialchars($employee['id']) ?>">
      
      <div>
        <label class="block mb-1 font-semibold text-light-text">Employee ID</label>
        <input type="text" name="employee_id" value="<?= htmlspecialchars($employee['employee_id']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Unique ID (e.g., ARES-001)" readonly>
        <p class="text-xs text-light-text mt-1">Employee ID cannot be changed.</p>
      </div>

      <div>
        <label class="block mb-1 font-semibold text-light-text">Full Name</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($employee['fullname']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required placeholder="Last Name, First Name">
      </div>

      <div>
        <label class="block mb-1 font-semibold text-light-text">Position</label>
        <select name="position" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
            <option value="">Select Position</option>
            <?php foreach ($positions as $pos): ?>
                <option value="<?= htmlspecialchars($pos) ?>" <?= ($employee['position'] == $pos) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pos) ?>
                </option>
            <?php endforeach; ?>
            <?php if (!in_array($employee['position'], $positions) && $employee['position']): ?>
                <option value="<?= htmlspecialchars($employee['position']) ?>" selected><?= htmlspecialchars($employee['position']) ?> (Current)</option>
            <?php endif; ?>
        </select>
      </div>
      
      <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block mb-1 font-semibold text-light-text">Daily Rate (₱)</label>
            <input type="number" name="daily_rate" value="<?= htmlspecialchars($employee['daily_rate']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required min="0" step="0.01">
        </div>
        <div>
            <label class="block mb-1 font-semibold text-light-text">Hired Date</label>
            <input type="date" name="hired_date" value="<?= htmlspecialchars($employee['hired_date']) ?>" class="w-full border p-3 rounded-lg bg-card-bg text-dark-text focus:outline-none focus:ring-2 focus:ring-accent" required>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <a href="index.php" class="px-6 py-2 bg-gray-300 rounded-full hover:bg-gray-400 text-dark-text font-semibold">Cancel</a>
        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-full hover:opacity-80 font-semibold">
            <i class="fas fa-save mr-2"></i>Update Record
        </button>
      </div>
    </form>
  </div>
</body>
</html>