<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify_or_die(string $token): void
{
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sess) || !hash_equals($sess, $token)) {
        http_response_code(400);
        echo "Bad Request (CSRF)";
        exit;
    }
}
