<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require 'vendor/autoload.php';


    $name    = htmlspecialchars($_POST['name']);
    $email   = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone   = htmlspecialchars($_POST['phone']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('GMAIL_USER') ?: 'aleinahsprivateresort@gmail.com';
        $mail->Password   = getenv('GMAIL_PASS') ?: '';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom($email, $name);
        $mail->addAddress('aleinahsprivateresort@gmail.com'); // ✅ your receiving email

        $mail->isHTML(true);
        $mail->Subject = "New Contact Message: $subject";
        $mail->Body = "
            <div style='font-family:Segoe UI,Arial,sans-serif;background:#f8f9fa;padding:24px;'>
                <div style='max-width:520px;margin:auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(30,93,140,0.08);padding:24px;'>
                    <h2 style='color:#1E5D8C;margin-bottom:18px;'>New Message from Aleinah's Private Resort</h2>
                    <table style='width:100%;font-size:1rem;color:#222F3E;'>
                        <tr>
                            <td style='padding:8px 0;width:120px;'><strong>Name:</strong></td>
                            <td style='padding:8px 0;'>$name</td>
                        </tr>
                        <tr>
                            <td style='padding:8px 0;'><strong>Email:</strong></td>
                            <td style='padding:8px 0;'>$email</td>
                        </tr>
                        <tr>
                            <td style='padding:8px 0;'><strong>Phone:</strong></td>
                            <td style='padding:8px 0;'>$phone</td>
                        </tr>
                        <tr>
                            <td style='padding:8px 0;'><strong>Subject:</strong></td>
                            <td style='padding:8px 0;'>$subject</td>
                        </tr>
                        <tr>
                            <td style='padding:8px 0;vertical-align:top;'><strong>Message:</strong></td>
                            <td style='padding:8px 0;'>".nl2br($message)."</td>
                        </tr>
                    </table>
                    <div style='margin-top:24px;font-size:0.95rem;color:#888;'>
                        <em>This message was sent from the Aleinah's Private Resort website contact form.</em>
                    </div>
                </div>
            </div>
        ";

        $mail->send();
        echo "<script>alert('Thank you! Your message has been sent.');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Message could not be sent. Error: {$mail->ErrorInfo}');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Aleinah's Private Resort</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header class="amenities-header">
        <a href="index.php" class="logo" style="text-decoration:none;">
            <img src="images/aleinahslogo.png" alt="Logo">
            <span class="logo-text">Aleinah's Private Resort</span>
        </a>
        <nav class="amenities-nav-menu">
            <a href="index.php">Home</a>
            <a href="amenities.php">Amenities</a>
            <a href="rates.php">Rates</a>
            <a href="location.php">Location</a>
            <a href="contact.php" class="active">Contact Us</a>
        </nav>
    </header>
<main class="contact-main">
    <section class="contact-section">
        <div class="contact-card">
            <h1 class="contact-title">Contact Us</h1>
            <p class="contact-desc">
                We'd love to hear from you! For bookings, inquiries, or feedback, please fill out the form below or reach us through our contact details.
            </p>
            <form class="contact-form" autocomplete="off" method="POST" action="contact.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name*</label>
                        <input type="text" id="name" name="name" required placeholder="Your Name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" required placeholder="you@email.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone*</label>
                        <input type="tel" id="phone" name="phone" required placeholder="0912 345 6789">
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject*</label>
                        <select id="subject" name="subject" required>
                            <option value="" disabled selected>Select a subject</option>
                            <option value="Booking">Booking Inquiry</option>
                            <option value="Feedback">Feedback</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                </div>
                <button type="submit" class="contact-btn">Send Message</button>
            </form>
            <div class="contact-details">
                <h2>Other Ways to Reach Us</h2>
                <p>
                    <strong>Email:</strong> <a href="mailto:aleinahsprivateresort@gmail.com">aleinahsprivateresort@gmail.com</a><br>
                    <strong>Phone:</strong> <a href="tel:+639690603727">+63 969 060 3727</a><br>
                    <strong>Facebook:</strong> <a href="https://facebook.com/aleinahsresort" target="_blank">facebook.com/aleinahsresort</a>
                </p>
            </div>
        </div>
    </section>
</main>
</body>
</html>
