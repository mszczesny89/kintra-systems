<?php
declare(strict_types=1);

/**
 * app/core/router.php
 * - whitelist tras
 * - RBAC
 * - realpath + allowlist katalogów
 * - redirect tylko do istniejących tras
 */

$appRoot = dirname(__DIR__); // <-- kluczowe: wskazuje na /app, nie /app/core

// ===== Helpers =====
function sys_path(string $appRoot, string $relative): string
{
    $rp = realpath($appRoot . '/' . ltrim($relative, '/'));
    return ($rp !== false && is_file($rp)) ? $rp : ($appRoot . '/' . ltrim($relative, '/'));
}

function safe_route_key(string $key, array $routes, string $fallback = 'home'): string
{
    if (!preg_match('/^[a-z0-9_-]{1,64}$/i', $key)) {
        return $fallback;
    }
    return array_key_exists($key, $routes) ? $key : $fallback;
}

function is_allowed_path(string $candidate, array $allowedRoots): bool
{
    if (!is_file($candidate)) {
        return false;
    }
    foreach ($allowedRoots as $root) {
        if ($root === '')
            continue;
        $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($candidate, $prefix)) {
            return true;
        }
    }
    return false;
}

// ===== Allowlist roots (względem /app) =====
$allowedRoots = [
    realpath($appRoot . '/pages') ?: '',
    realpath($appRoot . '/admin') ?: '',
    realpath($appRoot . '/actions') ?: '',
    realpath($appRoot . '/errors') ?: '',
];

// ===== Routes =====
// route => [relativeFileFromAppRoot, requiredPermission|null, options]
$routes = require sys_path($appRoot, 'core/accessboard.php');
// ===== System pages =====
$system = [
    '404' => 'errors/404.php',
    'not_allowed' => 'errors/not_allowed.php',
];

// ===== Resolve page =====
$page = $_GET['page'] ?? 'home';
$page = is_string($page) ? $page : 'home';
$page = safe_route_key($page, $routes, 'home');

$route = $routes[$page] ?? null;
if (!is_array($route)) {
    return sys_path($appRoot, $system['404']);
}

$relativeFile = $route['file'] ?? '';
$requiredPerm = $route['perm'] ?? null;
$scope = $route['scope'] ?? 'guest';
$opts = $route['opts'] ?? [];
$opts = is_array($opts) ? $opts : [];

if (!is_string($relativeFile) || $relativeFile === '') {
    return sys_path($appRoot, $system['404']);
}

// guest_only
if (!empty($opts['guest_only']) && is_logged()) {
    $to = is_string($opts['redirect'] ?? null) ? $opts['redirect'] : 'dashboard';
    $to = safe_route_key($to, $routes, 'dashboard');
    header('Location: ' . url($to), true, 302);
    exit;
}

// auth_only
if (!empty($opts['auth_only']) && !is_logged()) {
    header('Location: ' . url('login'), true, 302);
    exit;
}

if ($scope === 'auth' && !is_logged()) {
    header('Location: ' . url('login'), true, 302);
    exit;
}

if ($scope === 'guest' && is_logged()) {
    // opcjonalnie: zalogowany niech nie ogląda login itp.
    // tylko jeśli chcesz globalnie; albo zostaw guest_only per-route w opts
}


// resolve file
$candidate = realpath($appRoot . '/' . ltrim($relativeFile, '/'));
if ($candidate === false || !is_allowed_path($candidate, $allowedRoots)) {
    return sys_path($appRoot, $system['404']);
}

// ACL
if ($requiredPerm !== null) {
    if (!is_logged()) {
        header('Location: ' . url('login'), true, 302);
        exit;
    }
    if (!is_string($requiredPerm) || $requiredPerm === '' || !can($requiredPerm)) {
        return sys_path($appRoot, $system['not_allowed']);
    }
}

return $candidate;
