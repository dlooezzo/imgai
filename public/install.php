<?php
/**
 * IMGAI - Shared Hosting Auto-Installer & Setup System
 * 
 * Optimized for FreeHosting.com, cPanel, and Shared Hosting Environments.
 * Auto-detects domain, configures MySQL (default/recommended), runs migrations,
 * links public storage, preserves Cloudflare R2 & API configs, and locks securely.
 */

// Define installation lock file path with intelligent multi-folder detection
$rootDir = null;
$candidateRoots = [
    __DIR__ . '/..',
    __DIR__ . '/../imgai',
    __DIR__,
];

foreach ($candidateRoots as $candidate) {
    if (file_exists($candidate . '/bootstrap/app.php') && file_exists($candidate . '/artisan')) {
        $rootDir = realpath($candidate) ?: $candidate;
        break;
    }
}

if (!$rootDir) {
    $parentDir = dirname(__DIR__);
    $siblings = @glob($parentDir . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($siblings as $sibling) {
        if (file_exists($sibling . '/bootstrap/app.php') && file_exists($sibling . '/artisan')) {
            $rootDir = realpath($sibling) ?: $sibling;
            break;
        }
    }
}

if (!$rootDir) {
    $rootDir = dirname(__DIR__);
}

if (!defined('INSTALLER_ROOT_DIR')) define('INSTALLER_ROOT_DIR', $rootDir);
if (!defined('INSTALLER_LOCK')) define('INSTALLER_LOCK', INSTALLER_ROOT_DIR . '/storage/installed.lock');
if (!defined('INSTALLER_FALLBACK_LOCK')) define('INSTALLER_FALLBACK_LOCK', __DIR__ . '/installed.lock');
if (!defined('INSTALLER_ROOT_LOCK')) define('INSTALLER_ROOT_LOCK', INSTALLER_ROOT_DIR . '/.installed');

// Load InstallerDatabaseService if present
if (file_exists(INSTALLER_ROOT_DIR . '/app/Services/InstallerDatabaseService.php')) {
    require_once INSTALLER_ROOT_DIR . '/app/Services/InstallerDatabaseService.php';
}

// 1. SECURITY LOCK: Prevent re-running if installed
$isAlreadyInstalled = false;
$allKnownLocks = [
    INSTALLER_LOCK,
    INSTALLER_FALLBACK_LOCK,
    INSTALLER_ROOT_LOCK,
    INSTALLER_ROOT_DIR . '/installed.lock',
    __DIR__ . '/installed.lock',
    __DIR__ . '/storage/installed.lock',
    __DIR__ . '/../storage/installed.lock',
    __DIR__ . '/../.installed',
    __DIR__ . '/../imgai/storage/installed.lock',
    __DIR__ . '/../imgai/.installed',
];

foreach ($allKnownLocks as $lk) {
    if (file_exists($lk)) {
        $isAlreadyInstalled = true;
        break;
    }
}

$envCheckPath = INSTALLER_ROOT_DIR . '/.env';
if (!$isAlreadyInstalled && file_exists($envCheckPath)) {
    $envCheckContent = @file_get_contents($envCheckPath) ?: '';
    if (preg_match('/^APP_INSTALLED=(true|1)/m', $envCheckContent)) {
        $isAlreadyInstalled = true;
    }
}

// Block any POST re-installation attempt once system is installed
if ($isAlreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>النظام مثبت ومؤمّن</title><style>body{background:#090d16;color:#f8fafc;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head><body><div style="text-align:center;padding:2.5rem;background:#111827;border-radius:16px;border:1px solid rgba(255,255,255,0.1);max-width:520px;"><h2 style="color:#ef4444;margin-bottom:12px;">🔒 نظام التثبيت مؤمّن ومغلق</h2><p style="color:#94a3b8;line-height:1.6;">تم تثبيت وتأمين المشروع مسبقاً لمنع إعادة التشغيل من الإنترنت. لإعادة التهيئة، يجب إزالة ملف storage/installed.lock من الخادم يدوياً.</p><br><a href="/" style="display:inline-block;background:#3b82f6;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">الذهاب للرئيسية 🚀</a></div></body></html>';
    exit;
}


// 2. DOMAIN & PROTOCOL DETECTION (Clean & Canonical)
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
    ($_SERVER['SERVER_PORT'] ?? 80) == 443
);
$scheme = $isHttps ? 'https' : 'http';

// Read host header safely and sanitize strictly against injection
$rawHost = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$httpHostClean = preg_replace('/[^a-zA-Z0-9.:-]/', '', $rawHost);
// Remove default port numbers if explicitly present
$httpHostClean = preg_replace('/:(80|443)$/', '', $httpHostClean);

// Calculate subfolder path if script is located in a subfolder
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$parentPath = str_replace('\\', '/', dirname($scriptName));
$scriptDir = trim($parentPath, '/.');
$baseSubPath = ($scriptDir === '' || $scriptDir === '.' || $scriptDir === 'public') ? '' : '/' . $scriptDir;

$detectedAppUrl = rtrim($scheme . '://' . $httpHostClean . $baseSubPath, '/\\');

// .env file paths
$envFile = INSTALLER_ROOT_DIR . '/.env';
$envExample = INSTALLER_ROOT_DIR . '/.env.example';

// Ensure .env exists
if (!file_exists($envFile) && file_exists($envExample)) {
    @copy($envExample, $envFile);
}

// Read current .env
$envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

if (!function_exists('getEnvVal')) {
    function getEnvVal($content, $key, $default = '') {
        if (preg_match("/^{$key}=(.*)$/m", $content, $matches)) {
            return trim(trim($matches[1]), '"\'');
        }
        return $default;
    }
}

$currentDbHost = getEnvVal($envContent, 'DB_HOST', '127.0.0.1');
$currentDbPort = getEnvVal($envContent, 'DB_PORT', '3306');
$currentDbName = getEnvVal($envContent, 'DB_DATABASE', '');
$currentDbUser = getEnvVal($envContent, 'DB_USERNAME', '');
$currentDbPass = getEnvVal($envContent, 'DB_PASSWORD', '');
$currentAppKey = getEnvVal($envContent, 'APP_KEY', '');
$currentDbDriver = getEnvVal($envContent, 'DB_CONNECTION', 'mysql');

// Resolve current vs detected URL:
// If existing APP_URL in .env belongs to a different domain (e.g. project moved), automatically switch default to detected domain!
$envAppUrl = getEnvVal($envContent, 'APP_URL', '');
$envHost = parse_url($envAppUrl, PHP_URL_HOST);
$detectedHost = parse_url($detectedAppUrl, PHP_URL_HOST);

if (empty($envAppUrl) || in_array($envAppUrl, ['http://localhost', 'https://localhost']) || ($envHost && $detectedHost && $envHost !== $detectedHost)) {
    $currentAppUrl = $detectedAppUrl;
} else {
    $currentAppUrl = $envAppUrl;
}

// Available PHP PDO drivers
$hasMysqlPdo = extension_loaded('pdo_mysql');
$hasPgsqlPdo = extension_loaded('pdo_pgsql');
$hasSqlitePdo = extension_loaded('pdo_sqlite');

$error = null;
$success = null;
$logs = [];

// 3. HANDLE INSTALLATION POST REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    
    // Safely retrieve and validate $appUrl
    $rawAppUrl = trim($_POST['app_url'] ?? '');
    if (!empty($rawAppUrl)) {
        // Ensure protocol prefix exists, using detected scheme (http or https)
        if (!preg_match('~^(?:f|ht)tps?://~i', $rawAppUrl)) {
            $rawAppUrl = $scheme . '://' . ltrim($rawAppUrl, '/');
        }
        $appUrl = rtrim($rawAppUrl, '/');
    } else {
        $appUrl = rtrim($detectedAppUrl, '/');
    }

    $dbDriver = strtolower(trim($_POST['db_driver'] ?? 'mysql'));
    $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort = (int)($_POST['db_port'] ?? ($dbDriver === 'pgsql' ? 5432 : 3306));
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $runMigrations = isset($_POST['run_migrations']);
    $createStorageLink = isset($_POST['storage_link']);

    // A. Validate Driver & PHP Extension availability
    if ($dbDriver === 'mysql' && !$hasMysqlPdo) {
        $error = "امتداد PHP PDO MySQL (pdo_mysql) غير مفعّل على الخادم. يُرجى تفعيله من لوحة التحكم.";
    } elseif ($dbDriver === 'pgsql' && !$hasPgsqlPdo) {
        $error = "قاعدة بيانات PostgreSQL غير مدعومة على هذه الاستضافة لأن امتداد PHP PDO PostgreSQL (pdo_pgsql) غير متوفر. يُرجى اختيار MySQL (الموصى به للاستضافة المشتركة).";
    } elseif ($dbDriver === 'sqlite' && !$hasSqlitePdo) {
        $error = "امتداد PHP PDO SQLite (pdo_sqlite) غير متوفر على الخادم.";
    } elseif ($dbDriver !== 'sqlite' && (empty($dbName) || empty($dbUser))) {
        $error = "يرجى كتابة اسم قاعدة البيانات واسم المستخدم بشكل صحيح.";
    }

    // B. Test Database Connection
    if (!$error) {
        if ($dbDriver === 'sqlite') {
            $sqlitePath = INSTALLER_ROOT_DIR . '/database/database.sqlite';
            if (!file_exists($sqlitePath)) {
                @touch($sqlitePath);
            }
            if (!is_writable($sqlitePath) && !is_writable(dirname($sqlitePath))) {
                $error = "ملف قاعدة بيانات SQLite أو مجلد database غير قابل للكتابة. يرجى تعديل الصلاحيات إلى 775.";
            } else {
                $logs[] = "✅ استخدام قاعدة بيانات SQLite المحلية بنجاح.";
            }
        } elseif ($dbDriver === 'pgsql') {
            try {
                $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName};sslmode=require";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 6
                ]);
                $logs[] = "✅ الاتصال بقاعدة بيانات PostgreSQL تم بنجاح.";
            } catch (Exception $e) {
                $error = "فشل الاتصال بقاعدة بيانات PostgreSQL: " . $e->getMessage();
            }
        } else {
            // MySQL (Default)
            try {
                $provision = \App\Services\InstallerDatabaseService::provisionMysql(
                    $dbHost,
                    $dbPort,
                    $dbUser,
                    $dbPass,
                    $dbName
                );
                if (!empty($provision['logs'])) {
                    foreach ($provision['logs'] as $logLine) {
                        $logs[] = $logLine;
                    }
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }

    // C. Execute Setup, Environment Update, and Migrations
    if (!$error) {
        try {
            // Generate APP_KEY if missing
            if (empty($currentAppKey) || $currentAppKey === 'base64:') {
                $currentAppKey = 'base64:' . base64_encode(random_bytes(32));
                $logs[] = "🔑 تم توليد مفتاح التطبيق APP_KEY بنجاح.";
            }

            // Update .env while strictly preserving existing keys (R2, Supabase, API keys, etc.)
            $envUpdates = [
                'APP_NAME' => 'IMGAI',
                'APP_ENV' => 'production',
                'APP_KEY' => $currentAppKey,
                'APP_DEBUG' => 'false',
                'APP_URL' => $appUrl,
                'APP_INSTALLED' => 'true',
                'DB_CONNECTION' => $dbDriver,
                'SESSION_DRIVER' => 'file',
                'QUEUE_CONNECTION' => 'sync',
                'CACHE_STORE' => 'file',
                'FILESYSTEM_DISK' => 'public',
            ];

            if ($dbDriver !== 'sqlite') {
                $envUpdates['DB_HOST'] = $dbHost;
                $envUpdates['DB_PORT'] = (string)$dbPort;
                $envUpdates['DB_DATABASE'] = $dbName;
                $envUpdates['DB_USERNAME'] = $dbUser;
                $envUpdates['DB_PASSWORD'] = $dbPass;
            }

            foreach ($envUpdates as $key => $val) {
                $escapedVal = class_exists(\App\Services\InstallerDatabaseService::class)
                    ? \App\Services\InstallerDatabaseService::formatEnvValue((string)$val)
                    : ((strpos($val, ' ') !== false || strpos($val, '#') !== false) ? '"' . addcslashes($val, "\\\"") . '"' : $val);
                if (preg_match("/^{$key}=.*/m", $envContent)) {
                    $envContent = preg_replace_callback("/^{$key}=.*/m", function() use ($key, $escapedVal) {
                        return "{$key}={$escapedVal}";
                    }, $envContent);
                } else {
                    $envContent .= "\n{$key}={$escapedVal}";
                }
            }

            file_put_contents($envFile, $envContent);
            $logs[] = "📝 تم تحديث ملف الإعدادات .env وربطه بالدومين: " . htmlspecialchars($appUrl);

            // Bootstrap Laravel Application for Artisan
            require_once INSTALLER_ROOT_DIR . '/vendor/autoload.php';
            $app = require_once INSTALLER_ROOT_DIR . '/bootstrap/app.php';
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            // After setting up MySQL connection: purge mysql, then update config() before migrations
            if ($dbDriver === 'mysql') {
                \Illuminate\Support\Facades\DB::purge('mysql');
                config([
                    'database.default' => 'mysql',
                    'database.connections.mysql.host' => $dbHost,
                    'database.connections.mysql.port' => (string)$dbPort,
                    'database.connections.mysql.database' => $dbName,
                    'database.connections.mysql.username' => $dbUser,
                    'database.connections.mysql.password' => $dbPass,
                ]);
            }

            // Run Migrations (ONLY migrate --force, NO fresh or drops)
            if ($runMigrations) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $migrateOutput = \Illuminate\Support\Facades\Artisan::output();
                $logs[] = "🚀 تنفيذ جداول قاعدة البيانات (Migrations):\n" . trim($migrateOutput);
            }

            // Safe Database Domain Synchronization (Domain-Agnostic Migration)
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                    // Record canonical site_url in database settings
                    \App\Models\SiteSetting::set('site_url', $appUrl);

                    // Safely update application branding asset URLs if pointing to an old domain
                    $urlSettingsKeys = ['site_logo', 'site_logo_icon', 'site_favicon', 'seo_default_og_image', 'seo_default_twitter_image'];
                    foreach ($urlSettingsKeys as $key) {
                        $val = \App\Models\SiteSetting::get($key);
                        if (!empty($val) && is_string($val)) {
                            if (str_contains($val, 'jnifay.com') || (!empty($envHost) && str_contains($val, $envHost))) {
                                $updatedVal = preg_replace('~https?://[^/]+~', $appUrl, $val, 1);
                                \App\Models\SiteSetting::set($key, $updatedVal);
                                $logs[] = "🌐 تحديث رابط الإعداد [{$key}] ليتطابق مع الدومين الجديد: " . htmlspecialchars($updatedVal);
                            }
                        }
                    }
                }
            } catch (\Throwable $t) {
                // Non-fatal during initial migration phases
            }

            // Create Public Storage Link with safe fallbacks
            if ($createStorageLink) {
                $publicStorage = INSTALLER_ROOT_DIR . '/public/storage';
                $appStorage = INSTALLER_ROOT_DIR . '/storage/app/public';

                // Ensure target storage/app/public exists
                if (!file_exists($appStorage)) {
                    @mkdir($appStorage, 0775, true);
                }

                if (!file_exists($publicStorage)) {
                    $symlinkCreated = false;
                    if (function_exists('symlink')) {
                        $symlinkCreated = @symlink($appStorage, $publicStorage);
                    }
                    if (!$symlinkCreated) {
                        try {
                            \Illuminate\Support\Facades\Artisan::call('storage:link');
                            $symlinkCreated = file_exists($publicStorage);
                        } catch (\Throwable $t) {
                            // Fallback ignored
                        }
                    }

                    if (file_exists($publicStorage)) {
                        $logs[] = "📁 تم ربط مجلد التخزين العام (Storage Link) بنجاح.";
                    } else {
                        $logs[] = "ℹ️ مجلد التخزين العام جاهز في storage/app/public (التخزين السحابي Cloudflare R2 متصل افتراضياً).";
                    }
                } else {
                    $logs[] = "📁 مجلد التخزين العام (Storage Link) موجود مسبقاً.";
                }
            }

            // Clear All Caches & Logs to release disk quota and inodes
            try {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('view:clear');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
            } catch (\Throwable $t) {}

            // Prune storage to guarantee quota headroom on restricted shared hosting
            $logFile = INSTALLER_ROOT_DIR . '/storage/logs/laravel.log';
            if (file_exists($logFile)) {
                @file_put_contents($logFile, '');
            }

            // Clean framework cache/views to free inodes and disk blocks
            $cacheDirs = [
                INSTALLER_ROOT_DIR . '/storage/framework/cache/data',
                INSTALLER_ROOT_DIR . '/storage/framework/views',
                INSTALLER_ROOT_DIR . '/storage/framework/sessions',
            ];
            foreach ($cacheDirs as $dir) {
                if (is_dir($dir)) {
                    $files = @glob($dir . '/*');
                    if ($files) {
                        foreach ($files as $file) {
                            if (is_file($file) && !str_ends_with($file, '.gitignore')) {
                                @unlink($file);
                            }
                        }
                    }
                }
            }
            $logs[] = "🧹 تم مسح التخزين المؤقت وتحرير مساحة القرص (Cache & Storage Purged).";

            // Ensure storage directory exists
            $storageDir = dirname(INSTALLER_LOCK);
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0775, true);
            }

            // Resilient Lock Creation across all potential paths using multiple filesystem methods
            $lockCreated = false;
            $lockData = "Installed on " . date('Y-m-d H:i:s') . "\nURL: " . $appUrl . "\nDB Driver: " . $dbDriver . "\n";

            $targetLockPaths = [
                INSTALLER_LOCK,
                INSTALLER_FALLBACK_LOCK,
                INSTALLER_ROOT_LOCK,
                INSTALLER_ROOT_DIR . '/installed.lock',
                __DIR__ . '/installed.lock',
                __DIR__ . '/storage/installed.lock',
                __DIR__ . '/../storage/installed.lock',
                __DIR__ . '/../.installed',
                __DIR__ . '/../imgai/storage/installed.lock',
                __DIR__ . '/../imgai/.installed',
            ];

            foreach ($targetLockPaths as $lockPath) {
                $lockDir = dirname($lockPath);
                if (!is_dir($lockDir)) {
                    @mkdir($lockDir, 0775, true);
                }

                // Method 1: file_put_contents
                if (@file_put_contents($lockPath, $lockData) !== false) {
                    $lockCreated = true;
                } elseif (@file_put_contents($lockPath, '') !== false) {
                    $lockCreated = true;
                }

                // Method 2: fopen
                if (!file_exists($lockPath)) {
                    $fh = @fopen($lockPath, 'w');
                    if ($fh) {
                        @fwrite($fh, $lockData);
                        @fclose($fh);
                        $lockCreated = true;
                    }
                }

                // Method 3: touch
                if (!file_exists($lockPath) && function_exists('touch')) {
                    if (@touch($lockPath)) {
                        $lockCreated = true;
                    }
                }
            }

            $logs[] = "🔒 تم تفعيل قفل التثبيت بنجاح لمنع إعادة التهيئة.";
            $success = "تمت تهيئة وتثبيت المشروع بنجاح وربطه بالدومين!";
        } catch (\Throwable $e) {
            $error = "حدث خطأ أثناء إتمام التثبيت: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تهيئة IMGAI للاستضافة المشتركة</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --card-bg: rgba(17, 24, 39, 0.9);
            --border: rgba(255, 255, 255, 0.08);
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --accent: #8b5cf6;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --error: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Tajawal', 'Plus Jakarta Sans', sans-serif; }
        body {
            background-color: var(--bg);
            background-image: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.18) 0%, transparent 65%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .installer-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 640px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
        }
        .header { text-align: center; margin-bottom: 2rem; }
        .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: .4rem 1rem;
            border-radius: 999px;
            font-size: .88rem;
            font-weight: 600;
            margin-bottom: .8rem;
        }
        h1 { font-size: 1.65rem; font-weight: 700; color: #fff; margin-bottom: .4rem; }
        p.subtitle { color: var(--text-muted); font-size: .95rem; }
        .alert {
            padding: 1.1rem 1.3rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: .92rem;
            line-height: 1.6;
        }
        .alert-error { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }
        .alert-success { background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7; }
        .logs-box {
            background: #040711;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            font-family: monospace;
            font-size: .82rem;
            color: #38bdf8;
            max-height: 220px;
            overflow-y: auto;
            white-space: pre-wrap;
            direction: ltr;
            text-align: left;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .form-group { margin-bottom: 1.3rem; }
        label { display: block; font-size: .9rem; font-weight: 500; color: #cbd5e1; margin-bottom: .45rem; }
        .input-hint { font-size: .78rem; color: var(--text-muted); margin-top: .35rem; }
        input[type="text"], input[type="password"], input[type="number"], select {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .8rem 1rem;
            color: #fff;
            font-size: .95rem;
            transition: all .2s;
        }
        input[type="text"], input[type="password"], input[type="number"] {
            direction: ltr;
            text-align: left;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin-bottom: 1.1rem;
            cursor: pointer;
            color: #cbd5e1;
            font-size: .92rem;
        }
        .checkbox-group input { width: 1.15rem; height: 1.15rem; accent-color: var(--primary); cursor: pointer; }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            padding: .95rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: transform .15s, opacity .2s;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.35);
        }
        .btn-submit:hover { opacity: .95; transform: translateY(-1px); }
        .btn-submit:active { transform: translateY(0); }
        .info-pill {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .8rem 1rem;
            margin-bottom: 1.3rem;
            font-size: .85rem;
            color: #94a3b8;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="header">
        <div class="logo-badge">⚡ IMGAI Hosting Setup</div>
        <h1>تهيئة المشروع للاستضافة المشتركة</h1>
        <p class="subtitle">ربط تلقائي بالدومين وإعداد قاعدة البيانات وحزم التخزين</p>
    </div>

    <?php if ($isAlreadyInstalled && !$success): ?>
        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 14px; padding: 1.2rem 1.4rem; margin-bottom: 1.6rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <span style="display:inline-block; background:rgba(16,185,129,0.25); color:#34d399; padding:4px 12px; border-radius:999px; font-size:0.85rem; font-weight:700; margin-bottom:6px;">نظام التشغيل نشط ومؤمّن ✅</span>
                    <p style="margin:0; font-size:0.92rem; color:#cbd5e1; line-height:1.5;">المشروع مهيأ ومثبت بالفعل، وتم قفل التثبيت لمنع إعادة التشغيل من الإنترنت. إذا كنت ترغب بإعادة التثبيت، يجب إزالة ملف storage/installed.lock من الخادم يدوياً.</p>
                </div>
                <a href="<?= htmlspecialchars(!empty($currentAppUrl) ? $currentAppUrl : $detectedAppUrl) ?>/tools/overview" style="background:#10b981; color:#fff; padding:0.75rem 1.5rem; border-radius:10px; text-decoration:none; font-weight:700; font-size:0.95rem; white-space:nowrap; box-shadow:0 4px 14px rgba(16,185,129,0.35);">الانتقال للموقع الآن 🚀</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>🎉 <?= htmlspecialchars($success) ?></strong>
        </div>
        <?php if (!empty($logs)): ?>
            <div class="logs-box"><?= htmlspecialchars(implode("\n", $logs)) ?></div>
        <?php endif; ?>
        <div style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.35); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <div style="font-weight: 700; color: #38bdf8; font-size: 0.95rem; margin-bottom: 6px;">🔔 رابط Paddle Webhook لهذا الدومين:</div>
            <code style="font-size: 0.92rem; color: #f8fafc; word-break: break-all; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 6px; display: block;"><?= htmlspecialchars($appUrl) ?>/webhooks/paddle</code>
            <p style="font-size: 0.8rem; color: #94a3b8; margin: 8px 0 0 0; line-height: 1.4;">
                تذكير هام: قم بتحديث <strong>Webhook Destination URL</strong> في حساب Paddle (Developer Tools &gt; Notifications) بهذا الرابط الجديد.
            </p>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="<?= htmlspecialchars($appUrl) ?>/tools/overview" class="btn-submit" style="display:block; text-align:center; text-decoration:none;">الانتقال للموقع الآن 🚀</a>
            <a href="<?= htmlspecialchars($appUrl) ?>/admin/pricing" class="btn-submit" style="display:block; text-align:center; text-decoration:none; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.2); box-shadow:none;">لوحة تحكم الأسعار والاشتراكات ⚙️</a>
        </div>
    <?php elseif (!$isAlreadyInstalled): ?>

        <?php if (!empty($logs)): ?>
            <div class="logs-box"><?= htmlspecialchars(implode("\n", $logs)) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="install">

            <div class="form-group">
                <label>🌐 رابط الموقع (Website Application URL):</label>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 6px; padding: 4px 10px; margin-bottom: 8px; font-size: 0.82rem; color: #38bdf8;">
                    <span>Detected Website URL:</span>
                    <strong style="color: #ffffff;"><?= htmlspecialchars($detectedAppUrl) ?></strong>
                </div>
                <input type="text" name="app_url" value="<?= htmlspecialchars(!empty($currentAppUrl) ? $currentAppUrl : $detectedAppUrl) ?>" required>
                <div class="input-hint">تم اكتشاف الدومين تلقائيًا من المتصفح. يمكنك تعديله (http/https أو مع/بدون www) حسب إعدادات الشهادة والاستضافة.</div>
            </div>

            <div class="form-group">
                <label>🗄️ محرك قاعدة البيانات (Database Driver):</label>
                <select name="db_driver" id="db_driver" onchange="toggleDbFields(this.value)">
                    <option value="mysql" <?= ($currentDbDriver === 'mysql' || empty($currentDbDriver)) ? 'selected' : '' ?>>
                        MySQL — موصى به للاستضافة المشتركة (FreeHosting / cPanel) <?= $hasMysqlPdo ? '✅' : '❌' ?>
                    </option>
                    <option value="pgsql" <?= $currentDbDriver === 'pgsql' ? 'selected' : '' ?>>
                        PostgreSQL / Supabase DB سحابي <?= $hasPgsqlPdo ? '✅' : '(غير مدعوم على هذا الخادم ⚠️)' ?>
                    </option>
                    <option value="sqlite" <?= $currentDbDriver === 'sqlite' ? 'selected' : '' ?>>
                        SQLite (ملف محلي) <?= $hasSqlitePdo ? '✅' : '❌' ?>
                    </option>
                </select>
                <div class="input-hint">
                    <?php if (!$hasPgsqlPdo): ?>
                        <span style="color:#fbbf24;">⚠️ ملاحظة: تم اختيار MySQL تلقائياً لأن امتداد pdo_pgsql غير متاح في بيئة الاستضافة المشتركة الحالية.</span>
                    <?php else: ?>
                        <span>اختر MySQL للاستضافة المشتركة، أو ربط PostgreSQL مباشرة.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div id="remote_db_fields">
                <div class="grid-2">
                    <div class="form-group">
                        <label>خادم قاعدة البيانات (DB Host):</label>
                        <input type="text" name="db_host" id="db_host" value="<?= htmlspecialchars($currentDbHost ?: '127.0.0.1') ?>" required>
                        <div class="input-hint">غالباً <code>localhost</code> أو <code>127.0.0.1</code> في cPanel.</div>
                    </div>
                    <div class="form-group">
                        <label>المنفذ (DB Port):</label>
                        <input type="number" name="db_port" id="db_port" value="<?= htmlspecialchars($currentDbPort ?: '3306') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>اسم قاعدة البيانات (Database Name):</label>
                    <input type="text" name="db_name" id="db_name" value="<?= htmlspecialchars($currentDbName) ?>" placeholder="مثال: cpaneluser_imgai" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>اسم المستخدم (DB Username):</label>
                        <input type="text" name="db_user" id="db_user" value="<?= htmlspecialchars($currentDbUser) ?>" placeholder="مثال: cpaneluser_dbuser" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور (DB Password):</label>
                        <input type="password" name="db_pass" id="db_pass" value="<?= htmlspecialchars($currentDbPass) ?>" placeholder="••••••••">
                    </div>
                </div>
            </div>

            <div class="info-pill">
                ☁️ <strong>التخزين السحابي Cloudflare R2</strong> وإعدادات Supabase Auth ستظل محفوظة كما هي في ملف الإعدادات دون تغيير.
            </div>

            <label class="checkbox-group">
                <input type="checkbox" name="run_migrations" value="1" checked>
                <span>إنشاء وتحديث جداول قاعدة البيانات تلقائياً (Run Migrations)</span>
            </label>

            <label class="checkbox-group">
                <input type="checkbox" name="storage_link" value="1" checked>
                <span>تهيئة مجلد التخزين العام (Storage Link)</span>
            </label>

            <button type="submit" class="btn-submit">حفظ الإعدادات وبدء التثبيت ⚙️</button>
        </form>

        <script>
            function toggleDbFields(driver) {
                const fields = document.getElementById('remote_db_fields');
                const port = document.getElementById('db_port');
                const host = document.getElementById('db_host');
                if (driver === 'sqlite') {
                    fields.style.display = 'none';
                    document.getElementById('db_name').removeAttribute('required');
                    document.getElementById('db_user').removeAttribute('required');
                } else {
                    fields.style.display = 'block';
                    document.getElementById('db_name').setAttribute('required', 'required');
                    document.getElementById('db_user').setAttribute('required', 'required');
                    if (driver === 'pgsql') {
                        if (port.value === '3306') port.value = '5432';
                    } else if (driver === 'mysql') {
                        if (port.value === '5432') port.value = '3306';
                    }
                }
            }
            // Trigger on load
            toggleDbFields(document.getElementById('db_driver').value);
        </script>

    <?php endif; ?>
</div>

</body>
</html>
