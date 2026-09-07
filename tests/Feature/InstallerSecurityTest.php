<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallerSecurityTest extends TestCase
{
    /**
     * Test that when lock file exists, the installer blocks POST with 403.
     */
    public function test_installer_is_secured_when_lock_exists()
    {
        $installScript = base_path('public/install.php');
        $this->assertFileExists($installScript);

        $lock = base_path('storage/installed.lock');
        $hadLock = file_exists($lock);
        @touch($lock);

        $tempScript = tempnam(sys_get_temp_dir(), 'inst_test_');
        $appPath = addslashes(base_path());
        $code = <<<PHP
<?php
chdir('{$appPath}');
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST['action'] = 'install';
include 'public/install.php';
PHP;
        file_put_contents($tempScript, $code);

        try {
            $output = shell_exec(escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($tempScript));
        } finally {
            if (!$hadLock && file_exists($lock)) {
                @unlink($lock);
            }
            @unlink($tempScript);
        }

        $this->assertStringContainsString('نظام التثبيت مؤمّن ومغلق', (string)$output);
    }

    /**
     * Test that invalid database name in installer post request is rejected.
     */
    public function test_installer_rejects_malicious_database_name()
    {
        $tempScript = tempnam(sys_get_temp_dir(), 'inst_test_');
        $appPath = addslashes(base_path());
        $code = <<<PHP
<?php
chdir('{$appPath}');
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST['action'] = 'install';
\$_POST['db_driver'] = 'mysql';
\$_POST['db_host'] = '127.0.0.1';
\$_POST['db_port'] = '3306';
\$_POST['db_name'] = 'imgai; DROP TABLE users;';
\$_POST['db_user'] = 'root';
\$_POST['db_pass'] = 'secret';

\$lock = 'storage/installed.lock';
\$hadLock = file_exists(\$lock);
if (\$hadLock) @rename(\$lock, \$lock . '.testbak');

try {
    include 'public/install.php';
} finally {
    if (\$hadLock && file_exists(\$lock . '.testbak')) {
        @rename(\$lock . '.testbak', \$lock);
    }
}
PHP;
        file_put_contents($tempScript, $code);

        $output = shell_exec(escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($tempScript));
        @unlink($tempScript);

        $this->assertStringContainsString('غير صالح', (string)$output);
    }
}
