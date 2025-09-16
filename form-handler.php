<?php
// form-handler.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Collect and sanitize form data
        $name = sanitize_input($_POST['name']);
        $email = filter_var(sanitize_input($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
        $service = isset($_POST['service']) ? sanitize_input($_POST['service']) : '';
        $destination = isset($_POST['destination']) ? sanitize_input($_POST['destination']) : '';
        $date = isset($_POST['date']) ? sanitize_input($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_input($_POST['time']) : '';
        $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
        
        // Determine which form was submitted
        $formType = 'Contact Form';
        if (isset($_POST['consultation_type'])) {
            $formType = 'Consultation Request';
        }
        
        // Validate essential fields
        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }
        
        // Email configuration
        $to = "info@kindredpathway.org";
        $subject = "New $formType from Kindred Pathway Website";
        
        // Compose email body
        $email_body = "You have received a new $formType from your website.\n\n";
        $email_body .= "Name: $name\n";
        $email_body .= "Email: $email\n";
        $email_body .= "Phone: $phone\n";
        
        if (!empty($service)) {
            $email_body .= "Service: $service\n";
        }
        
        if (!empty($destination)) {
            $email_body .= "Destination Country: $destination\n";
        }
        
        if (!empty($date)) {
            $email_body .= "Preferred Date: $date\n";
        }
        
        if (!empty($time)) {
            $email_body .= "Preferred Time: $time\n";
        }
        
        $email_body .= "Message:\n$message\n";
        
        // Email headers
        $headers = "From: $name <$email>\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send email
        if (mail($to, $subject, $email_body, $headers)) {
            // Redirect to thank you page
            header('Location: thank-you.html');
            exit;
        } else {
            throw new Exception("Failed to send email. Please try again later.");
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