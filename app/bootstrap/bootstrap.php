<?php
declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
  return;
}
define('APP_BOOTSTRAPPED', true);

require_once __DIR__ . '/env.php';

// 1) DB najpierw (żeby mieć $pdo)
require_once __DIR__ . '/db.php';

// 2) Session start
require_once __DIR__ . '/session.php';

// 3) URL helpery (często potrzebne wcześniej)
require_once __DIR__ . '/urls.php';

// 4) Auth + RBAC helpery (definicje is_logged/can/load_* itd.)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permision.php'; // literówka w nazwie? jeśli plik ma być permission.php, to popraw

// 5) CSRF (może być tu, byle po session_start)
require_once dirname(__DIR__) . '/core/csrf.php';

// 6) Pozostałe (mogą korzystać z sesji/auth/db)
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/remember_me.php';
require_once __DIR__ . '/activity.php';

// 7) Auto-load perms/roles do sesji (po auth + po $pdo)
if (function_exists('is_logged') && is_logged()) {
  if (empty($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
    if (function_exists('load_roles_to_session')) {
      load_roles_to_session($pdo, (int)$_SESSION['user_id']);
    }
  }

  if (empty($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
    if (function_exists('load_permissions_to_session')) {
      load_permissions_to_session($pdo, (int)$_SESSION['user_id']);
    }
  }
}
