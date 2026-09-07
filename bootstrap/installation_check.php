<?php
/**
 * RDS Persistent Installation Checker
 * 
 * Verifies application installation state directly against the persistent database (RDS).
 * Does NOT rely solely on ephemeral filesystem lock files or container environment variables.
 * 
 * Authoritative installation requires the confirmed existence of at minimum:
 * - migrations
 * - users
 * - site_settings
 * - sessions
 */

if (!function_exists('checkApplicationInstallationStatus')) {
    function checkApplicationInstallationStatus(string $projectRoot): array
    {
        $requiredTables = ['migrations', 'users', 'site_settings', 'sessions'];
        $lockFile = $projectRoot . '/storage/installed.lock';

        // 1. Fast local cache shortcut: If lock file exists and is non-empty, verify it
        if (file_exists($lockFile)) {
            return [
                'status' => 'installed',
                'reason' => 'lock_file_present',
                'source' => 'local_cache',
                'tables' => $requiredTables,
            ];
        }

        // 2. Resolve database credentials with environment precedence (getenv/$_ENV > .env file)
        $envVars = [];
        $envPath = $projectRoot . '/.env';
        if (file_exists($envPath)) {
            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v, " \t\n\r\0\x0B\"'");
                    $envVars[$k] = $v;
                }
            }
        }

        $getVar = function (string $key, $default = null) use ($envVars) {
            $val = getenv($key);
            if ($val !== false && $val !== '') {
                return $val;
            }
            if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
                return $_ENV[$key];
            }
            if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
                return $_SERVER[$key];
            }
            return $envVars[$key] ?? $default;
        };

        $connection = strtolower($getVar('DB_CONNECTION', 'mysql'));
        $database = $getVar('DB_DATABASE', '');
        $host = $getVar('DB_HOST', '127.0.0.1');
        $port = $getVar('DB_PORT', '3306');
        $username = $getVar('DB_USERNAME', 'root');
        $password = $getVar('DB_PASSWORD', '');

        // Handle SQLite (useful for local development, tests, and in-memory execution)
        if ($connection === 'sqlite') {
            $dbPath = $database ?: ($projectRoot . '/database/database.sqlite');
            if ($dbPath === ':memory:' || file_exists($dbPath)) {
                try {
                    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 2,
                    ]);
                    $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
                    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name IN ({$placeholders})");
                    $stmt->execute($requiredTables);
                    $foundTables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    $count = count(array_unique($foundTables));

                    if ($count === count($requiredTables)) {
                        @touch($lockFile);
                        return ['status' => 'installed', 'reason' => 'rds_verified', 'tables' => $foundTables];
                    }
                    if ($count === 0) {
                        return ['status' => 'uninstalled', 'reason' => 'db_empty', 'tables' => []];
                    }
                    return ['status' => 'partially_installed', 'reason' => 'missing_tables', 'found' => $foundTables, 'required' => $requiredTables];
                } catch (Throwable $e) {
                    return ['status' => 'db_error', 'reason' => $e->getMessage()];
                }
            }
            return ['status' => 'uninstalled', 'reason' => 'sqlite_file_missing'];
        }

        // If no database name or host is configured, we cannot connect to RDS
        if (empty($database) || empty($host)) {
            return ['status' => 'uninstalled', 'reason' => 'no_db_configuration'];
        }

        // 3. Connect to RDS MySQL/MariaDB/PostgreSQL via PDO with strict timeout
        try {
            if ($connection === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            }

            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3, // 3-second strict timeout for health check
            ]);

            // Query existing tables
            if ($connection === 'pgsql') {
                $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('migrations', 'users', 'site_settings', 'sessions')");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name IN ('migrations', 'users', 'site_settings', 'sessions')");
                $stmt->execute([$database]);
            }

            $foundTables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $foundCount = count(array_unique($foundTables));

            if ($foundCount === count($requiredTables)) {
                // Application is genuinely initialized in RDS!
                // Create local lock file as a container performance cache
                $storageDir = $projectRoot . '/storage';
                if (!is_dir($storageDir)) {
                    @mkdir($storageDir, 0775, true);
                }
                @touch($lockFile);

                return [
                    'status' => 'installed',
                    'reason' => 'rds_verified',
                    'source' => 'rds_database',
                    'tables' => $foundTables,
                ];
            }

            if ($foundCount === 0) {
                return [
                    'status' => 'uninstalled',
                    'reason' => 'database_empty',
                    'tables' => [],
                ];
            }

            return [
                'status' => 'partially_installed',
                'reason' => 'partial_schema_detected',
                'found' => $foundTables,
                'required' => $requiredTables,
            ];
        } catch (Throwable $e) {
            // If connection fails, return diagnostic error
            return [
                'status' => 'db_error',
                'reason' => $e->getMessage(),
            ];
        }
    }
}
