<?php
declare(strict_types=1);

/**
 * accessboard.php
 * key => [
 *   file   => relative file from /app
 *   scope  => guest|auth
 *   perm   => string|null
 *   menu   => null|[ 'nav' => 'top'|'sidebar', 'label' => string, 'order' => int ]
 *   opts   => [ 'auth_only' => bool, 'guest_only' => bool, 'redirect' => string ]
 * ]
 */

return [
    // ===== PUBLIC (top nav) =====
    'home' => [
        'file'  => 'pages/home.php',
        'scope' => 'guest',
        'perm'  => null,
        'menu'  => ['nav' => 'top', 'label' => 'Home', 'order' => 10],
        'opts'  => [],
    ],
    'about' => [
        'file'  => 'pages/about.php',
        'scope' => 'guest',
        'perm'  => null,
        'menu'  => ['nav' => 'top', 'label' => 'About', 'order' => 20],
        'opts'  => [],
    ],
    'contact' => [
        'file'  => 'pages/contact.php',
        'scope' => 'guest',
        'perm'  => null,
        'menu'  => ['nav' => 'top', 'label' => 'Contact', 'order' => 30],
        'opts'  => [],
    ],
    'login' => [
        'file'  => 'pages/login.php',
        'scope' => 'guest',
        'perm'  => null,
        'menu'  => ['nav' => 'top', 'label' => 'Login', 'order' => 40],
        'opts'  => ['guest_only' => true, 'redirect' => 'dashboard'],
    ],

    // ===== AUTH USER (sidebar) =====
    'dashboard' => [
        'file'  => 'pages/dashboard.php',
        'scope' => 'auth',
        'perm'  => 'dashboard.view',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Dashboard', 'order' => 10],
        'opts'  => [],
    ],
    'plan' => [
        'file'  => 'pages/plan.php',
        'scope' => 'auth',
        'perm'  => 'plan.view',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Plan', 'order' => 20],
        'opts'  => [],
    ],
    'progress' => [
        'file'  => 'pages/progress.php',
        'scope' => 'auth',
        'perm'  => 'progress.view',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Progress', 'order' => 30],
        'opts'  => [],
    ],
    'invoices' => [
        'file'  => 'pages/invoices.php',
        'scope' => 'auth',
        'perm'  => 'invoices.view',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Invoices', 'order' => 40],
        'opts'  => [],
    ],
    'testers' => [
        'file'  => 'pages/testers.php',
        'scope' => 'auth',
        'perm'  => 'testers.view',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Testers', 'order' => 50],
        'opts'  => [],
    ],

    // ===== AUTH ACTIONS =====
    'logout' => [
        'file'  => 'actions/logout.php',
        'scope' => 'auth',
        'perm'  => null,
        'menu'  => null, // renderujesz ręcznie jako POST button
        'opts'  => ['auth_only' => true],
    ],

    // ===== ADMIN (sidebar albo osobna sekcja) =====
    'admin' => [
        'file'  => 'admin/admin.php',
        'scope' => 'auth',
        'perm'  => 'admin_panel.access',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Admin Panel', 'order' => 900],
        'opts'  => [],
    ],
    'users' => [
        'file'  => 'admin/users.php',
        'scope' => 'auth',
        'perm'  => 'users.manage',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Users', 'order' => 910],
        'opts'  => [],
    ],
    'rbac' => [
        'file'  => 'admin/rbac.php',
        'scope' => 'auth',
        'perm'  => 'rbac.manage',
        'menu'  => ['nav' => 'sidebar', 'label' => 'RBAC', 'order' => 920],
        'opts'  => [],
    ],
    'register' => [
        'file'  => 'admin/register.php',
        'scope' => 'auth',
        'perm'  => 'user.register',
        'menu'  => ['nav' => 'sidebar', 'label' => 'Register', 'order' => 930],
        'opts'  => [],
    ],
];
