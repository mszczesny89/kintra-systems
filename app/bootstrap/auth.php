<?php
declare(strict_types=1);

function is_logged(): bool
{
  // bywa string, więc nie blokujemy
  return isset($_SESSION['user_id']) && (is_int($_SESSION['user_id']) || ctype_digit((string)$_SESSION['user_id']));
}

function can(string $permission): bool
{
  if (!is_logged()) return false;
  if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) return false;

  return !empty($_SESSION['permissions'][$permission]);
}

function load_permissions_to_session(PDO $pdo, int $userId, ?int $forceRoleId = null): void
{
  $sql = "
    SELECT DISTINCT p.name
    FROM permissions p
    JOIN role_permissions rp ON rp.permission_id = p.id
    JOIN user_roles ur ON ur.role_id = rp.role_id
    WHERE ur.user_id = :uid
  ";

  $params = [':uid' => $userId];

  if ($forceRoleId !== null) {
    $sql .= " AND ur.role_id = :rid";
    $params[':rid'] = $forceRoleId;
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $perms = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $name = (string)$row['name'];
    $perms[$name] = true;
  }

  $_SESSION['permissions'] = $perms;
}

function load_roles_to_session(PDO $pdo, int $userId, ?int $forceRoleId = null): void
{
  $sql = "
    SELECT r.name
    FROM roles r
    JOIN user_roles ur ON ur.role_id = r.id
    WHERE ur.user_id = :uid
  ";

  $params = [':uid' => $userId];

  if ($forceRoleId !== null) {
    $sql .= " AND ur.role_id = :rid";
    $params[':rid'] = $forceRoleId;
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $roles = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roles[] = (string)$row['name'];
  }

  $_SESSION['roles'] = $roles;
}

function require_permission(?string $permission): void
{
  if ($permission === null) return;

  if (!is_logged()) {
    header('Location: ' . url('login'));
    exit;
  }

  if (!can($permission)) {
    http_response_code(403);
    require __DIR__ . '/../views/403.php';
    exit;
  }
}
?>