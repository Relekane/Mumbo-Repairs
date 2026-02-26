
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes (ensure the 'src' folder is in the same directory)
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ✅ 1. Sanitize and retrieve POST data
    $name    = htmlspecialchars(trim($_POST['name']));
    $email   = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    // ✅ 2. Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo "Please fill in all fields.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }

    // ✅ 3. Create PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // ✅ 4. Afrihost SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'mail.cnwebtest.co.za';
        $mail->SMTPAuth   = true;
        $mail->Username   = '_mainaccount@cnwebtest.co.za';
        $mail->Password   = 'CN_Web_Solutions@2025';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // ✅ 5. Sender and recipient
        $mail->setFrom('_mainaccount@cnwebtest.co.za', 'Website Contact Form');
        $mail->addReplyTo($email, $name);
        $mail->addAddress('molotokarabo@icloud.com', 'Website Owner');

        // ✅ 6. Email content
        $mail->isHTML(true);
        $mail->Subject = "📩 New Message from $name — $subject";
        $mail->Body    = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p><strong>Message:</strong><br>{$message}</p>
        ";

        // ✅ 7. Send the email
        $mail->send();
        echo "Message sent successfully!";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "Invalid request method.";
}
?>