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
        $name        = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone       = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $service     = htmlspecialchars(trim($_POST['service'] ?? ''));
        $visaType    = htmlspecialchars(trim($_POST['visaType'] ?? ''));
        $otherVisaType = htmlspecialchars(trim($_POST['otherVisaType'] ?? ''));
        $destination = htmlspecialchars(trim($_POST['destination'] ?? ''));
        $otherDestination = htmlspecialchars(trim($_POST['otherDestination'] ?? ''));
        $date        = htmlspecialchars(trim($_POST['date'] ?? ''));
        $time        = htmlspecialchars(trim($_POST['time'] ?? ''));
        $message     = htmlspecialchars(trim($_POST['message'] ?? ''));

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }

        // Handle visa type logic
        $finalVisaType = $visaType;
        if ($visaType === 'Other' && !empty($otherVisaType)) {
            $finalVisaType = $otherVisaType . " (Other)";
        } elseif ($visaType === 'Other' && empty($otherVisaType)) {
            throw new Exception("Please specify the visa type when selecting 'Other'.");
        }

        // Handle destination logic
        $finalDestination = $destination;
        if ($destination === 'Other' && !empty($otherDestination)) {
            $finalDestination = $otherDestination . " (Other)";
        } elseif ($destination === 'Other' && empty($otherDestination)) {
            throw new Exception("Please specify the destination country when selecting 'Other'.");
        }

        $formType = isset($_POST['consultation_type']) ? 'Consultation Request' : 'Contact Form';

        // Configure PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'lim106.truehost.cloud';   // your host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@kindredpathway.org';
        $mail->Password   = 'info@kindred.pathway';    // ⚠️ replace with real password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Debugging (disable in production)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'error_log';

        $mail->setFrom('info@kindredpathway.org', 'Kindred Pathway Website');
        $mail->addAddress('info@kindredpathway.org', 'Kindred Pathway');
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "New $formType from Website";

        // Build styled HTML email body
        $email_body = '
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <style>
            body { font-family: Arial, sans-serif; background-color: #f4f6f8; margin:0; padding:0; }
            .container { background:#fff; max-width:600px; margin:20px auto; border-radius:8px; overflow:hidden; border:1px solid #ddd; }
            .header { background:#003366; color:#fff; padding:20px; text-align:center; }
            .header h2 { margin:0; }
            .content { padding:20px; color:#333; }
            .content p { margin:8px 0; line-height:1.5; }
            .label { font-weight:bold; color:#003366; }
            .footer { background:#f4f6f8; text-align:center; padding:15px; font-size:12px; color:#777; }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h2> New ' . $formType . '</h2>
            </div>
            <div class="content">
              <p><span class="label">Name:</span> ' . $name . '</p>
              <p><span class="label">Email:</span> ' . $email . '</p>
              <p><span class="label">Phone:</span> ' . $phone . '</p>';
        
        if ($service) {
            $email_body .= '<p><span class="label">Service:</span> ' . $service . '</p>';
        }
        if ($finalVisaType) {
            $email_body .= '<p><span class="label">Visa Type:</span> ' . $finalVisaType . '</p>';
        }
        if ($finalDestination) {
            $email_body .= '<p><span class="label">Destination Country:</span> ' . $finalDestination . '</p>';
        }
        if ($date) {
            $email_body .= '<p><span class="label">Preferred Date:</span> ' . $date . '</p>';
        }
        if ($time) {
            $email_body .= '<p><span class="label">Preferred Time:</span> ' . $time . '</p>';
        }

        $email_body .= '
              <p><span class="label">Message:</span></p>
              <p>' . nl2br($message) . '</p>
            </div>
            <div class="footer">
              <p>Kindred Pathway | Immigration & Relocation Support</p>
            </div>
          </div>
        </body>
        </html>';

        $mail->Body    = $email_body;
        $mail->AltBody = strip_tags($email_body);

        if ($mail->send()) {
            // In production redirect to thank you page
            header('Location: thank-you.html');
            exit;
        } else {
            throw new Exception("Mailer Error: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        // Redirect back with error
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=1&message=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Not a POST request
    header('Location: index.html');
    exit;
}