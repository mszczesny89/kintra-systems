<?php
declare(strict_types=1);

require __DIR__ . '/../layout/menu.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

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
                    INSERT INTO users (username, password_hash, created_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$username, $hash]);

                $userId = (int) $pdo->lastInsertId();

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

            // NIE zmieniaj sesji admina
            $_SESSION['flash_success'] = "Użytkownik '{$username}' utworzony (ID: {$userId}) i dostał rolę 'user'.";

            // redirect na dashboard admina / listę userów
            header('Location: ' . url('dashboard'));
            exit;
        }
    }
}
?>
<div class="contact">
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

        <button type="submit">Create account</button>
    </form>
</div>
<?php require __DIR__ . '/../layout/menu_end.php'; ?>