<?php
// form-handler.php - SECURE VERSION WITH SPAM FILTERING & JUNK FOLDER ROUTING

// Enable error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

class SpamFilter {
    private $spamKeywords = [
        // SEO related
        'seo', 'search engine', 'ranking', 'organic ranking', 'google ranking',
        'backlink', 'link building', 'keyword', 'traffic', 'visitors',
        'digital marketing', 'online presence', 'return on investment',
        'answer engine optimization', 'aeo', 'monthly seo',
        
        // Affiliate/MLM related
        'affiliate', 'commission', 'profit share', 'payout', 'referral',
        'collaboration', 'partnership', 'joint venture', 'business opportunity',
        'make money', 'earn money', 'passive income', 'side hustle',
        
        // Generic spam
        'investment', 'bitcoin', 'crypto', 'forex', 'trading',
        'lottery', 'prize', 'winner', 'free', 'discount',
        'urgent', 'limited time', 'act now', 'don\'t miss',
        'click here', 'sign up', 'subscribe', 'buy now',
        
        // Company names from spam emails
        'monkey digital', 'digital x', 'seo experts'
    ];
    
    private $suspiciousDomains = [
        'monkeydigital.co', 'digital-x-press.com',
        // Add other spam domains as you encounter them
    ];
    
    public function isSpam($name, $email, $phone, $message, $honeypot = '') {
        // Honeypot check - if this field is filled, it's definitely spam
        if (!empty($honeypot)) {
            return true;
        }
        
        $content = strtolower($name . ' ' . $email . ' ' . $phone . ' ' . $message);
        
        // Check for spam keywords
        foreach ($this->spamKeywords as $keyword) {
            if (strpos($content, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        // Check for suspicious domains
        $emailDomain = strtolower(substr(strrchr($email, "@"), 1));
        foreach ($this->suspiciousDomains as $domain) {
            if (strpos($emailDomain, $domain) !== false) {
                return true;
            }
        }
        
        // Check for suspicious phone patterns (very long numbers, etc.)
        if (strlen($phone) > 15) {
            return true;
        }
        
        // Check for excessive links in message
        $linkCount = substr_count(strtolower($message), 'http');
        if ($linkCount > 2) {
            return true;
        }
        
        return false;
    }
}

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
        $honeypot    = htmlspecialchars(trim($_POST['website'] ?? '')); // Honeypot field

        // Basic validation
        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }

        // Initialize spam filter
        $spamFilter = new SpamFilter();
        
        // Check if submission is spam
        $isSpam = $spamFilter->isSpam($name, $email, $phone, $message, $honeypot);
        
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
        $mail->Host       = 'lim106.truehost.cloud';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@kindredpathway.org';
        $mail->Password   = 'info@kindred.pathway';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@kindredpathway.org', 'Kindred Pathway Website');
        
        if ($isSpam) {
            // ROUTE SPAM DIRECTLY TO JUNK FOLDER
            $mail->addAddress('info@kindredpathway.org', 'Kindred Pathway');
            
            // Add headers to force Roundcube to treat as spam
            $mail->addCustomHeader('X-Priority', '5 (Lowest)');
            $mail->addCustomHeader('X-MSMail-Priority', 'Low');
            $mail->addCustomHeader('Importance', 'Low');
            $mail->addCustomHeader('X-Spam-Flag', 'YES');
            $mail->addCustomHeader('X-Spam-Status', 'Yes');
            $mail->addCustomHeader('X-Spam-Level', '*****');
            $mail->addCustomHeader('Precedence', 'bulk');
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
            
            $mail->Subject = "[SPAM] New $formType from Website - AUTO FILTERED";
        } else {
            // Send legitimate emails normally
            $mail->addAddress('info@kindredpathway.org', 'Kindred Pathway');
            $mail->Subject = "New $formType from Website";
        }
        
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);

        // Build styled HTML email body
        $spamIndicator = $isSpam ? '<div style="background: #ff0000; color: white; padding: 10px; text-align: center; font-weight: bold;">🚫 AUTOMATED SPAM FILTER - This email was detected as spam and routed to Junk folder</div>' : '';
        
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
            .spam-warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px; }
          </style>
        </head>
        <body>
          <div class="container">
            ' . $spamIndicator . '
            <div class="header">
              <h2> New ' . $formType . '</h2>
            </div>
            <div class="content">';
              
        if ($isSpam) {
            $email_body .= '<div class="spam-warning">
                <strong> SPAM DETECTED:</strong> This message was automatically flagged as spam and routed to your Junk folder.<br>
                <strong>Detected Patterns:</strong> SEO/Affiliate marketing content
            </div>';
        }
              
        $email_body .= '
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
            // Always show success message to avoid revealing spam detection
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