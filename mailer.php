<?php
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Function to send the email
function sendVerificationEmail($toEmail, $username, $verificationCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'suyatsarah519@gmail.com';
        $mail->Password = 'ihrc skuz wgax evyj';  // App-specific password for Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('suyatsarah519@gmail.com', 'phpmailer');
        $mail->addAddress($toEmail, $username);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Account Verification';

        // ✅ Include the actual verification link
        $verifyLink = "http://localhost/ias2/verify.php?code=$verificationCode";

        $mail->Body = "
            Hello $username,<br><br>
            Thank you for registering. Please click the link below to verify your email address and complete your registration:<br>
            <a href='$verifyLink'>$verifyLink</a><br><br>
            Best regards,<br>
            Your Company Name
        ";

        // Send it
        $mail->send();
        echo 'Verification email has been sent.';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
