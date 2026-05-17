<?php
// -------------------- Dompdf --------------------
require __DIR__ . '/../dompdf-3.1.0/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Booking details from PayMongo redirect
$name = $_GET['name'] ?? 'Guest';
$email = $_GET['email'] ?? '';
$phone = $_GET['phone'] ?? '';
$package = $_GET['package'] ?? '';
$booked_date = $_GET['booked_date'] ?? '';

// Logo path for the displayed page
$logoPath = __DIR__ . "/../images/alienahslogo.png";
$logoBase64 = "";
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoBase64 = 'data:image/png;base64,' . $logoData;
}

// -------------------- PHPMailer --------------------
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
    $mail->Username   = 'aleinahsprivateresort@gmail.com'; // your Gmail
    $mail->Password   = 'mgwrfjsjfubnkeuv';               // your App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('aleinahsprivateresort@gmail.com', "Aleinah's Private Resort");
    $mail->addAddress($email, $name); // send to client
    $mail->addReplyTo('aleinahsprivateresort@gmail.com', "Aleinah's Private Resort");

    $mail->Subject = "Your Booking Receipt - Aleinah's Private Resort";
    $mail->Body     = "Hi $name,\n\nThank you for booking with Aleinah's Private Resort. Please find your booking receipt attached.\n\nBest regards,\nAleinah's Private Resort Team";

    // The PDF will be attached later in the script
    $mail->send();
    $status = "✅ Receipt emailed to <b>$email</b>";
} catch (Exception $e) {
    $status = "⚠️ Email not sent: {$mail->ErrorInfo}";
}

// -------------------- HTML Content for the Page and PDF --------------------
// This HTML will be displayed on the page.
$receipt_content_html = "
<!DOCTYPE html>
<html>
<head>
    <title>Booking Successful</title>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f7f7f7; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .receipt-container { 
            background-color: #fff; 
            padding: 2.5rem; 
            border-radius: 1rem; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); 
            max-width: 600px; 
            width: 100%; 
            text-align: center;
        }
        .header { margin-bottom: 2rem; }
        .logo { width: 80px; height: auto; margin-bottom: 1rem; }
        .status-message { color: #27ae60; font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem; }
        .email-status-box { 
            background-color: #e8f5e9; 
            border: 1px solid #c8e6c9; 
            padding: 1rem; 
            border-radius: 0.5rem; 
            margin-bottom: 2rem; 
            font-size: 0.9rem;
        }
        
        /* New receipt styling */
        .receipt-details {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .receipt-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-details td {
            padding: 12px 20px;
            font-size: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .receipt-details td:first-child {
            font-weight: 500;
            color: #555;
            width: 35%; /* Adjust width for better alignment */
        }
        .receipt-details tr:last-child td {
            border-bottom: none;
        }

        .download-link { 
            background-color: #2563eb; 
            color: #fff; 
            padding: 0.75rem 1.5rem; 
            border-radius: 0.5rem; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 2rem;
            transition: background-color 0.2s ease-in-out;
        }
        .download-link:hover { background-color: #1d4ed8; }
        .download-link svg { margin-right: 0.5rem; }
        
        /* Specific styles for the PDF to hide unnecessary elements */
        @media print {
            .hide-on-print { display: none !important; }
        }
    </style>
    <link href=\"https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap\" rel=\"stylesheet\">
</head>
<body>
    <div class='receipt-container'>
        <div class='header'>
            <h1 class='status-message hide-on-print'>✅ Booking Successful!</h1>
            <div class='email-status-box hide-on-print'>
                $status
            </div>
        </div>
        
        <div class='pdf-header'>
            <img src='data:image/png;base64,$logoBase64' alt='Resort Logo' class='logo'>
            <h2 style='margin: 0;'>Aleinah's Private Resort</h2>
            <h3 style='margin: 0; color: #777;'>Booking Receipt</h3>
        </div>

        <div class='receipt-details'>
            <table>
                <tr>
                    <td>Name:</td>
                    <td>$name</td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td>$email</td>
                </tr>
                <tr>
                    <td>Phone:</td>
                    <td>$phone</td>
                </tr>
                <tr>
                    <td>Package:</td>
                    <td>$package</td>
                </tr>
                <tr>
                    <td>Date Booked:</td>
                    <td>$booked_date</td>
                </tr>
                <tr>
                    <td>Amount Paid:</td>
                    <td>$PHPpackage</td>
                </tr>
            </table>
        </div>
        
        <div class='hide-on-print'>
            <p style='margin-top: 2rem; color: #555;'>You can also download a copy of your receipt below:</p>
            <a href='receipt_$name.pdf' download class='download-link'>
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor' style='width: 20px; height: 20px;'>
                    <path fill-rule='evenodd' d='M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 11.586V3a1 1 0 112 0v8.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z' clip-rule='evenodd' />
                </svg>
                Download Receipt
            </a>
        </div>
    </div>
</body>
</html>
";

// -------------------- PDF Generation from HTML --------------------
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Prepare HTML for PDF with a proper header and footer by removing web-specific content
$dompdf_html = preg_replace('/<h1 class=\'status-message.*\'.*?<\/h1>/s', '', $receipt_content_html);
$dompdf_html = preg_replace('/<div class=\'email-status-box.*\'.*?<\/div>/s', '', $dompdf_html);
$dompdf_html = preg_replace('/<div class=\'hide-on-print\'.*?>.*?<\/div>/s', '', $dompdf_html);
$dompdf_html = str_replace(
    '<body>', 
    '<body><div class="pdf-header"><img src="data:image/png;base64,' . $logoBase64 . '" alt="Resort Logo" class="logo"><h2>Aleinah\'s Private Resort</h2><h3>Booking Receipt</h3></div>', 
    $dompdf_html
);

$dompdf->loadHtml($dompdf_html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Generate the PDF file
$pdfFile = "receipt_$name.pdf";
$pdfPath = __DIR__ . "/$pdfFile";
file_put_contents($pdfPath, $dompdf->output());

// -------------------- Display the success page --------------------
echo $receipt_content_html;
?>