<?php
declare(strict_types=1);
?>
<!-- FOOTER START -->
<footer>
  <div class="container">
    <small>
      © <?= date('Y') ?> Kintra Systems Ltd.
      <?php if (isset($_SESSION['user_id'])): ?>
        <span style="margin-left:0.5rem;">
          Logged as <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>
    </small>
  </div>
</footer>
<!-- FOOTER END -->

</body>
</html>
