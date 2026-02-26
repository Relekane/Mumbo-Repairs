<?php
// health_mail.php - quick environment checks
require_once __DIR__ . '/config.php';

echo "USE_SMTP = " . (defined('USE_SMTP') && USE_SMTP ? 'true' : 'false') . "<br>";
echo "SMTP_HOST = " . (defined('SMTP_HOST') ? SMTP_HOST : '(not defined)') . "<br>";
echo "SMTP_PORT = " . (defined('SMTP_PORT') ? SMTP_PORT : '(not defined)') . "<br>";
echo "MAIL_FROM_ADDRESS = " . (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : '(not defined)') . "<br>";

echo "<hr>";
echo "Checking vendor/autoload.php: ";
$autoload = __DIR__ . '/vendor/autoload.php';
echo file_exists($autoload) ? 'found' : 'NOT FOUND';
echo "<br>";

echo "Checking src PHPMailer files: ";
echo (file_exists(__DIR__ . '/src/PHPMailer.php') && file_exists(__DIR__ . '/src/SMTP.php')) ? 'found' : 'NOT FOUND';
echo "<br>";

echo "OpenSSL extension: " . (extension_loaded('openssl') ? 'loaded' : 'NOT loaded') . "<br>";
echo "PHP version: " . PHP_VERSION . "<br>";