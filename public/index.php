<?php
declare(strict_types=1);

$app = dirname(__DIR__) . '/app';

require $app . '/bootstrap/bootstrap.php';

function incCounter(PDO $pdo, string $key, int $delta = 1): void {
  $stmt = $pdo->prepare("
    INSERT INTO counters (k, v) VALUES (:k, :d)
    ON DUPLICATE KEY UPDATE v = v + VALUES(v)
  ");
  $stmt->execute([':k' => $key, ':d' => $delta]);
}

function incDaily(PDO $pdo, string $col, int $delta = 1): void {
  // $col: 'visits' albo 'messages_sent'
  if (!in_array($col, ['visits', 'messages_sent'], true)) return;

  $stmt = $pdo->prepare("
    INSERT INTO stats_daily (day, $col) VALUES (CURDATE(), :d)
    ON DUPLICATE KEY UPDATE $col = $col + VALUES($col)
  ");
  $stmt->execute([':d' => $delta]);
}

// --- Visits: 1x na sesję dziennie
$today = date('Y-m-d');
if (empty($_SESSION['counted_visit_day']) || $_SESSION['counted_visit_day'] !== $today) {
  $_SESSION['counted_visit_day'] = $today;

  incCounter($pdo, 'visits_total', 1);
  incDaily($pdo, 'visits', 1); // opcjonalnie
}


// Basic no-cache for authenticated app pages (optional but recommended)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Jeśli user wchodzi na root / bez page i jest zalogowany -> dashboard
$page = $_GET['page'] ?? null;
if ($page === null && is_logged()) {
    header('Location: ' . url('dashboard'), true, 302);
    exit;
}
$main = require $app . '/core/router.php';

if (!is_string($main) || !is_file($main)) {
    http_response_code(500);
    exit('Routing error');
}

require $app . '/layout/header.php';
?>
<main class="container">
  <?php require $main; ?>
</main>
<?php require $app . '/layout/footer.php'; ?>
<script src="/js/app.js?v=1" defer></script>


