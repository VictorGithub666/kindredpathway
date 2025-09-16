<?php
// form-handler.php

// Enable error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Collect and sanitize form data
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $service = htmlspecialchars(trim($_POST['service'] ?? ''));
        $destination = htmlspecialchars(trim($_POST['destination'] ?? ''));
        $date = htmlspecialchars(trim($_POST['date'] ?? ''));
        $time = htmlspecialchars(trim($_POST['time'] ?? ''));
        $message = htmlspecialchars(trim($_POST['message'] ?? ''));

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }

        $formType = isset($_POST['consultation_type']) ? 'Consultation Request' : 'Contact Form';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'lim106b.superfasthost.cloud';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@kindredpathway.org';
        $mail->Password   = 'info@kindred.pathway'; // ⚠️ Make sure this is the REAL password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Debugging (comment out in production)
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'error_log';

        $mail->setFrom('info@kindredpathway.org', 'Kindred Pathway Website');
        $mail->addAddress('info@kindredpathway.org', 'Kindred Pathway');
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "New $formType from Website";

        $body  = "<h2>New $formType</h2>";
        $body .= "<p><strong>Name:</strong> $name</p>";
        $body .= "<p><strong>Email:</strong> $email</p>";
        $body .= "<p><strong>Phone:</strong> $phone</p>";
        if ($service)     $body .= "<p><strong>Service:</strong> $service</p>";
        if ($destination) $body .= "<p><strong>Destination:</strong> $destination</p>";
        if ($date)        $body .= "<p><strong>Date:</strong> $date</p>";
        if ($time)        $body .= "<p><strong>Time:</strong> $time</p>";
        $body .= "<p><strong>Message:</strong><br>" . nl2br($message) . "</p>";

        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        if ($mail->send()) {
            echo "✅ Mail sent successfully!";
            // header('Location: thank-you.html'); exit; // uncomment for production
        } else {
            throw new Exception("Mailer Error: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
} else {
    echo "❌ Invalid request method: " . $_SERVER["REQUEST_METHOD"];
}
