<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/navboard.php';

$isAuth = is_logged();
$routes = accessboard();

$current = current_route_key($routes, 'dashboard');
$pageTitle = $pageTitle ?? ($routes[$current]['menu']['label'] ?? ucfirst($current));

$items = nav_items('sidebar', $isAuth);
?>

<div class="lewy-bar" data-shell="dashboard">
  <button class="menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="sidebar">
    <span></span><span></span><span></span>
  </button>

  <div class="mobile-header">
    <span class="page-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <nav id="sidebar" class="sidebar" aria-label="Sidebar navigation">
    <ul>
      <?php foreach ($items as $it): ?>
        <?php $isActive = ($current === $it['key']); ?>
        <li>
          <a class="sidebar__link<?= $isActive ? ' is-active' : '' ?>"
             href="<?= htmlspecialchars(url($it['route']), ENT_QUOTES, 'UTF-8') ?>"
             <?= $isActive ? 'aria-current="page"' : '' ?>>
            <?= htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <main class="content" id="main">
