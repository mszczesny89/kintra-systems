<?php require $app . '/actions/zaloguj.php'; ?>
<section class="login">
<h2>Login</h2>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(url('login'), ENT_QUOTES, 'UTF-8') ?>">
    <label>
        Username
        <input type="text" name="username" required>
    </label>

    <label>
        Password
        <input type="password" name="password" required>
    </label>
    <button class="button button--secondary" type="submit">Login</button>

    <label class="remember">
        <input  type="checkbox" name="remember_me">
        Remember me (30 days)
    </label>
</form>
</section>