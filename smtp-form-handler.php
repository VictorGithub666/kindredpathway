<?php
// form-handler.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Import PHPMailer classes - using your existing PHPMailer installation
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer from your existing directory
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Collect and sanitize form data
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
        $service = isset($_POST['service']) ? htmlspecialchars(trim($_POST['service'])) : '';
        $destination = isset($_POST['destination']) ? htmlspecialchars(trim($_POST['destination'])) : '';
        $date = isset($_POST['date']) ? htmlspecialchars(trim($_POST['date'])) : '';
        $time = isset($_POST['time']) ? htmlspecialchars(trim($_POST['time'])) : '';
        $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';
        
        // Validate essential fields
        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }
        
        // Determine form type
        $formType = 'Contact Form';
        if (isset($_POST['consultation_type'])) {
            $formType = 'Consultation Request';
        }
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings - USING YOUR CPANEL EMAIL
        $mail->isSMTP();
        $mail->Host       = 'mail.kindredpathway.org';  // Your server's mail host
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@kindredpathway.org';  // Your cPanel email
        $mail->Password   = 'info@kindred.pathway'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Enable debugging if needed
        // $mail->SMTPDebug = 2; // Uncomment for debugging
        
        // Recipients
        $mail->setFrom('info@kindredpathway.org', 'Kindred Pathway Website');
        $mail->addAddress('info@kindredpathway.org', 'Kindred Pathway');
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "New $formType from Website";
        
        // Build email body
        $email_body = "<h2>New $formType</h2>";
        $email_body .= "<p><strong>Name:</strong> $name</p>";
        $email_body .= "<p><strong>Email:</strong> $email</p>";
        $email_body .= "<p><strong>Phone:</strong> $phone</p>";
        
        if (!empty($service)) {
            $email_body .= "<p><strong>Service:</strong> $service</p>";
        }
        
        if (!empty($destination)) {
            $email_body .= "<p><strong>Destination Country:</strong> $destination</p>";
        }
        
        if (!empty($date)) {
            $email_body .= "<p><strong>Preferred Date:</strong> $date</p>";
        }
        
        if (!empty($time)) {
            $email_body .= "<p><strong>Preferred Time:</strong> $time</p>";
        }
        
        $email_body .= "<p><strong>Message:</strong><br>" . nl2br($message) . "</p>";
        
        $mail->Body = $email_body;
        
        // Alternative plain text version
        $mail->AltBody = strip_tags($email_body);
        
        // Send email
        if ($mail->send()) {
            // Redirect to thank you page
            header('Location: thank-you.html');
            exit;
        } else {
            throw new Exception("Message could not be sent. Please try again later.");
        }
        
    } catch (Exception $e) {
        // Log the error
        error_log("Form submission error: " . $e->getMessage());
        
        // Redirect back with error
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=1&message=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Not a POST request
    header('Location: index.html');
    exit;
}
?>