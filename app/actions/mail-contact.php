<?php
/**
 * CONTACT FORM HANDLER (PHPMailer + SMTP, HTML email)
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/**
 * CONFIG
 */
$to            = 'info@kintra-systems.com';
$siteName      = 'Kintra Systems';
$subjectPrefix = '[Contact]';

$errors  = [];
$success = false;

/**
 * CSRF token
 */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Helpers
 */
function post(string $key): string {
  return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function is_valid_email(string $email): bool {
  return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function clean_header_value(string $value): string {
  // Basic header injection prevention
  $value = str_replace(["\r", "\n"], ' ', $value);
  return trim($value);
}

function h(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * PHPMailer autoload (Debian/Ubuntu path)
 */
require_once '/usr/share/php/libphp-phpmailer/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Main
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // CSRF
  $csrf = post('csrf_token');
  if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    $errors[] = 'Invalid request token. Refresh the page and try again.';
  }

  // Honeypot (should be empty)
  $company = post('company');
  if ($company !== '') {
    $errors[] = 'Spam detected.';
  }

  // Inputs
  $name    = post('name');
  $email   = post('email');
  $message = post('message');

  // Limits
  if (mb_strlen($name) > 80)     $errors[] = 'Name is too long.';
  if (mb_strlen($email) > 120)   $errors[] = 'Email is too long.';
  if (mb_strlen($message) > 5000) $errors[] = 'Message is too long.';

  // Required
  if ($name === '')    $errors[] = 'Name is required.';
  if ($email === '')   $errors[] = 'Email is required.';
  if ($message === '') $errors[] = 'Message is required.';

  // Email validate
  if ($email !== '' && !is_valid_email($email)) {
    $errors[] = 'Email address is invalid.';
  }

  // Sanitize for headers
  $safeName  = clean_header_value($name);
  $safeEmail = clean_header_value($email);

  if (!$errors) {

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
    $date = date('Y-m-d H:i:s');

    // Subject (strip CRLF)
    $subject = $subjectPrefix . ' New message from ' . $safeName;
    $subject = preg_replace("/[\r\n]+/", ' ', (string)$subject);

    /**
     * Build HTML Body (safe)
     */
    $safeMessageHtml = nl2br(h($message));

    $body = '
<div style="font-family: Arial, sans-serif; font-size: 14px; color:#111;">
  <h2 style="margin:0 0 12px 0;">New message from ' . h($siteName) . '</h2>

  <table cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:700px;">
    <tr>
      <td style="padding:8px 0; width:120px;"><strong>Name:</strong></td>
      <td style="padding:8px 0;">' . h($safeName) . '</td>
    </tr>
    <tr>
      <td style="padding:8px 0;"><strong>Email:</strong></td>
      <td style="padding:8px 0;">' . h($safeEmail) . '</td>
    </tr>
    <tr>
      <td style="padding:8px 0;"><strong>IP:</strong></td>
      <td style="padding:8px 0;">' . h((string)$ip) . '</td>
    </tr>
    <tr>
      <td style="padding:8px 0;"><strong>Date:</strong></td>
      <td style="padding:8px 0;">' . h($date) . '</td>
    </tr>
  </table>

  <hr style="border:none; border-top:1px solid #ddd; margin:16px 0;">

  <div style="margin:0 0 6px 0;"><strong>Message:</strong></div>
  <div style="line-height:1.5;">' . $safeMessageHtml . '</div>
</div>
';

    /**
     * AltBody (plain text, proper CRLF)
     */
    $altLines = [
      "New message from {$siteName}",
      "----------------------------------------",
      "Name: {$safeName}",
      "Email: {$safeEmail}",
      "IP: {$ip}",
      "Date: {$date}",
      "----------------------------------------",
      "Message:",
      $message
    ];
    $altBody = implode("\r\n", $altLines);

    try {
      $mail = new PHPMailer(true);

      $mail->CharSet = 'UTF-8';
      $mail->isSMTP();

      $host = getenv('APP_SMTP_HOST');
      $port = getenv('APP_SMTP_PORT');
      $user = getenv('APP_SMTP_USER');
      $pass = getenv('APP_SMTP_PASS');
      $from = getenv('APP_SMTP_FROM');

      if (!$host || !$port || !$user || !$pass || !$from) {
        throw new RuntimeException('SMTP configuration missing in .env');
      }

      if (!filter_var($safeEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid reply-to email.');
      }

      // SMTP config
      $mail->Host       = $host;
      $mail->SMTPAuth   = true;
      $mail->Username   = $user;
      $mail->Password   = $pass;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = (int)$port;

      // Headers / recipients
      $mail->setFrom($from, $siteName);
      $mail->addAddress($to);
      $mail->addReplyTo($safeEmail, $safeName);

      // Content
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $body;
      $mail->AltBody = $altBody;

      $mail->send();

        incCounter($pdo, 'messages_sent_total', 1);
        incDaily($pdo, 'messages_sent', 1); // opcjonalnie

      $success = true;

      // Clear fields after success (if you render them back)
      $name = $email = $message = '';

      // Rotate CSRF token
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    } catch (Throwable $e) {
      $errors[] = 'Mail sending failed: ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage());
    }
  }
}
?>
