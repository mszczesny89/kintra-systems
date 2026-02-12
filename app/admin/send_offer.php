<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//require_once '/usr/share/php/libphp-phpmailer/autoload.php';

/**
 * REQUIRE: $pdo musi istnieć wcześniej
 */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'PDO connection not available.';
    exit;
}

function json_out(int $code, array $payload): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_email(string $email): string {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid recipient email.');
    }
    return $email;
}

function clean_header_value(string $value): string {
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

/**
 * POST only
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, ['ok' => false]);
}

try {

    $recipientEmail = clean_email((string)($_POST['recipient_email'] ?? ''));
    $offerKey       = clean_header_value((string)($_POST['offer_key'] ?? ''));
    $subject        = clean_header_value((string)($_POST['subject'] ?? ''));
    $htmlBody       = (string)($_POST['html'] ?? '');
    $textBody       = (string)($_POST['text'] ?? '');

    if ($offerKey === '' || mb_strlen($offerKey) > 80) {
        throw new RuntimeException('Invalid offer_key.');
    }

    if ($subject === '') {
        throw new RuntimeException('Subject required.');
    }

    if ($htmlBody === '') {
        throw new RuntimeException('HTML body required.');
    }

    if ($textBody === '') {
        $textBody = trim(strip_tags($htmlBody));
    }

    /**
     * SMTP ENV
     */
    $host = getenv('APP_SMTP_HOST');
    $port = (int)(getenv('APP_SMTP_PORT') ?: 587);
    $user = getenv('APP_SMTP_USER');
    $pass = getenv('APP_SMTP_PASS');
    $from = getenv('APP_SMTP_FROM') ?: $user;

    if (!$host || !$user || !$pass || !$from) {
        throw new RuntimeException('SMTP configuration missing.');
    }

    /**
     * DEDUPE HASH
     */
    $messageHash = hash('sha256', $recipientEmail . '|' . $offerKey);

    /**
     * 1️⃣ INSERT (idempotencja)
     */
    $logId = null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO offer_send_log
              (recipient_email, offer_key, message_hash, status, attempts)
            VALUES
              (:recipient, :offer_key, :hash, 'pending', 0)
        ");

        $stmt->execute([
            ':recipient' => $recipientEmail,
            ':offer_key' => $offerKey,
            ':hash'      => $messageHash,
        ]);

        $logId = (int)$pdo->lastInsertId();

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            // Duplicate
            json_out(409, [
                'ok' => true,
                'status' => 'duplicate'
            ]);
        }

        throw $e;
    }

    /**
     * 2️⃣ SMTP SEND
     */
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // FROM = info@kintra-systems.com
        $mail->setFrom($from, 'Kintra Systems');

        // Reply też wraca do info@
        $mail->addReplyTo($from, 'Kintra Systems');

        $mail->addAddress($recipientEmail);

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();

        /**
         * 3️⃣ UPDATE -> sent
         */
        $upd = $pdo->prepare("
            UPDATE offer_send_log
            SET status='sent',
                sent_at=NOW(),
                attempts=attempts+1,
                last_error=NULL
            WHERE id=:id
        ");

        $upd->execute([':id' => $logId]);

        json_out(200, [
            'ok' => true,
            'status' => 'sent',
            'log_id' => $logId
        ]);

    } catch (Throwable $e) {

        $err = $mail->ErrorInfo ?: $e->getMessage();

        $upd = $pdo->prepare("
            UPDATE offer_send_log
            SET status='failed',
                attempts=attempts+1,
                last_error=:err
            WHERE id=:id
        ");

        $upd->execute([
            ':id'  => $logId,
            ':err' => mb_substr((string)$err, 0, 65000)
        ]);

        json_out(500, [
            'ok' => false,
            'status' => 'failed'
        ]);
    }

} catch (Throwable $e) {

    json_out(400, [
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
