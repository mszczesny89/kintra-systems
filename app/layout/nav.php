<?php

$isLogged = is_logged();

// aktywna strona (podświetlenie)
$current = $_GET['page'] ?? 'home';
if (!is_string($current) || !preg_match('/^[a-z0-9_-]{1,64}$/i', $current)) {
    $current = 'home';
}

/**
 * Sloty: guest vs auth (Home->Dashboard itd.)
 */
$slots = [
    [
        'guest' => ['home', 'Home'],
        'auth'  => ['dashboard', 'Dashboard', 'dashboard.view'],
    ],
    [
        'guest' => ['about', 'About'],
        'auth'  => ['profile', 'Profile', 'profile.view'],
    ],
    [
        'guest' => ['contact', 'Contact'],
        'auth'  => ['settings', 'Settings', 'settings.view'],
    ],
    [
        'guest' => ['login', 'Login'],
        'auth'  => ['logout', 'Logout'], // specjalny case w renderze (POST)
    ],
];

/**
 * Admin: osobna lista pod spodem
 */
$navAdmin = [
    ['admin',       'Admin Panel',  'admin_panel.access'],
    ['users',       'Users',        'users.manage'],
    ['rbac',        'RBAC',         'rbac.manage'],
    ['register',    'Register',     'user.register'],
];

$primaryItems = [];
foreach ($slots as $slot) {
    $row = $isLogged ? $slot['auth'] : $slot['guest'];
    [$page, $label, $perm] = array_pad($row, 3, null);

    if ($isLogged && $perm !== null) {
        if (!is_string($perm) || $perm === '' || !can($perm)) {
            continue;
        }
    }

    $primaryItems[] = [$page, $label];
}

$adminItems = [];
if ($isLogged) {
    foreach ($navAdmin as $row) {
        [$page, $label, $perm] = array_pad($row, 3, null);

        if ($perm !== null) {
            if (!is_string($perm) || $perm === '' || !can($perm)) {
                continue;
            }
        }

        $adminItems[] = [$page, $label];
    }
}
?>

<nav class="site-nav" aria-label="Primary navigation">
  <div>

    <!-- PRIMARY -->
    <ul class="nav-list nav-list--primary">
      <?php foreach ($primaryItems as [$page, $label]): ?>
        <?php
          $isActive = ($page === $current) ? ' is-active' : '';
          $isLogout = ($isLogged && $page === 'logout');
        ?>
        <li class="nav-item<?= $isActive ?><?= $isLogout ? ' nav-item-right' : '' ?>">
          <?php if ($isLogout): ?>
            <form method="post" action="<?= htmlspecialchars(url('logout'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
              <button type="submit" class="nav-link nav-button">Logout</button>
            </form>
          <?php else: ?>
            <a class="nav-link" href="<?= htmlspecialchars(url($page), ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- ADMIN (only if has items) -->
    <?php if (!empty($adminItems)): ?>
      <div class="nav-admin" aria-label="Administration">
        <div class="nav-admin__label"></div>
        <ul class="nav-list nav-list--admin">
          <?php foreach ($adminItems as [$page, $label]): ?>
            <?php $isActive = ($page === $current) ? ' is-active' : ''; ?>
            <li class="nav-item<?= $isActive ?>">
              <a class="nav-link" href="<?= htmlspecialchars(url($page), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

  </div>
</nav>
