<?php
declare(strict_types=1);

function base_path(): string
{
  $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
  return rtrim(str_replace('/index.php', '', $script), '/');
}

function url(string $page, array $params = []): string
{
  $q = http_build_query(array_merge(['page' => $page], $params));
  return base_path() . '/index.php?' . $q;
}
?>