<?php
declare(strict_types=1);

$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) {
  return;
}

if (isset($_SESSION['user_id'])) {
  $now  = time();
  $last = (int)($_SESSION['last_seen_update'] ?? 0);

  if ($now - $last >= 300) {
    $_SESSION['last_seen_update'] = $now;

    $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
    $stmt->execute([(int)$_SESSION['user_id']]);
  }
}
?>