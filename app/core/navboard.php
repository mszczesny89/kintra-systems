<?php
declare(strict_types=1);

function accessboard(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $appRoot = dirname(__DIR__);
    $file = $appRoot . '/core/accessboard.php';
    $cache = is_file($file) ? require $file : [];
    return is_array($cache) ? $cache : [];
}

function current_route_key(array $routes, string $fallback = 'home'): string
{
    $page = $_GET['page'] ?? $fallback;
    $page = is_string($page) ? $page : $fallback;
    if (!preg_match('/^[a-z0-9_-]{1,64}$/i', $page)) return $fallback;
    return array_key_exists($page, $routes) ? $page : $fallback;
}

/**
 * $nav = 'top' albo 'sidebar'
 */
function nav_items(string $nav, bool $isAuth): array
{
    $routes = accessboard();
    $scope = $isAuth ? 'auth' : 'guest';

    $items = [];
    foreach ($routes as $key => $r) {
        if (!is_array($r)) continue;
        if (($r['scope'] ?? 'guest') !== $scope) continue;

        $menu = $r['menu'] ?? null;
        if (!is_array($menu) || ($menu['nav'] ?? '') !== $nav) continue;

        $perm = $r['perm'] ?? null;
        if ($isAuth && is_string($perm) && $perm !== '' && function_exists('can') && !can($perm)) {
            continue;
        }

        $items[] = [
            'key'   => $key,
            'label' => (string)($menu['label'] ?? $key),
            'route' => $key,
            'order' => (int)($menu['order'] ?? 1000),
        ];
    }

    usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
    return $items;
}
