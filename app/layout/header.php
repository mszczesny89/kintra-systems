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
?>
<body class="<?= htmlspecialchars($pageClass, ENT_QUOTES, 'UTF-8') ?>">

<!-- HEADER START -->
<header class="container header">
  <img src="img/kintra-logofull.svg" alt="KINTRA SYSTEMS" class="img_logo"/>
</header>

<?php require __DIR__ . '/nav.php'; ?>
<!-- HEADER END -->
