<?php 
require $app . '/actions/mail-contact.php';
?>
<section class="contact">
  <h2>Contact</h2>

  <?php if ($success): ?>
    <div class="alert alert-success">
      Message sent. We’ll get back to you shortly.
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars(url('contact'), ENT_QUOTES, 'UTF-8') ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- Honeypot (must stay hidden) -->
    <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
      <label>Company</label>
      <input type="text" name="company" value="">
    </div>

    <div class="field">
      <label for="name">Name</label>
      <input id="name" name="name" type="text" autocomplete="name"
             value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
             maxlength="80" required>
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" autocomplete="email"
             value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
             maxlength="120" required>
    </div>

    <div class="field">
      <label for="message">Message</label>
      <textarea id="message" name="message" rows="6" maxlength="5000" required><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <button class="button button--secondary" type="submit">Send message</button>
  </form>
</section>
