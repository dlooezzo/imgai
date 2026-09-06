<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Locate Laravel project root directory intelligently across all shared hosting topologies
$projectRoot = null;
$candidateRoots = [
    __DIR__ . '/..',
    __DIR__ . '/../imgai',
    __DIR__,
];

foreach ($candidateRoots as $candidate) {
    if (file_exists($candidate . '/bootstrap/app.php') && file_exists($candidate . '/artisan')) {
        $projectRoot = realpath($candidate) ?: $candidate;
        break;
    }
}

if (!$projectRoot) {
    // Search sibling folders under parent directory (cPanel split-folder setups)
    $parentDir = dirname(__DIR__);
    $siblings = @glob($parentDir . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($siblings as $sibling) {
        if (file_exists($sibling . '/bootstrap/app.php') && file_exists($sibling . '/artisan')) {
            $projectRoot = realpath($sibling) ?: $sibling;
            break;
        }
    }
}

if (!$projectRoot) {
    $projectRoot = dirname(__DIR__);
}

// 2. Comprehensive check if application is installed
$isInstalled = false;
$lockFiles = [
    $projectRoot . '/storage/installed.lock',
    $projectRoot . '/.installed',
    $projectRoot . '/installed.lock',
    __DIR__ . '/installed.lock',
    __DIR__ . '/storage/installed.lock',
    __DIR__ . '/../storage/installed.lock',
    __DIR__ . '/../.installed',
    __DIR__ . '/../installed.lock',
];

foreach ($lockFiles as $f) {
    if (file_exists($f)) {
        $isInstalled = true;
        break;
    }
}

// 3. Check .env configuration for APP_INSTALLED flag
$envPath = $projectRoot . '/.env';
if (!$isInstalled && file_exists($envPath)) {
    $envContent = @file_get_contents($envPath) ?: '';
    if (preg_match('/^APP_INSTALLED=(true|1)/m', $envContent)) {
        $isInstalled = true;
    }
}

// 4. Automatic redirect to installer if not installed
if (!$isInstalled) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

    // Prevent infinite redirect loops if already accessing installer
    if (!str_contains($path, 'install.php')) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $installUrl = ($scriptDir === '' || $scriptDir === '.' || $scriptDir === '/') ? '/install.php' : $scriptDir . '/install.php';

        header('Location: ' . $installUrl, true, 302);
        exit;
    }

    if (file_exists(__DIR__ . '/install.php')) {
        require __DIR__ . '/install.php';
        exit;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $projectRoot . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $projectRoot . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $projectRoot . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
