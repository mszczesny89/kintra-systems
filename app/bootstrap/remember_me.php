<?php
declare(strict_types=1);

/* ===== Remember-me autologin (z rotacją) =====
   DB: remember_tokens: id, user_id, token_hash, expires_at, created_at, last_used_at
*/

$pdo = $GLOBALS['pdo'] ?? null;
$https = (bool) ($GLOBALS['APP_HTTPS'] ?? false);

if (!$pdo instanceof PDO) {
  return;
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me']) && is_string($_COOKIE['remember_me'])) {
  $token = $_COOKIE['remember_me'];

  if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("
      SELECT id, user_id
      FROM remember_tokens
      WHERE token_hash = ? AND expires_at > NOW()
      LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $rt = $stmt->fetch();

    if ($rt) {
      $stmt = $pdo->prepare("
        SELECT u.id, u.username, COALESCE(r.name, 'user') AS role
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        WHERE u.id = ?
        LIMIT 1
      ");
      $stmt->execute([(int) $rt['user_id']]);
      $user = $stmt->fetch();


      if ($user) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        $_SESSION['role'] = (string) ($user['role'] ?? 'user');

        session_regenerate_id(true);

        $pdo->beginTransaction();
        try {
          $del = $pdo->prepare("DELETE FROM remember_tokens WHERE id = ?");
          $del->execute([(int) $rt['id']]);

          $newToken = bin2hex(random_bytes(32));
          $newHash = hash('sha256', $newToken);
          $exp = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

          $ins = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at, last_used_at)
            VALUES (?, ?, ?, NOW(), NOW())
          ");
          $ins->execute([(int) $user['id'], $newHash, $exp]);

          $pdo->commit();

          cookie_set('remember_me', $newToken, time() + 60 * 60 * 24 * 30, $https);
        } catch (Throwable $e) {
          $pdo->rollBack();
          cookie_delete('remember_me', $https);
        }

        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([(int) $user['id']]);
      } else {
        cookie_delete('remember_me', $https);
      }
    } else {
      cookie_delete('remember_me', $https);
    }
  } else {
    cookie_delete('remember_me', $https);
  }
}
?>