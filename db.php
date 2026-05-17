<?php
// dbconfig.php (for localhost XAMPP)

$host = "localhost";
$username = "root";
$password = ""; // default is empty for XAMPP
$dbname = "test2"; // make sure this DB exists in phpMyAdmin

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connection successful.";
}

// Optional: Set character set
$conn->set_charset("utf8mb4");
?>
