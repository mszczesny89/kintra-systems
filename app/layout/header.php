<!-- KINTRA-SYSTEMS -->
<!doctype html>
<html>

<head>
  <?php require dirname(__DIR__) . '/layout/meta.php'; ?>
</head>
<?php
$page = $_GET['page'] ?? 'home';
$page = preg_replace('/[^a-z0-9\-]/', '', $page);
$pageClass = 'page-' . $page;

$isAuth = !empty($_SESSION['user_id']); // dostosuj do swojego klucza
?>

<body class="<?= htmlspecialchars($pageClass, ENT_QUOTES, 'UTF-8') ?>">

  <?php if ($isAuth): ?>
    <header class="container header header--in">
      <div class="header__brand">
        <img src="img/kintra-logofullwhite.svg" alt="KINTRA SYSTEMS" class="img_logo img_logo--in" />
      </div>
      <div class="header__nav">
        <?php require __DIR__ . '/nav.php'; ?>
      </div>
    </header>
  <?php else: ?>
    <header class="container header header--guest">
      <div class="header__brand">
        <img src="img/kintra-logofullwhite.svg" alt="KINTRA SYSTEMS" class="img_logo" />
      </div>
      <div class="header__nav">
        <?php require __DIR__ . '/nav.php'; ?>
      </div>
    </header>
  <?php endif; ?>


  <!-- HEADER END -->