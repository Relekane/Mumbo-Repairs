<?php
// submit_booking.php
// Handles booking form (and a simple contact-like fallback), stores to DB and sends email via PHPMailer.
// Update: robust loader for PHPMailer (vendor/autoload.php OR src/ files), safe fallbacks to PHP mail().

// DEBUG - enable while you test; remove or set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Load PHPMailer (try composer autoload, then src/ files, otherwise fallback) ---
$smtpAvailable = false;
if (defined('USE_SMTP') && USE_SMTP) {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        $smtpAvailable = true;
    } else {
        // Try the user's provided src/ files (PHPMailer placed in project/src/)
        $srcException = __DIR__ . '/src/Exception.php';
        $srcPHPMailer = __DIR__ . '/src/PHPMailer.php';
        $srcSMTP = __DIR__ . '/src/SMTP.php';
        if (file_exists($srcException) && file_exists($srcPHPMailer) && file_exists($srcSMTP)) {
            require_once $srcException;
            require_once $srcPHPMailer;
            require_once $srcSMTP;
            $smtpAvailable = true;
        } else {
            error_log("PHPMailer requested but autoload.php and src/ files not found. Falling back to PHP mail(). Install PHPMailer with Composer or upload src/ or vendor/ folder.");
            $smtpAvailable = false;
        }
    }
}

// Allowed image MIME types and upload limits
$ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB default
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');

// Helper: sanitize simple text
function clean($s) {
    return trim($s === null ? '' : (string)$s);
}

// Helper: send email using PHPMailer if available, otherwise PHP mail()
function sendEmail($to, $subject, $htmlBody, $altBody = '') {
    global $smtpAvailable;
    // Use global config constants
    if ($smtpAvailable) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;

            // Map secure setting
            if (defined('SMTP_SECURE') && strtolower(SMTP_SECURE) === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                // default to STARTTLS when 'tls' or anything else
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = SMTP_PORT;
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody;
            // send
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer send error: " . $e->getMessage());
            return false;
        }
    } else {
        // Basic PHP mail() fallback (HTML headers)
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        return mail($to, $subject, $htmlBody, $headers);
    }
}

// Ensure uploads dir exists
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Invalid request method.";
    exit;
}

// Determine form type:
// - Booking form uses fields like customer_name/customer_email/customer_phone/service_type
// - Fallback contact-like form uses name/email/subject/message
$isBooking = !empty($_POST['customer_name']) || !empty($_POST['customer_email']) || !empty($_POST['service_type']);

// Gather data (support both formats)
if ($isBooking) {
    $customer_name  = clean($_POST['customer_name'] ?? '');
    $customer_email = clean($_POST['customer_email'] ?? '');
    $customer_phone = clean($_POST['customer_phone'] ?? '');
    $car_make       = clean($_POST['car_make'] ?? null);
    $car_model      = clean($_POST['car_model'] ?? null);
    $car_year       = clean($_POST['car_year'] ?? null);
    $license_plate  = clean($_POST['license_plate'] ?? null);
    $service_type   = clean($_POST['service_type'] ?? '');
    $details        = clean($_POST['details'] ?? null);
} else {
    // map contact form fields into booking columns so they are still stored
    $customer_name  = clean($_POST['name'] ?? '');
    $customer_email = clean($_POST['email'] ?? '');
    $customer_phone = ''; // not present in contact form
    $car_make       = null;
    $car_model      = null;
    $car_year       = null;
    $license_plate  = null;
    $service_type   = clean($_POST['subject'] ?? 'Contact form');
    $details        = clean($_POST['message'] ?? null);
}

// Basic validation
if ($customer_name === '' || $customer_email === '' || $service_type === '') {
    echo "Please fill in the required fields (name, email, service/subject).";
    exit;
}
if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address.";
    exit;
}

// Handle optional file upload (field names: image OR attachment)
$image_path = null;
$uploadField = null;
if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $uploadField = 'image';
} elseif (!empty($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $uploadField = 'attachment';
}

if ($uploadField !== null) {
    $f = $_FILES[$uploadField];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error code: " . $f['error']);
        echo "File upload error.";
        exit;
    }
    if ($f['size'] > MAX_UPLOAD_SIZE) {
        echo "Uploaded file is too large. Max " . (MAX_UPLOAD_SIZE/1024/1024) . " MB.";
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $ALLOWED_MIME)) {
        echo "Invalid file type. Allowed types: jpg, png, gif, webp.";
        exit;
    }
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $basename;
    if (!move_uploaded_file($f['tmp_name'], $destination)) {
        error_log("Failed to move uploaded file to $destination");
        echo "Failed to save uploaded file.";
        exit;
    }
    // Store web-accessible relative path (adjust if your public path differs)
    $image_path = 'uploads/' . $basename;
}

// Save booking into DB
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO bookings
      (customer_name, customer_email, customer_phone, car_make, car_model, car_year, license_plate, service_type, details, image_path)
      VALUES (:name, :email, :phone, :car_make, :car_model, :car_year, :license_plate, :service_type, :details, :image_path)");
    $stmt->execute([
        ':name' => $customer_name,
        ':email' => $customer_email,
        ':phone' => $customer_phone,
        ':car_make' => $car_make,
        ':car_model' => $car_model,
        ':car_year' => $car_year,
        ':license_plate' => $license_plate,
        ':service_type' => $service_type,
        ':details' => $details,
        ':image_path' => $image_path
    ]);
    $bookingId = $pdo->lastInsertId();
} catch (Exception $e) {
    error_log("DB insert error: " . $e->getMessage());
    echo "There was a problem saving your booking. Try again later.";
    exit;
}

// Build email content
$html = "<h2>New Booking / Contact Submission</h2>";
$html .= "<p><strong>ID:</strong> " . htmlspecialchars($bookingId) . "</p>";
$html .= "<p><strong>Name:</strong> " . htmlspecialchars($customer_name) . "</p>";
$html .= "<p><strong>Email:</strong> " . htmlspecialchars($customer_email) . "</p>";
if ($customer_phone) $html .= "<p><strong>Phone:</strong> " . htmlspecialchars($customer_phone) . "</p>";
if ($car_make || $car_model || $car_year) {
    $html .= "<p><strong>Car:</strong> " . htmlspecialchars(implode(' ', array_filter([$car_make, $car_model, $car_year]))) . "</p>";
}
if ($license_plate) $html .= "<p><strong>License:</strong> " . htmlspecialchars($license_plate) . "</p>";
$html .= "<p><strong>Service / Subject:</strong> " . htmlspecialchars($service_type) . "</p>";
if ($details) $html .= "<p><strong>Details / Message:</strong><br>" . nl2br(htmlspecialchars($details)) . "</p>";
if ($image_path) {
    // try to provide a link to uploaded file (assumes public web root contains this script's folder)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scriptDir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $imageUrl = $protocol . '://' . $host . $scriptDir . '/' . $image_path;
    $html .= "<p><strong>Attachment:</strong> <a href=\"" . htmlspecialchars($imageUrl) . "\" target=\"_blank\">View</a></p>";
}

// Send notification to admin
$subject = "New booking request (#$bookingId) - " . $service_type;
$sent = sendEmail(ADMIN_EMAIL, $subject, $html, strip_tags($html));

if ($sent) {
    // If contact form style, return simple message, else booking confirmation
    if ($isBooking) {
        echo "<!doctype html><html><body><h2>Booking submitted</h2><p>Thank you, your booking request has been submitted and is pending admin approval. Your booking ID is <strong>$bookingId</strong>.</p></body></html>";
    } else {
        echo "Message sent and saved (ID: $bookingId).";
    }
} else {
    // Email failed but DB saved. Inform user but note admin hasn't been emailed.
    error_log("Email send failed for booking ID $bookingId");
    echo "Your booking was saved (ID: $bookingId) but we could not send notification email to admin. Please contact the workshop directly.";
}

exit;