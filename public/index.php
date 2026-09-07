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

// 2. Authoritative RDS Persistent Installation Check
require_once $projectRoot . '/bootstrap/installation_check.php';
$installCheck = checkApplicationInstallationStatus($projectRoot);

if ($installCheck['status'] === 'partially_installed') {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    $found = htmlspecialchars(implode(', ', $installCheck['found'] ?? []));
    $req = htmlspecialchars(implode(', ', $installCheck['required'] ?? []));
    echo "<!DOCTYPE html><html><head><title>Partial Schema Detected</title></head><body style=\"font-family:sans-serif;background:#090d16;color:#f8fafc;padding:3rem;text-align:center;\"><div style=\"max-width:600px;margin:0 auto;background:#111827;padding:2rem;border-radius:12px;border:1px solid #ef4444;\"><h2 style=\"color:#ef4444;\">Database Partially Initialized</h2><p style=\"color:#94a3b8;\">Found tables: {$found}<br>Required: {$req}</p><p style=\"color:#cbd5e1;\">To protect production data, auto-installation and table dropping are strictly halted. Please review migrations manually.</p></div></body></html>";
    exit;
}

if ($installCheck['status'] === 'uninstalled') {
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

if ($installCheck['status'] === 'db_error') {
    // If DB is unreachable and in production, do NOT redirect to installer (prevents exposing installer on DB hiccups)
    $envMode = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
    if ($envMode === 'production') {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><title>Database Unavailable</title></head><body style=\"font-family:sans-serif;background:#090d16;color:#f8fafc;padding:3rem;text-align:center;\"><div style=\"max-width:500px;margin:0 auto;background:#111827;padding:2rem;border-radius:12px;\"><h2 style=\"color:#f59e0b;\">Service Temporarily Unavailable</h2><p style=\"color:#94a3b8;\">Connecting to the database timed out or failed. Please refresh in a moment.</p></div></body></html>";
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
