<?php
/**
 * Root Index Forwarder & Installation Router
 * 
 * Automatically redirects to /install.php when the script is first uploaded to a hosting,
 * and delegates all traffic to public/index.php once installed.
 */

$projectRoot = __DIR__;

// 1. Comprehensive check if application is installed
$isInstalled = false;
$lockFiles = [
    $projectRoot . '/storage/installed.lock',
    $projectRoot . '/.installed',
    $projectRoot . '/installed.lock',
    $projectRoot . '/public/installed.lock',
];

foreach ($lockFiles as $f) {
    if (file_exists($f)) {
        $isInstalled = true;
        break;
    }
}

// 2. Check .env configuration for APP_INSTALLED flag
$envPath = $projectRoot . '/.env';
if (!$isInstalled && file_exists($envPath)) {
    $envContent = @file_get_contents($envPath) ?: '';
    if (preg_match('/^APP_INSTALLED=(true|1)/m', $envContent)) {
        $isInstalled = true;
    }
}

// 3. If not installed, redirect directly to install.php
if (!$isInstalled) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

    if (!str_contains($path, 'install.php')) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $installUrl = ($scriptDir === '' || $scriptDir === '.' || $scriptDir === '/') ? '/install.php' : $scriptDir . '/install.php';

        header('Location: ' . $installUrl, true, 302);
        exit;
    }

    if (file_exists(__DIR__ . '/public/install.php')) {
        require __DIR__ . '/public/install.php';
        exit;
    }
}

// 4. If installed, hand over to public/index.php
require_once __DIR__ . '/public/index.php';
