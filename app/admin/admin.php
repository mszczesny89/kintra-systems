<div class="admin-strona">
  <div class="admin-menu">
    <h2>This Is Not a Web Studio.</h2>
      and often not the most critical one.
  </div>  
  <div class="admin-tresc">
    <h2>This Is Not a Web Studio.</h2>
    <p>
      <?php
        function getCounter(PDO $pdo, string $key): int {
          $stmt = $pdo->prepare("SELECT v FROM counters WHERE k = :k LIMIT 1");
          $stmt->execute([':k' => $key]);
          return (int)($stmt->fetchColumn() ?: 0);
        }

        $visits = getCounter($pdo, 'visits_total');
        $msgs   = getCounter($pdo, 'messages_sent_total');


        if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
          echo "<div class='admin-stats'>
          <div>Visits total: {$visits}</div>
          <div>Messages sent: {$msgs}</div>
        </div>";
        }
      ?>
    </p>
  </div>
</div>