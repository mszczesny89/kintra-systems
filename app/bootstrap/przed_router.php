<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__dev_role_switch'])) {
  if (!is_logged()) {
    http_response_code(403);
    exit('Not logged');
  }

  $userId = (int)$_SESSION['user_id'];

  $roleIdRaw = $_POST['dev_role_id'] ?? '';
  $roleId = ($roleIdRaw === '' ? null : (int)$roleIdRaw);

  if ($roleId === null) {
    unset($_SESSION['effective_role_id']);
    // wróć do realnych ról usera
    load_permissions_to_session($pdo, $userId, null);
    load_roles_to_session($pdo, $userId, null);
  } else {
    // (bezpiecznie) pozwól przełączyć tylko na role przypisane userowi
    $chk = $pdo->prepare("SELECT 1 FROM user_roles WHERE user_id = :u AND role_id = :r LIMIT 1");
    $chk->execute([':u' => $userId, ':r' => $roleId]);
    if (!$chk->fetchColumn()) {
      http_response_code(400);
      exit('Ta rola nie jest przypisana do usera');
    }

    $_SESSION['effective_role_id'] = $roleId;
    load_permissions_to_session($pdo, $userId, $roleId);
    load_roles_to_session($pdo, $userId, $roleId);
  }

  $url = strtok($_SERVER['REQUEST_URI'], '#');
  header("Location: {$url}", true, 303);
  exit;
}
?>