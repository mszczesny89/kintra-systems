<?php
declare(strict_types=1);

$isLogged = is_logged();

// aktywna strona (podświetlenie)
$current = $_GET['page'] ?? 'home';
if (!is_string($current) || !preg_match('/^[a-z0-9_-]{1,64}$/i', $current)) {
    $current = 'home';
}
?>

<nav class="site-nav" aria-label="Primary navigation">
  <div>

    <ul class="nav-list nav-list--primary">

      <?php if (!$isLogged): ?>

        <?php
          $items = [
            ['home', 'Home'],
            ['about', 'About'],
            ['contact', 'Contact'],
            ['login', 'Login'],
          ];
        ?>

        <?php foreach ($items as [$page, $label]): ?>
          <?php $isActive = ($page === $current) ? ' is-active' : ''; ?>
          <li class="nav-item<?= $isActive ?>">
            <a class="nav-link" href="<?= htmlspecialchars(url($page), ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
          </li>
        <?php endforeach; ?>

      <?php else: ?>

        <li class="nav-item nav-item-right">
          <form method="post" action="<?= htmlspecialchars(url('logout'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
            <button type="submit" class="nav-link nav-button">
              Logout
            </button>
          </form>
        </li>

      <?php endif; ?>

    </ul>

  </div>
</nav>
