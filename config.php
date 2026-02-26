<?php
// config.php - update these values

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'cnwebtr0v1f0_Applicatons');
define('DB_USER', 'cnwebtr0v1f0_clive');
define('DB_PASS', 'Pass0713519090');

// Admin primary email (where new booking notifications are sent)
define('ADMIN_EMAIL', 'moleshiwa.clive@gmail.com');

// Use PHPMailer (recommended) with SMTP? set to true if you will use SMTP
define('USE_SMTP', true);

// SMTP settings (only used if USE_SMTP true)
define('SMTP_HOST', 'mail.cnwebtest.co.za');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'applications@cnwebtest.co.za');
define('SMTP_PASSWORD', 'PassCN_Web_Solutions@2025');
define('SMTP_SECURE', 'ssl'); // 'tls' or 'ssl'





// From address for outgoing emails
define('MAIL_FROM_ADDRESS', 'applications@cnwebtest.co.za');
define('MAIL_FROM_NAME', 'Car Service');

// Uploads directory relative to this file
define('UPLOAD_DIR', __DIR__ . '/uploads');

// Max upload file size in bytes (e.g., 5MB)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// Allowed upload MIME types (images)
$ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];