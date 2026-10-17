<?php
/**
 * PHP Built-in Dev Server Router
 * Kaldis Coffee PLC
 * Usage: php -S 127.0.0.1:8000 -t . router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Strip /Planner prefix if present so both URLs work seamlessly
$cleanUri = $uri;
if (str_starts_with($cleanUri, '/Planner/')) {
    $cleanUri = substr($cleanUri, 8); // remove '/Planner'
} elseif ($cleanUri === '/Planner') {
    $cleanUri = '/';
}

// Serve static or php files directly if they exist
if ($cleanUri !== '' && $cleanUri !== '/' && file_exists(__DIR__ . $cleanUri) && !is_dir(__DIR__ . $cleanUri)) {
    if (str_ends_with($cleanUri, '.php')) {
        $_SERVER['SCRIPT_NAME'] = $cleanUri;
        require_once __DIR__ . $cleanUri;
        exit;
    }
    return false;
}

// Check for extensionless .php file (e.g. /overview -> /overview.php, /admin/dashboard -> /admin/dashboard.php)
if ($cleanUri !== '' && $cleanUri !== '/' && file_exists(__DIR__ . $cleanUri . '.php')) {
    $scriptName = $cleanUri . '.php';
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    require_once __DIR__ . $scriptName;
    exit;
}

// Check for directory index.php (e.g. /admin or /admin/ -> /admin/index.php)
$dirIndex = rtrim($cleanUri, '/') . '/index.php';
if ($cleanUri !== '' && $cleanUri !== '/' && file_exists(__DIR__ . $dirIndex)) {
    $_SERVER['SCRIPT_NAME'] = $dirIndex;
    require_once __DIR__ . $dirIndex;
    exit;
}

// Default to root index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/index.php';
