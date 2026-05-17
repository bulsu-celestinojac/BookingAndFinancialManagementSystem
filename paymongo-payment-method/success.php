<?php
include 'dbconfig.php'; // connect DB

// -------------------- Dompdf --------------------
require __DIR__ . '/../dompdf-3.1.0/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// ✅ Fetch the latest PAID booking from DB with all details
$stmt = $conn->prepare("SELECT name, email, phone, package, amount, booked_date, payment_method, created_at
                           FROM payments
                           WHERE status='paid'
                           ORDER BY id DESC LIMIT 1");
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $package, $amount, $booked_date, $payment_method, $created_at);
$stmt->fetch();
$stmt->close();

// If no paid booking found, stop
if (!$name) {
    exit("⚠️ No paid booking found. Please wait for payment confirmation.");
}

// Logo
// Make sure this path is correct relative to the script's location
$logoPath = __DIR__ . "/../images/alienahslogo.png";
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoBase64 = 'data:image/png;base64,' . $logoData;
}

// Dompdf setup
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Format created_at to a readable date and time
$createdAtFormatted = date("F j, Y, g:i a", strtotime($created_at));
// Format amount for display
$amountFormatted = number_format($amount, 2);

// HTML for receipt
$html = "
<html>
<head>
<style>
body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f9fafb; }
.receipt {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    max-width: 650px;
    margin: 40px auto;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}
.logo { text-align: center; margin-bottom: 20px; }
.logo img { width: 140px; }
h2 { text-align: center; color: #1e3a8a; margin-bottom: 5px; }
h3 { text-align: center; color: #374151; margin-top: 0; }
table {
    width: 100%;
    margin-top: 25px;
    border-collapse: collapse;
    font-size: 15px;
}
td {
    padding: 10px;
    border-bottom: 1px solid #e5e7eb;
}
td:first-child { font-weight: 600; color: #1f2937; width: 40%; }
td:last-child { color: #374151; }
.footer {
    margin-top: 25px;
    font-size: 13px;
    text-align: center;
    color: #6b7280;
}
</style>
</head>
<body>
<div class='receipt'>
    <div class='logo'>
        <img src='$logoBase64' alt='Resort Logo'/>
    </div>
    <h2>Aleinah's Private Resort</h2>
    <h3>Booking Receipt</h3>
    
    <table>
        <tr><td>Name:</td><td>$name</td></tr>
        <tr><td>Email:</td><td>$email</td></tr>
        <tr><td>Phone:</td><td>$phone</td></tr>
        <tr><td>Package:</td><td>$package</td></tr>
        <tr><td>Date Booked:</td><td>$booked_date</td></tr>
        <tr><td>Payment Method:</td><td>$payment_method</td></tr>
        <tr><td>Amount Paid:</td><td>₱$amountFormatted</td></tr>
        <tr><td>Date of Payment:</td><td>$createdAtFormatted</td></tr>
    </table>

    <div class='footer'>
        Thank you for booking with Aleinah's Private Resort!<br>
        We look forward to welcoming you.
    </div>
</div>
</body>
</html>
";

// Generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfFile = "receipt_" . preg_replace("/[^a-zA-Z0-9]/", "_", $name) . ".pdf";
$pdfPath = __DIR__ . "/$pdfFile";
file_put_contents($pdfPath, $dompdf->output());

// -------------------- Email receipt --------------------
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('GMAIL_USER') ?: 'aleinahsprivateresort@gmail.com';
    $mail->Password   = getenv('GMAIL_PASS') ?: '';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('aleinahsprivateresort@gmail.com', "Aleinah's Private Resort");
    $mail->addAddress($email, $name);
    $mail->Subject = "Your Booking Receipt - Aleinah's Private Resort";
    $mail->Body    = "Hi $name,\n\nThank you for booking with Aleinah's Private Resort. Please find your booking receipt attached.\n\nBest regards,\nAleinah's Private Resort Team";
    $mail->addAttachment($pdfPath);

    $mail->send();
    $status = "receipt emailed to <b>$email</b>";
} catch (Exception $e) {
    $status = "⚠️ Email not sent: {$mail->ErrorInfo}";
}

// -------------------- Success Page --------------------
echo "
<html>
<head>
<style>
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: #f3f4f6;
    color: #333;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.container {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    max-width: 550px;
    width: 90%;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    text-align: center;
}
.logo img { width: 120px; margin-bottom: 15px; }
h1 { color: #1e3a8a; margin-bottom: 10px; }
.status {
    background: #e0f2fe;
    color: #0369a1;
    padding: 10px 15px;
    border-radius: 8px;
    margin: 15px 0;
    font-weight: 500;
}
table { width: 100%; margin: 20px 0; border-collapse: collapse; font-size: 14px; }
td { padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
td:first-child { font-weight: 600; color: #1f2937; width: 40%; }
td:last-child { color: #374151; }
a.download-btn {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #fff;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    transition: 0.3s;
}
a.download-btn:hover { background: linear-gradient(135deg, #1e40af, #2563eb); }
</style>
</head>
<body>
<div class='container'>
    <div class='logo'>
        <img src='aleinahslogo.png' alt='Resort Logo'>
    </div>
    <h1>Booking Successful!</h1>
    <p class='status'>$status</p>

    <table>
        <tr><td>Name:</td><td>$name</td></tr>
        <tr><td>Email:</td><td>$email</td></tr>
        <tr><td>Phone:</td><td>$phone</td></tr>
        <tr><td>Package:</td><td>$package</td></tr>
        <tr><td>Date Booked:</td><td>$booked_date</td></tr>
        <tr><td>Payment Method:</td><td>$payment_method</td></tr>
        <tr><td>Amount Paid:</td><td>₱$amountFormatted</td></tr>
        <tr><td>Date of Payment:</td><td>$createdAtFormatted</td></tr>
    </table>

    <p>You can also download a copy of your receipt below:</p>
    <a href='$pdfFile' download class='download-btn'>⬇ Download Receipt</a>
</div>
</body>
</html>
";
?>