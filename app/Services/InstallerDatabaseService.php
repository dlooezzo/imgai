<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

class InstallerDatabaseException extends Exception
{
}

class InstallerDatabaseService
{
    /**
     * Validate database identifier to strictly prevent SQL injection.
     * Allowed: 1-64 characters, starting with letter/number/underscore,
     * containing only letters, numbers, underscores, or hyphens.
     */
    public static function isValidIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_][a-zA-Z0-9_-]{0,63}$/', $name);
    }

    /**
     * Escape database identifier for MySQL using backticks.
     * Throws InvalidArgumentException if the identifier contains invalid characters.
     */
    public static function escapeIdentifier(string $name): string
    {
        if (!self::isValidIdentifier($name)) {
            throw new InvalidArgumentException(
                "اسم قاعدة البيانات [{$name}] غير صالح. يجب أن يتكون من 1 إلى 64 حرفاً ويحتوي فقط على أحرف إنجليزية وأرقام وشرطة سفلية (_) أو شرطة (-) بدون مسافات أو رموز خاصة."
            );
        }

        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * Format value safely for .env storage.
     * Handles special characters like #, =, quotes, backslashes, spaces, $.
     */
    public static function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // If the value contains characters that can break .env parsing (space, #, =, quotes, backslash, $, newline)
        if (preg_match('/[\s#="\'\\\\\$]/', $value)) {
            $escaped = addcslashes($value, "\\\"\$");
            return '"' . $escaped . '"';
        }

        return $value;
    }

    /**
     * Create default PDO options for MySQL connections.
     */
    public static function getPdoOptions(int $timeout = 5): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => $timeout,
        ];
    }

    /**
     * Connect to MySQL server without specifying a database.
     */
    public static function connectServer(
        string $host,
        int $port,
        string $user,
        string $pass,
        int $timeout = 5,
        ?callable $pdoFactory = null
    ): PDO {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $options = self::getPdoOptions($timeout);

        try {
            if ($pdoFactory) {
                return $pdoFactory($dsn, $user, $pass, $options);
            }
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new InstallerDatabaseException(
                "فشل الاتصال بخادم MySQL ({$host}:{$port}): " . $e->getMessage() . 
                " (تأكد من عنوان الخادم DB_HOST، المنفذ DB_PORT، اسم المستخدم DB_USERNAME، وكلمة المرور DB_PASSWORD)."
            );
        }
    }

    /**
     * Check if a database already exists on the MySQL server.
     */
    public static function databaseExists(PDO $serverPdo, string $dbName): bool
    {
        try {
            $stmt = $serverPdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$dbName]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            // Fallback for restricted users: try switching to DB directly
            try {
                $escaped = self::escapeIdentifier($dbName);
                $serverPdo->exec("USE {$escaped}");
                return true;
            } catch (Throwable $ignored) {
                return false;
            }
        }
    }

    /**
     * Create database safely if it does not already exist.
     */
    public static function createDatabaseIfNotExists(PDO $serverPdo, string $dbName): void
    {
        $escaped = self::escapeIdentifier($dbName);
        $sql = "CREATE DATABASE IF NOT EXISTS {$escaped} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        try {
            $serverPdo->exec($sql);
        } catch (PDOException $e) {
            throw new InstallerDatabaseException(
                "فشل إنشاء قاعدة البيانات {$escaped} على خادم MySQL: " . $e->getMessage() . 
                " (تأكد من أن المستخدم يمتلك صلاحية CREATE DATABASE على الخادم، أو أنشئ قاعدة البيانات يدوياً)."
            );
        }
    }

    /**
     * Connect to MySQL server with a specific database selected.
     */
    public static function connectDatabase(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $dbName,
        int $timeout = 5,
        ?callable $pdoFactory = null
    ): PDO {
        self::escapeIdentifier($dbName); // Validate safety
        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        $options = self::getPdoOptions($timeout);

        try {
            if ($pdoFactory) {
                return $pdoFactory($dsn, $user, $pass, $options);
            }
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new InstallerDatabaseException(
                "فشل الاتصال بقاعدة البيانات [{$dbName}] على خادم MySQL: " . $e->getMessage()
            );
        }
    }

    /**
     * Orchestrate full MySQL provisioning:
     * 1. Validate database name against SQL injection.
     * 2. Connect to MySQL server without database.
     * 3. Check if database exists.
     * 4. If not exists -> CREATE DATABASE IF NOT EXISTS `dbname`.
     *    If exists -> use without recreation.
     * 5. Reconnect to MySQL server with database=dbname.
     *
     * @return array ['logs' => string[], 'created' => bool, 'reconnected' => bool]
     * @throws InstallerDatabaseException|InvalidArgumentException
     */
    public static function provisionMysql(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $dbName,
        ?callable $pdoFactory = null
    ): array {
        $logs = [];

        // 1. Strict validation
        if (!self::isValidIdentifier($dbName)) {
            throw new InvalidArgumentException(
                "اسم قاعدة البيانات [{$dbName}] غير صالح أو يحتوي على رموز غير مسموح بها. يجب أن يتكون من 1 إلى 64 حرفاً ويحتوي فقط على أحرف إنجليزية وأرقام وشرطة سفلية (_) أو شرطة (-) لمنع أي ثغرات حقن SQL."
            );
        }

        // 2. Connect to server without database
        $serverPdo = self::connectServer($host, $port, $user, $pass, 5, $pdoFactory);
        $logs[] = "✅ تم الاتصال بنجاح بخادم MySQL ({$host}:{$port}) بدون تحديد قاعدة بيانات مسبقاً.";

        // 3. Check if database exists
        $exists = self::databaseExists($serverPdo, $dbName);
        $created = false;

        if ($exists) {
            $logs[] = "ℹ️ قاعدة البيانات `{$dbName}` موجودة مسبقاً، سيتم استخدامها مباشرة دون إعادة إنشائها.";
        } else {
            // 4. Create database safely
            self::createDatabaseIfNotExists($serverPdo, $dbName);
            $created = true;
            $logs[] = "✅ تم إنشاء قاعدة البيانات `{$dbName}` بنجاح (CREATE DATABASE IF NOT EXISTS) بترميز utf8mb4.";
        }

        // 5. Reconnect with database=dbname
        self::connectDatabase($host, $port, $user, $pass, $dbName, 5, $pdoFactory);
        $logs[] = "✅ تم إعادة الاتصال بنجاح بقاعدة البيانات المحددة (database={$dbName}).";

        return [
            'success' => true,
            'logs' => $logs,
            'created' => $created,
            'reconnected' => true,
        ];
    }
}
