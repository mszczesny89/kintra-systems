<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_ACTIVE) {
  return;
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$https =
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'); // tylko jeśli ufasz proxy

session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',
  'secure'   => $https,
  'httponly' => true,
  'samesite' => 'Strict',
]);

session_name('APPSESSID');
session_start();

/**
 * Udostępniamy zmienną $https globalnie dla innych modułów (cookies, remember_me).
 * W PHP bez kontenera DI to najprostsze.
 */
$GLOBALS['APP_HTTPS'] = $https;
?>