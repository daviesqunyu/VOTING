<?php
/**
 * Email Configuration for E-Voting System
 * 
 * This file contains email configuration settings for password reset functionality.
 * You may need to configure your server's email settings or use a third-party email service.
 */

// Email Configuration
define('EMAIL_FROM', 'noreply@evoting.com');
define('EMAIL_FROM_NAME', 'E-Voting System');
define('EMAIL_SUBJECT_PREFIX', 'E-Voting System - ');

// SMTP Configuration (if using SMTP)
define('SMTP_HOST', 'localhost');  // Your SMTP server
define('SMTP_PORT', 587);          // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', '');       // SMTP username
define('SMTP_PASSWORD', '');       // SMTP password
define('SMTP_SECURE', 'tls');      // 'tls' or 'ssl'

// Alternative: Use PHPMailer (recommended for production)
// Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
// Then uncomment and configure the following:

/*
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmailWithPHPMailer($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = EMAIL_SUBJECT_PREFIX . $subject;
        $mail->Body    = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
*/

// Simple email function using PHP's mail() function
function sendSimpleEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">" . "\r\n";
    $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, EMAIL_SUBJECT_PREFIX . $subject, $message, $headers);
}

// Email templates
function getPasswordResetEmailTemplate($username, $reset_link) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Password Reset Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello " . htmlspecialchars($username) . ",</p>
                <p>You have requested to reset your password for the E-Voting System.</p>
                <p>Please click the button below to reset your password:</p>
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($reset_link) . "' class='button'>Reset Password</a>
                </p>
                <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                <p>" . htmlspecialchars($reset_link) . "</p>
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This link will expire in 1 hour</li>
                    <li>If you didn't request this password reset, please ignore this email</li>
                    <li>For security reasons, this link can only be used once</li>
                </ul>
            </div>
            <div class='footer'>
                <p>This is an automated message from the E-Voting System. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " E-Voting System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Test email function
function testEmailConfiguration() {
    $test_email = 'test@example.com';
    $test_subject = 'Email Configuration Test';
    $test_message = '<h2>Email Test</h2><p>If you receive this email, your email configuration is working correctly.</p>';
    
    if (sendSimpleEmail($test_email, $test_subject, $test_message)) {
        echo "Email test sent successfully. Check your email configuration.";
    } else {
        echo "Email test failed. Please check your server's email configuration.";
    }
}

// Uncomment the line below to test email configuration
// testEmailConfiguration();
?> 