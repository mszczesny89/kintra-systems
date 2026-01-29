<?php
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$realRoleId = $_SESSION['role_id'] ?? null;
$effRoleId  = $_SESSION['effective_role_id'] ?? null;
$currentRoleId = $effRoleId ?? $realRoleId;
?>
<div class="bg-dark text-white small py-1">
  <div class="container-fluid d-flex align-items-center gap-2">
    <strong class="me-2">DEV</strong>

    <form method="post" class="d-flex align-items-center gap-2 m-0">
      <input type="hidden" name="__dev_role_switch" value="1">

      <span class="opacity-75">Rola:</span>
      <select name="dev_role_id" class="form-select form-select-sm w-auto">
        <option value="">— domyślna (realna) —</option>
        <?php foreach ($roles as $r): ?>
          <option value="<?= (int)$r['id'] ?>" <?= ($currentRoleId === (int)$r['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['name'], ENT_QUOTES) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-sm btn-outline-light" type="submit">Zastosuj</button>
    </form>

    <div class="ms-auto opacity-75">
      real: <?= (int)($realRoleId ?? 0) ?> |
      effective: <?= $effRoleId === null ? '—' : (int)$effRoleId ?>
    </div>
  </div>
</div>