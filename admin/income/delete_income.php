<?php
// File: delete_income.php

require_once '../../db_config.php'; // Corrected secure path

$status = '';
$message = '';
$recordId = $_GET['id'] ?? 'N/A';
$redirectUrl = 'index.php?status=delete_success'; // Default redirect URL

if (isset($_GET['id'])) {
  $id = $_GET['id'];
  $stmt = $conn->prepare("DELETE FROM income WHERE id = ?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    $status = 'success';
    $message = "Income record has been successfully deleted.";
  } else {
    $status = 'error';
    $message = 'Failed to delete record. Please try again: ' . htmlspecialchars($conn->error);
    $redirectUrl = 'index.php?status=delete_error';
  }

  $stmt->close();
  // We keep the connection open for the final message display, then close implicitly
} else {
  $status = 'error';
  $message = 'No record was specified for deletion.';
  $redirectUrl = 'index.php?status=delete_error';
}

// Ensure the connection is closed before exit
$conn->close(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delete Income Record</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
      :root { --color-accent: #56b4d3; }
      .bg-accent { background-color: var(--color-accent); }
  </style>
  <?php if ($status === 'success'): ?>
  <script>
    // Redirect after 1.5 seconds to ensure the user sees the confirmation message
    setTimeout(function() {
      window.location.href = '<?= $redirectUrl ?>';
    }, 1000); 
  </script>
  <?php endif; ?>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full text-center">
      <h2 class="text-xl font-bold mb-4 <?= $status === 'success' ? 'text-blue-600' : 'text-red-600' ?>">
        <?= $status === 'success' ? 'Success!' : 'Error!' ?>
      </h2>
      <p class="mb-6 text-gray-700"><?= htmlspecialchars($message) ?></p>
      
      <?php if ($status === 'error'): ?>
      <button onclick="window.location.href='<?= $redirectUrl ?>'" class="bg-accent text-white px-6 py-2 rounded hover:opacity-80 font-semibold">
        OK
      </button>
      <?php endif; ?>
      
    </div>
  </div>
</body>
</html>