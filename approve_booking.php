<?php
// approve_booking.php
// Handles admin approve/reject actions, updates DB and sends customer email.
// Robust PHPMailer loader: tries composer vendor/, then src/ files, otherwise falls back to PHP mail().

error_reporting(E_ALL);
ini_set('display_errors', 0); // keep display off in production; errors are logged
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// --- Load PHPMailer (try composer autoload, then src/, otherwise fallback) ---
$smtpAvailable = false;
if (defined('USE_SMTP') && USE_SMTP) {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        $smtpAvailable = true;
    } else {
        $srcException = __DIR__ . '/src/Exception.php';
        $srcPHPMailer = __DIR__ . '/src/PHPMailer.php';
        $srcSMTP = __DIR__ . '/src/SMTP.php';
        if (file_exists($srcException) && file_exists($srcPHPMailer) && file_exists($srcSMTP)) {
            require_once $srcException;
            require_once $srcPHPMailer;
            require_once $srcSMTP;
            $smtpAvailable = true;
        } else {
            error_log("PHPMailer requested but vendor/autoload.php and src/ files not found. Falling back to PHP mail().");
            $smtpAvailable = false;
        }
    }
}

// Email helper (uses PHPMailer when available)
function sendEmail($to, $subject, $body, $altBody = '') {
    global $smtpAvailable;
    // Use constants from config.php
    if ($smtpAvailable) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;

            // Map SMTP_SECURE to PHPMailer constants
            $secure = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : 'tls';
            if ($secure === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = SMTP_PORT;

            // Optional: uncomment if you need to relax certificate checks for testing only
            // $mail->SMTPOptions = [
            //     'ssl' => [
            //         'verify_peer' => false,
            //         'verify_peer_name' => false,
            //         'allow_self_signed' => true
            //     ]
            // ];

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log detailed PHPMailer exception
            error_log("PHPMailer send error: " . $e->getMessage());
            return false;
        }
    } else {
        // Fallback to PHP mail()
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        return mail($to, $subject, $body, $headers);
    }
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}

$id = intval($_POST['id'] ?? 0);
$action = (isset($_POST['action']) && $_POST['action'] === 'approve') ? 'approved' : 'rejected';
$admin_note = trim($_POST['admin_note'] ?? '');

try {
    if ($id <= 0) {
        throw new Exception("Invalid booking id.");
    }

    $pdo = getPDO();
    // Get booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $booking = $stmt->fetch();
    if (!$booking) {
        throw new Exception("Booking not found.");
    }

    // Update status and admin_note
    $stmt = $pdo->prepare("UPDATE bookings SET status = :s, admin_note = :n WHERE id = :id");
    $stmt->execute([':s' => $action, ':n' => $admin_note, ':id' => $id]);

    // Compose email
    if ($action === 'approved') {
        $subject = "Your booking #$id has been approved";
        $body = "<p>Hi " . htmlspecialchars($booking['customer_name']) . ",</p>"
              . "<p>Your booking (ID #$id) for <strong>" . htmlspecialchars($booking['service_type']) . "</strong> has been approved by our staff.</p>"
              . "<p>We will contact you to arrange timing. Admin note: " . nl2br(htmlspecialchars($admin_note)) . "</p>"
              . "<p>Thanks,<br>" . MAIL_FROM_NAME . "</p>";
    } else {
        $subject = "Your booking #$id has been rejected";
        $body = "<p>Hi " . htmlspecialchars($booking['customer_name']) . ",</p>"
              . "<p>Unfortunately your booking (ID #$id) has been rejected. Admin note: " . nl2br(htmlspecialchars($admin_note)) . "</p>"
              . "<p>If you have questions contact us at " . htmlspecialchars(ADMIN_EMAIL) . ".</p>";
    }

    // Send notification (if sending fails, we still updated DB; log failure)
    $sent = sendEmail($booking['customer_email'], $subject, $body, strip_tags($body));
    if (!$sent) {
        error_log("Failed to send approval email for booking ID $id to " . $booking['customer_email']);
        // Optionally set a session flash message to show admin that email failed
        // $_SESSION['flash'] = "Booking updated but email could not be sent to the customer.";
    }

    header('Location: admin_dashboard.php');
    exit;

} catch (Exception $e) {
    error_log("Approve error: " . $e->getMessage());
    // Show a safe error message
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit;
}