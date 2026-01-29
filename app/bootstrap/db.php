<?php
declare(strict_types=1);

$dbhost = getenv('APP_DB_HOST');
$dbname = getenv('APP_DB_NAME');
$dbuser = getenv('APP_DB_USER');
$dbpass = getenv('APP_DB_PASS');

if (
  $dbhost === false || $dbhost === '' ||
  $dbname === false || $dbname === '' ||
  $dbuser === false || $dbuser === '' ||
  $dbpass === false || $dbpass === ''
) {
  http_response_code(500);
  echo "Server configuration error.";
  exit();
}

$dsn = 'mysql:host=' . $dbhost . ';dbname=' . $dbname . ';charset=utf8mb4';

try {
  $pdo = new PDO(
    $dsn,
    (string)$dbuser,
    (string)$dbpass,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (PDOException $e) {
  error_log('DB connection error: ' . $e->getMessage());
  http_response_code(500);
  echo "Database connection error.";
  exit();
}

$GLOBALS['pdo'] = $pdo;
?>