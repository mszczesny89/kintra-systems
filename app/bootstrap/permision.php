<?php

if (is_logged()) {
    if (empty($_SESSION['roles']) || !is_array($_SESSION['roles'])) {
        load_roles_to_session($pdo, (int)$_SESSION['user_id']);
    }
    if (empty($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        load_permissions_to_session($pdo, (int)$_SESSION['user_id']);
    }
}
?>