<?php
/**
 * /php/login.php
 * - GET: pokazuje formularz
 * - POST: loguje użytkownika
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']);

    if ($username === '' || $password === '') {
        $error = "Brak loginu lub hasła.";
    } else {
        // Pobierz usera
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash
            FROM users
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $error = "Zły login lub hasło.";
        } else {
            $userId = (int) $user['id'];

            // Zaloguj
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = (string) $user['username'];

            // RBAC
            load_permissions_to_session($pdo, $userId);
            load_roles_to_session($pdo, $userId); // opcjonalnie

            // zabezpieczenie przeciw session fixation
            session_regenerate_id(true);

            // last_login tylko na logowaniu
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);

            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);
                $expDb = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

                $cleanup = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? OR expires_at <= NOW()");
                $cleanup->execute([$userId]);

                $ins = $pdo->prepare("
                    INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at, last_used_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $ins->execute([$userId, $hash, $expDb]);

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