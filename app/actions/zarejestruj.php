<?php
declare(strict_types=1);

/**
 * /php/register.php
 * - GET: formularz
 * - POST: rejestracja + auto-login (opcjonalnie remember_me)
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim((string)($_POST['username'] ?? ''));
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');
    $remember  = isset($_POST['remember_me']);

    // Walidacja username
    if ($username === '') {
        $error = "Username wymagany.";
    } elseif (!preg_match('/^[a-z0-9_]{3,32}$/i', $username)) {
        $error = "Username: 3–32 znaki, litery/cyfry/_";
    } elseif ($password === '' || $password2 === '') {
        $error = "Hasło wymagane.";
    } elseif ($password !== $password2) {
        $error = "Hasła nie są takie same.";
    } elseif (mb_strlen($password) < 8) {
        $error = "Hasło musi mieć min. 8 znaków.";
    } else {
        // Sprawdź czy username zajęty
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "Taki username już istnieje.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->beginTransaction();
            try {
                // Utwórz usera (legacy role zostawiamy na razie, ale źródłem prawdy będzie RBAC)
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password_hash, role, created_at)
                    VALUES (?, ?, 'user', NOW())
                ");
                $stmt->execute([$username, $hash]);

                $userId = (int)$pdo->lastInsertId();

                // Przypisz rolę RBAC: 'user'
                $stmt = $pdo->prepare("
                    INSERT INTO user_roles (user_id, role_id)
                    SELECT :uid, r.id
                    FROM roles r
                    WHERE r.name = 'user'
                    LIMIT 1
                ");
                $stmt->execute([':uid' => $userId]);

                // last_login po rejestracji
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$userId]);

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            // Auto-login po rejestracji
            $_SESSION['user_id']  = $userId;
            $_SESSION['username'] = $username;

            // RBAC: załaduj role/permissiony do sesji
            load_permissions_to_session($pdo, $userId);
            load_roles_to_session($pdo, $userId); // opcjonalnie

            session_regenerate_id(true);

            // Remember me opcjonalnie (zostawiam Twoją logikę)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expDb = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

                $cleanup = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? OR expires_at <= NOW()");
                $cleanup->execute([$userId]);

                $ins = $pdo->prepare("
                    INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at, last_used_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $ins->execute([$userId, $tokenHash, $expDb]);

                cookie_set('remember_me', $token, time() + 60 * 60 * 24 * 30, $https);
            } else {
                cookie_delete('remember_me', $https);
            }

            header('Location: ' . url('dashboard'));
            exit;
        }
    }
}
?>
<h1>Register</h1>

<?php if (!empty($error)): ?>
  <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(url('register'), ENT_QUOTES, 'UTF-8') ?>">
  <label>
    Username
    <input type="text" name="username" required>
  </label>
  <br><br>

  <label>
    Password
    <input type="password" name="password" required>
  </label>
  <br><br>

  <label>
    Repeat password
    <input type="password" name="password2" required>
  </label>
  <br><br>

  <label>
    <input type="checkbox" name="remember_me">
    Remember me (30 dni)
  </label>
  <br><br>

  <button type="submit">Create account</button>
</form>
