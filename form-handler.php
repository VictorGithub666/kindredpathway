<?php
// form-handler.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $service = htmlspecialchars(trim($_POST['service'] ?? ''));
    $destination = htmlspecialchars(trim($_POST['destination'] ?? ''));
    $date = htmlspecialchars(trim($_POST['date'] ?? ''));
    $time = htmlspecialchars(trim($_POST['time'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    // Determine which form was submitted
    $formType = 'Contact Form'; // Default
    if (isset($_POST['consultation_type'])) {
        $formType = 'Consultation Request';
    }
    
    // Validate essential fields
    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Store error in session and redirect back
        session_start();
        $_SESSION['form_error'] = "Please fill in all required fields correctly.";
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=1');
        exit;
    }
    
    // Email configuration - Using your info@ email
    $to = "info@kindredpathway.org"; // Your preferred cPanel email
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
        // Store form data in session for thank you page
        session_start();
        $_SESSION['form_data'] = [
            'name' => $name,
            'email' => $email,
            'form_type' => $formType
        ];
        
        // Redirect to thank you page
        header('Location: thank-you.html');
        exit;
    } else {
        // Error response
        session_start();
        $_SESSION['form_error'] = "Oops! Something went wrong. Please try again later.";
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=1');
        exit;
    }
} else {
    // Not a POST request
    header('Location: index.html');
    exit;
}
?>