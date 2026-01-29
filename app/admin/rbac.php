<?php
declare(strict_types=1);
require __DIR__ . '/../layout/menu.php';
?>
<?php

/**
 * pages/page_admin/rbac.php
 * RBAC manager (AJAX autosave):
 * - User Roles: lista wszystkich userów + dropdown roli (auto-save)
 * - Role Permissions: wszystkie role naraz + checkboxy (auto-save)
 */

require_once __DIR__ . '/../../app/core/csrf.php';

// hard guard (router powinien też chronić)
if (!function_exists('is_logged') || !function_exists('can') || !is_logged() || !can('rbac.manage')) {
  http_response_code(403);
  echo "403";
  exit;
}

function int_from_post(string $key): int
{
  $v = $_POST[$key] ?? null;
  return (is_string($v) && ctype_digit($v)) ? (int) $v : 0;
}

function int_array_from_post(string $key): array
{
  $raw = $_POST[$key] ?? [];
  if (!is_array($raw))
    return [];
  $out = [];
  foreach ($raw as $v) {
    if (is_string($v) && ctype_digit($v))
      $out[] = (int) $v;
    if (is_int($v) && $v > 0)
      $out[] = $v;
  }
  return array_values(array_unique(array_filter($out, fn($x) => $x > 0)));
}

function ajax_json(array $payload, int $status = 200): void
{
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/**
 * Odśwież role + uprawnienia w sesji (jeśli masz te helpery w auth.php).
 * - Jeśli edytujesz rolę/permy aktualnie zalogowanego usera -> UI i navbar aktualizują się od razu.
 */
function refresh_auth_session(PDO $pdo, int $userId): void
{
  if (!function_exists('load_roles_to_session') || !function_exists('load_permissions_to_session'))
    return;

  // zabezpieczenie: refresh tylko dla usera w sesji
  $sid = $_SESSION['user_id'] ?? null;
  if ($sid === null)
    return;

  // bywa string
  if (!(is_int($sid) || (is_string($sid) && ctype_digit($sid))))
    return;
  if ((int) $sid !== $userId)
    return;

  load_roles_to_session($pdo, $userId);
  load_permissions_to_session($pdo, $userId);
}

$selfPage = 'rbac';

$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] === '1')
  || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
  || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'));

$tab = (string) ($_GET['tab'] ?? 'user_roles');
$tab = in_array($tab, ['user_roles', 'role_perms'], true) ? $tab : 'user_roles';

$csrf = csrf_token();

/* =========================
   AJAX/POST actions
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_verify_or_die((string) ($_POST['csrf'] ?? ''));
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'set_user_role') {
      $userId = int_from_post('user_id');
      $roleId = int_from_post('role_id');

      if ($userId <= 0 || $roleId <= 0) {
        throw new RuntimeException('Invalid input');
      }

      $pdo->beginTransaction();
      try {
        // UI zakłada 1 rolę => czyścimy i wstawiamy jedną
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);

        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
        $stmt->execute([':uid' => $userId, ':rid' => $roleId]);

        $pdo->commit();
      } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
      }

      // refresh sesji jeśli zmieniłeś rolę SAMEMU SOBIE
      refresh_auth_session($pdo, $userId);

      if ($isAjax)
        ajax_json(['ok' => true, 'msg' => 'Zapisano rolę użytkownika.']);
      exit;
    }

    if ($action === 'save_role_perms') {
      $roleId = int_from_post('role_id');
      $permIds = int_array_from_post('perm_ids');

      if ($roleId <= 0) {
        throw new RuntimeException('Invalid role_id');
      }

      $pdo->beginTransaction();
      try {
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :rid");
        $stmt->execute([':rid' => $roleId]);

        if ($permIds) {
          $ins = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)");
          foreach ($permIds as $pid) {
            $ins->execute([':rid' => $roleId, ':pid' => $pid]);
          }
        }

        $pdo->commit();
      } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
      }

      /**
       * Tu nie wiemy, czy edytowana rola należy do obecnego usera.
       * Najprościej: jeśli jesteś zalogowany -> doładuj permsy z DB dla siebie.
       * (koszt marginalny, a UX natychmiastowy).
       */
      if (isset($_SESSION['user_id']) && (is_int($_SESSION['user_id']) || ctype_digit((string) $_SESSION['user_id']))) {
        $sid = (int) $_SESSION['user_id'];
        if (function_exists('load_roles_to_session'))
          load_roles_to_session($pdo, $sid);
        if (function_exists('load_permissions_to_session'))
          load_permissions_to_session($pdo, $sid);
      }

      if ($isAjax)
        ajax_json(['ok' => true, 'msg' => 'Zapisano uprawnienia roli.']);
      exit;
    }

    throw new RuntimeException('Unknown action');

  } catch (Throwable $e) {
    if ($isAjax)
      ajax_json(['ok' => false, 'err' => $e->getMessage()], 400);

    http_response_code(400);
    echo "Błąd: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
  }
}

/* =========================
   Data for UI (GET)
   ========================= */

// roles
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// users + current role (UI zakłada 1 rolę na usera)
$usersRows = $pdo->query("
    SELECT u.id, u.username, ur.role_id
    FROM users u
    LEFT JOIN user_roles ur ON ur.user_id = u.id
    ORDER BY u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

// perms (with labels)
$perms = $pdo->query("
    SELECT id, name, label, perm_group
    FROM permissions
    ORDER BY perm_group ASC, sort_order ASC, label ASC, name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// permissions per role -> map
$rolePermMap = [];
$stmt = $pdo->query("SELECT role_id, permission_id FROM role_permissions");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $rid = (int) $row['role_id'];
  $pid = (int) $row['permission_id'];
  $rolePermMap[$rid][$pid] = true;
}
?>

<style>
  .rbac-wrap {
    max-width: 980px;
  }

  .rbac-tabs a {
    margin-right: 10px;
  }

  .rbac-toast {
    margin: 10px 0;
    padding: 8px 10px;
    border-radius: 6px;
    display: none;
  }

  .rbac-toast.ok {
    display: block;
    border: 1px solid rgba(0, 128, 0, .35);
  }

  .rbac-toast.err {
    display: block;
    border: 1px solid rgba(255, 0, 0, .35);
  }

  .rbac-table {
    width: 100%;
    border-collapse: collapse;
  }

  .rbac-table th,
  .rbac-table td {
    padding: 10px 8px;
    border-bottom: 1px solid rgba(255, 255, 255, .08);
    text-align: left;
  }

  .rbac-small {
    opacity: .7;
    font-size: 12px;
  }

.rbac-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    align-items: flex-start;
}

.rbac-grid section {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: transparent;
}

  @media (max-width: 820px) {
    .rbac-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="rbac-wrap">
  <h1>RBAC</h1>

  <div id="rbac-toast" class="rbac-toast"></div>

  <nav class="rbac-tabs" style="margin:12px 0;">
    <a href="index.php?page=<?= htmlspecialchars($selfPage, ENT_QUOTES, 'UTF-8') ?>&tab=user_roles">User Roles</a>
    <a href="index.php?page=<?= htmlspecialchars($selfPage, ENT_QUOTES, 'UTF-8') ?>&tab=role_perms">Role Permissions</a>
  </nav>

  <?php if ($tab === 'user_roles'): ?>

    <h2>User Roles</h2>
    <p class="rbac-small">Zmiana roli zapisuje się automatycznie.</p>

    <table class="rbac-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usersRows as $row): ?>
          <?php
          $uid = (int) $row['id'];
          $currentRoleId = isset($row['role_id']) ? (int) $row['role_id'] : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <form method="post" action="index.php?page=rbac&ajax=1" data-ajax="1" data-kind="user-role" style="margin:0;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="set_user_role">
                <input type="hidden" name="user_id" value="<?= $uid ?>">

                <select name="role_id">
                  <?php foreach ($roles as $r): ?>
                    <?php $rid = (int) $r['id']; ?>
                    <option value="<?= $rid ?>" <?= $rid === $currentRoleId ? 'selected' : '' ?>>
                      <?= htmlspecialchars((string) $r['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>

                <span class="rbac-small" data-status></span>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php else: ?>

    <h2>Role Permissions</h2>
    <p class="rbac-small">Checkboxy zapisują się automatycznie. Każda rola ma własny zestaw.</p>
    <div class="rbac-grid">
      <?php foreach ($roles as $r): ?>
        <?php
        $rid = (int) $r['id'];
        $set = $rolePermMap[$rid] ?? [];
        ?>

        <section style="margin:20px 0 30px;">
          <h3><?= htmlspecialchars(ucfirst((string) $r['name']), ENT_QUOTES, 'UTF-8') ?></h3>

          <form method="post" action="index.php?page=rbac&ajax=1" data-ajax="1" data-kind="role-perms">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_role_perms">
            <input type="hidden" name="role_id" value="<?= $rid ?>">

            <?php foreach ($perms as $p): ?>
              <?php
              $pid = (int) $p['id'];
              $checked = isset($set[$pid]);
              ?>
              <label style="display:block; margin:6px 0;">
                <input type="checkbox" name="perm_ids[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?>>
                <?= htmlspecialchars(($p['label'] ?: $p['name']) ?? $p['name'], ENT_QUOTES, 'UTF-8') ?>
                <span class="rbac-small">(<?= htmlspecialchars((string) $p['name'], ENT_QUOTES, 'UTF-8') ?>)</span>
              </label>
            <?php endforeach; ?>

            <div class="rbac-small" data-status></div>
          </form>
        </section>

      <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout/menu_end.php'; ?>
<script>
    (() => {
      const toast = document.getElementById('rbac-toast');
  let toastTimer = null;
    
      function showToast(msg, ok = true, ms = 2500) {
        if (!toast) return;
    clearTimeout(toastTimer);
    
        toast.textContent = msg || '';
    toast.className = 'rbac-toast ' + (ok ? 'ok' : 'err');
  
         to astTimer = setTimeout(() => {
          toast.textContent = '';
          toast.className = 'rbac-toast';
        }, ms);
  }
 
         const timers = new WeakMap();
    fu  nction debounceForm(form, fn, ms = 450) {
        const prev = timers.get(form);
        if (prev) clearTimeout(prev);
        timers.set(form, setTimeout(fn, ms));
  }

       as   ync function safeJson(res) {
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (!ct.includes('application/json')) return null;
        try { return await res.json(); } catch { return null; }
  }
   
       async function postForm(form) {
        const url = form.getAttribute('action') || 'index.php?page=rbac&ajax=1';
    const fd = new FormData(form);

            const statusEl = form.querySelector('[data-status]');
    if (statusEl) statusEl.textContent = 'Saving...';
  
         co nst res = await fetch(url, {
          method: 'POST',
          body: fd,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
    });
  
      const data = await safeJson(res);
   
         if (!res.ok || (data && data.ok === false)) {
          const err = (data && data.err) ? data.err : `Request failed (${res.status})`;
          if (statusEl) statusEl.textContent = 'Error';
          showToast(err, false, 3000);
          return;
    }
  
          if (statusEl) statusEl.textContent = 'Saved';
        showToast((data && data.msg) ? data.msg : 'Saved', true, 2000);
  }
    
  do    cument.addEventListener('change', (e) => {
        const el = e.target;
    if (!(el instanceof HTMLElement)) return;
 
           const form = el.closest('form[data-ajax="1"]');
    if (!form) return;
  
         if  (form.dataset.kind === 'user-role' && el.matches('select[name="role_id"]')) {
          postForm(form).catch(err => showToast(String(err), false, 3000));
          return;
    }

         if    (form.dataset.kind === 'role-perms' && el.matches('input[type="checkbox"][name="perm_ids[]"]')) {
        de  bounceForm(form, () => {
            postForm(form).catch(err => showToast(String(err), false, 3000));
          }, 450);
        }
      });
  })();
</script>
