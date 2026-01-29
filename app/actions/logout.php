<?php
declare(strict_types=1);

/**
 * /php/logout.php
 * - POST-only
 * - Usuwa remember token z DB (jeśli jest)
 * - Usuwa cookie remember_me
 * - Czyści i niszczy sesję
 * - Redirect 303 na home
 */

// ====== POST-only ======
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . url('home'), true, 303);
    exit;
}

// ====== (Opcjonalnie) debug gdy nagłówki już wysłane ======
if (headers_sent($file, $line)) {
    http_response_code(500);
    echo "Headers already sent in {$file} on line {$line}";
    exit;
}

// ====== Usuń token z DB, jeśli istnieje ======
if (isset($_COOKIE['remember_me']) && is_string($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];

    if (preg_match('/\A[a-f0-9]{64}\z/i', $token) === 1) {
        $hash = hash('sha256', $token);

        // jeśli $pdo nie jest ustawione, tu byłby fatal wcześniej w kodzie
        $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE token_hash = ?');
        $stmt->execute([$hash]);
    }
}

// ====== Usuń cookie remember_me ======
cookie_delete('remember_me', $https);

// ====== Wyczyść sesję ======
$_SESSION = [];

// Usuń cookie sesyjne (PHPSESSID) jeśli używasz cookies
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'] ?? '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => 'Strict',
    ]);
}

// Finalnie niszczymy sesję
session_destroy();

// ====== Redirect ======
header('Location: ' . url('home'), true, 303);
exit;
?>
