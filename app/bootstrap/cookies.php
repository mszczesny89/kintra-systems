<?php
declare(strict_types=1);

function cookie_set(string $name, string $value, int $expires, bool $secure): void
{
  setcookie($name, $value, [
    'expires'  => $expires,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Strict',
  ]);
}

function cookie_delete(string $name, bool $secure): void
{
  setcookie($name, '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Strict',
  ]);
}
?>