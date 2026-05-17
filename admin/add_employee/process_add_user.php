<?php
// FILE: admin/add_user/process_add_user.php
session_start();

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../../login/index.php"); 
    exit();
}

// OPTIONAL: Restrict access to only Admin or Manager roles
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
    header("Location: ../../admin/dashboard/index.php?error=unauthorized"); 
    exit();
}


// 1. --- DATABASE CONNECTION DETAILS ---
// *******************************************************************
// 🔥 FIX FOR "Access denied" ERROR 🔥
// YOU MUST REPLACE ALL CAPITALIZED PLACEHOLDERS WITH YOUR ACTUAL CREDENTIALS.
// THE USERNAME AND PASSWORD MUST BE CORRECT AND HAVE ACCESS TO BOTH DATABASES.
// *******************************************************************

$DB_HOST = "localhost"; 
$DB_USER = "u205310066_YOUR_DB_USERNAME"; // E.g., u205310066_user. Must be the full user ID.
$DB_PASS = "YOUR_CORRECT_DB_PASSWORD";   // THIS PASSWORD MUST MATCH THE USER ABOVE.
$DB_BOOKING_NAME = "u205310066_booking_system"; // E.g., u205310066_booking
$DB_RESORT_NAME = "u205310066_aleinahsresort"; // E.g., u205310066_aleinahsresort


// Connection 1: Booking System (users table)
$conn_booking = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_BOOKING_NAME);
if ($conn_booking->connect_error) {
    die("Booking DB Connection failed: " . $conn_booking->connect_error);
}

// Connection 2: Resort Payroll System (employees table)
$conn_resort = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_RESORT_NAME);
// This is the connection attempt that fails, causing the error on line ~37
if ($conn_resort->connect_error) {
    $conn_booking->close();
    die("Resort DB Connection failed: " . $conn_resort->connect_error);
}

// 2. --- Gather and Sanitize Data ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add_user_form.php");
    exit();
}

$fullname = $_POST['fullname'] ?? '';
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? 'staff'; // Now only accepts 'admin' or 'staff'
$password_default = $_POST['password_default'] ?? 'password123'; // Default password

$hired_date = $_POST['hired_date'] ?? date('Y-m-d');
$position = $_POST['position'] ?? ''; // Now coming from a text field
$daily_rate = floatval($_POST['daily_rate'] ?? 0);

$password_hashed = password_hash($password_default, PASSWORD_DEFAULT);

// 3. --- Transaction Start ---
$conn_booking->begin_transaction();
$conn_resort->begin_transaction();

$success_flag = false;
$error_message = "An unknown error occurred.";
$employee_id = null; // Will be set during the transaction

try {
    // --- CRITICAL FIX: GENERATE employee_id ---
    // Fetch the highest existing numeric ID and increment it
    $result_id = $conn_resort->query("SELECT MAX(CAST(SUBSTRING_INDEX(employee_id, '-', -1) AS UNSIGNED)) AS max_id FROM employees");
    $row_id = $result_id->fetch_assoc();
    $next_numeric_id = ($row_id['max_id'] ?? 0) + 1;
    // Format the new ID (e.g., ARES-001, ARES-002, etc.)
    $employee_id = 'ARES-' . str_pad($next_numeric_id, 3, '0', STR_PAD_LEFT);
    // ----------------------------------------
    
    // A. INSERT INTO BOOKING SYSTEM (users table)
    $stmt_booking = $conn_booking->prepare("INSERT INTO users (fullname, username, password, role, email) VALUES (?, ?, ?, ?, ?)");
    $stmt_booking->bind_param("sssss", $fullname, $username, $password_hashed, $role, $email);
    
    if (!$stmt_booking->execute()) {
        throw new Exception("Login Account Creation Failed: " . $stmt_booking->error);
    }
    $stmt_booking->close();

    // B. INSERT INTO RESORT PAYROLL SYSTEM (employees table)
    // The query has 6 placeholders for (employee_id, fullname, position, daily_rate, hired_date, status)
    $stmt_resort = $conn_resort->prepare("INSERT INTO employees (employee_id, fullname, position, daily_rate, hired_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    $status_active = 'Active';

    $stmt_resort->bind_param("sssdss", 
        $employee_id, // Now using the auto-generated ID
        $fullname, 
        $position, 
        $daily_rate, 
        $hired_date, 
        $status_active
    );

    if (!$stmt_resort->execute()) {
        throw new Exception("Payroll Record Creation Failed: " . $stmt_resort->error);
    }
    $stmt_resort->close();
    
    // C. COMMIT BOTH TRANSACTIONS
    $conn_booking->commit();
    $conn_resort->commit();

    $success_flag = true;
    $error_message = "User '{$username}' and employee record (ID: {$employee_id}) created successfully!";

} catch (Exception $e) {
    // D. ROLLBACK TRANSACTIONS ON FAILURE
    $conn_booking->rollback();
    $conn_resort->rollback();
    
    // Improved error handling
    $error_message = strpos($e->getMessage(), 'Duplicate entry') !== false ? 
                     "Error: Username or Email already exists. Please check and try again." : 
                     "Transaction Failed: " . $e->getMessage();
}

// 4. --- Close Connections and Redirect ---
$conn_booking->close();
$conn_resort->close();

if ($success_flag) {
    $redirect_status = 'success';
} else {
    $redirect_status = 'error';
}

header("Location: add_user_form.php?status={$redirect_status}&message=" . urlencode($error_message));
exit();