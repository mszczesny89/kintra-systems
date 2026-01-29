<?php
declare(strict_types=1);

$envPath = dirname(__DIR__, 2) . '/.env';
if (!file_exists($envPath)) {
  return;
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
  return;
}

foreach ($lines as $i => $line) {
  $line = str_replace("\r", "", $line);

  if ($i === 0) {
    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line); // BOM
  }

  $line = trim($line);

  if ($line === '' || str_starts_with($line, '#')) continue;
  if (!str_contains($line, '=')) continue;

  [$k, $v] = explode('=', $line, 2);
  $k = trim($k);
  $v = trim($v);

  if ($k === '') continue;

  if (
    (str_starts_with($v, '"') && str_ends_with($v, '"')) ||
    (str_starts_with($v, "'") && str_ends_with($v, "'"))
  ) {
    $v = substr($v, 1, -1);
  }

  // Nadpisuj zawsze w ramach requestu
  putenv($k . '=' . $v);

  // Opcjonalnie: �eby by�o widoczne te� w $_ENV/$_SERVER (czasem wygodne)
  $_ENV[$k] = $v;
  $_SERVER[$k] = $v;
}
?>