<?php

namespace Tests\Unit;

use App\Services\InstallerDatabaseException;
use App\Services\InstallerDatabaseService;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class InstallerDatabaseServiceTest extends TestCase
{
    /**
     * Test valid database identifiers.
     */
    public function test_valid_database_identifiers()
    {
        $validNames = [
            'imgai',
            'imgai_db',
            'cpaneluser_imgai',
            'imgai-production',
            '_internal_db',
            'db123',
            'MY_DB_2026',
        ];

        foreach ($validNames as $name) {
            $this->assertTrue(
                InstallerDatabaseService::isValidIdentifier($name),
                "Expected [{$name}] to be a valid identifier."
            );

            $escaped = InstallerDatabaseService::escapeIdentifier($name);
            $this->assertStringStartsWith('`', $escaped);
            $this->assertStringEndsWith('`', $escaped);
        }
    }

    /**
     * Test SQL injection payloads and invalid database names are rejected.
     */
    public function test_sql_injection_payloads_are_strictly_rejected()
    {
        $maliciousNames = [
            'imgai; DROP TABLE users;',
            'imgai` --',
            "' OR '1'='1",
            'imgai/*comment*/',
            'imgai database',
            'imgai&rm -rf',
            "imgai\0nullbyte",
            'imgai;--',
            'imgai UNION SELECT 1',
            '', // empty
            str_repeat('a', 65), // > 64 chars
        ];

        foreach ($maliciousNames as $malicious) {
            $this->assertFalse(
                InstallerDatabaseService::isValidIdentifier($malicious),
                "Expected [{$malicious}] to be rejected as invalid/malicious."
            );

            $this->expectException(InvalidArgumentException::class);
            InstallerDatabaseService::escapeIdentifier($malicious);
        }
    }

    /**
     * Test .env value formatting for passwords with special characters (#, =, spaces, quotes, etc.).
     */
    public function test_env_value_formatting_with_special_characters()
    {
        // Simple string without special chars
        $this->assertEquals('simplePass123', InstallerDatabaseService::formatEnvValue('simplePass123'));

        // Password with #
        $this->assertEquals('"p#ssword"', InstallerDatabaseService::formatEnvValue('p#ssword'));

        // Password with =
        $this->assertEquals('"pass=word"', InstallerDatabaseService::formatEnvValue('pass=word'));

        // Password with spaces
        $this->assertEquals('"pass word"', InstallerDatabaseService::formatEnvValue('pass word'));

        // Password with double quotes
        $this->assertEquals('"pass\"word"', InstallerDatabaseService::formatEnvValue('pass"word'));

        // Password with backslash
        $this->assertEquals('"pass\\\\word"', InstallerDatabaseService::formatEnvValue('pass\\word'));

        // Password with $
        $this->assertEquals('"pass\\$word"', InstallerDatabaseService::formatEnvValue('pass$word'));

        // Complex password with multiple special characters
        $complex = 'p#ss=w"o$rd 123!';
        $formatted = InstallerDatabaseService::formatEnvValue($complex);
        $this->assertStringStartsWith('"', $formatted);
        $this->assertStringEndsWith('"', $formatted);
        $this->assertStringContainsString('p#ss=w', $formatted);
    }

    /**
     * Test Scenario: Database does NOT exist -> CREATE DATABASE is executed, and reconnects.
     */
    public function test_provision_mysql_creates_database_when_it_does_not_exist()
    {
        $executedSql = [];
        $connectedDsns = [];

        // Mock statement for information_schema query
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(false); // Does not exist

        // Mock Server PDO
        $serverPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepare', 'exec'])
            ->getMock();

        $serverPdo->method('prepare')->willReturn($mockStmt);
        $serverPdo->expects($this->once())
            ->method('exec')
            ->with($this->callback(function ($sql) use (&$executedSql) {
                $executedSql[] = $sql;
                return str_contains($sql, 'CREATE DATABASE IF NOT EXISTS `imgai`') &&
                       str_contains($sql, 'utf8mb4');
            }))
            ->willReturn(1);

        // Mock Database PDO (for reconnection)
        $databasePdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdoFactory = function ($dsn, $user, $pass, $options) use (&$connectedDsns, $serverPdo, $databasePdo) {
            $connectedDsns[] = $dsn;
            if (!str_contains($dsn, 'dbname=')) {
                return $serverPdo;
            }
            return $databasePdo;
        };

        $result = InstallerDatabaseService::provisionMysql(
            '127.0.0.1',
            3306,
            'db_user',
            'p#ss=word',
            'imgai',
            $pdoFactory
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['created']);
        $this->assertTrue($result['reconnected']);

        // Assert first connection had no dbname
        $this->assertEquals('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $connectedDsns[0]);

        // Assert second connection had database=imgai
        $this->assertEquals('mysql:host=127.0.0.1;port=3306;dbname=imgai;charset=utf8mb4', $connectedDsns[1]);

        // Assert CREATE DATABASE was executed
        $this->assertCount(1, $executedSql);
        $this->assertStringContainsString('CREATE DATABASE IF NOT EXISTS `imgai`', $executedSql[0]);
    }

    /**
     * Test Scenario: Database ALREADY exists -> Uses existing database, DOES NOT execute CREATE DATABASE.
     */
    public function test_provision_mysql_uses_existing_database_without_recreation()
    {
        $executedSql = [];
        $connectedDsns = [];

        // Mock statement for information_schema query
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn('imgai'); // Already exists!

        // Mock Server PDO
        $serverPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepare', 'exec'])
            ->getMock();

        $serverPdo->method('prepare')->willReturn($mockStmt);
        // exec should NOT be called to create database
        $serverPdo->expects($this->never())->method('exec');

        // Mock Database PDO (for reconnection)
        $databasePdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdoFactory = function ($dsn, $user, $pass, $options) use (&$connectedDsns, $serverPdo, $databasePdo) {
            $connectedDsns[] = $dsn;
            if (!str_contains($dsn, 'dbname=')) {
                return $serverPdo;
            }
            return $databasePdo;
        };

        $result = InstallerDatabaseService::provisionMysql(
            '127.0.0.1',
            3306,
            'db_user',
            'p#ss=word',
            'imgai',
            $pdoFactory
        );

        $this->assertTrue($result['success']);
        $this->assertFalse($result['created']); // Not created, existing used
        $this->assertTrue($result['reconnected']);

        // Assert reconnection to existing DB
        $this->assertEquals('mysql:host=127.0.0.1;port=3306;dbname=imgai;charset=utf8mb4', $connectedDsns[1]);
        $this->assertEmpty($executedSql);
    }

    /**
     * Test Scenario: Invalid connection credentials -> Throws clear InstallerDatabaseException.
     */
    public function test_provision_mysql_fails_gracefully_on_invalid_credentials()
    {
        $pdoFactory = function ($dsn, $user, $pass, $options) {
            throw new PDOException("Access denied for user 'bad_user'@'localhost' (using password: YES)");
        };

        $this->expectException(InstallerDatabaseException::class);
        $this->expectExceptionMessage("فشل الاتصال بخادم MySQL");

        InstallerDatabaseService::provisionMysql(
            '127.0.0.1',
            3306,
            'bad_user',
            'wrong_pass',
            'imgai',
            $pdoFactory
        );
    }

    /**
     * Test Scenario: Insufficient privileges for CREATE DATABASE -> Throws clear user-friendly exception.
     */
    public function test_provision_mysql_fails_when_user_lacks_create_database_privilege()
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(false); // Does not exist

        $serverPdo = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepare', 'exec'])
            ->getMock();

        $serverPdo->method('prepare')->willReturn($mockStmt);
        $serverPdo->method('exec')->willThrowException(
            new PDOException("Access denied for user 'db_user'@'%' to database 'imgai'")
        );

        $pdoFactory = function ($dsn, $user, $pass, $options) use ($serverPdo) {
            return $serverPdo;
        };

        $this->expectException(InstallerDatabaseException::class);
        $this->expectExceptionMessage("فشل إنشاء قاعدة البيانات `imgai` على خادم MySQL");

        InstallerDatabaseService::provisionMysql(
            '127.0.0.1',
            3306,
            'db_user',
            'password',
            'imgai',
            $pdoFactory
        );
    }

    /**
     * Test Real PDO Connection failure when connection details are wrong.
     */
    public function test_real_pdo_connection_failure_with_wrong_connection_details()
    {
        $this->expectException(InstallerDatabaseException::class);
        $this->expectExceptionMessage("فشل الاتصال بخادم MySQL");

        // Attempt connecting to a non-existent port with real PDO
        InstallerDatabaseService::connectServer(
            '127.0.0.1',
            49151, // unlikely to be listening
            'invalid_user',
            'invalid_password',
            1 // 1 second timeout
        );
    }
}
