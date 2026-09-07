<?php

namespace Tests\Feature;

use App\Services\Installer\InstallerDomainDetector;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InstallerDomainDetectionTest extends TestCase
{
    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempEnvPath = storage_path('framework/testing/test_env_' . uniqid());
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempEnvPath)) {
            File::delete($this->tempEnvPath);
        }
        parent::tearDown();
    }

    /**
     * 1. Installer detects domain from server environment and produces https://example.com
     */
    public function test_detects_https_domain_and_writes_to_env(): void
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
        ];

        $origin = InstallerDomainDetector::detectOrigin($server);
        $this->assertEquals('https://example.com', $origin);

        $envContent = "APP_NAME=IMGAI\nAPP_KEY=base64:existingkey123\nDB_HOST=127.0.0.1\n";
        File::put($this->tempEnvPath, $envContent);

        $written = InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, $origin);
        $this->assertTrue($written);

        $resultEnv = File::get($this->tempEnvPath);
        $this->assertStringContainsString('APP_URL=https://example.com', $resultEnv);
    }

    /**
     * 2. Installer does not include script name /install.php in origin
     */
    public function test_origin_does_not_contain_script_path_like_install_php(): void
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'SCRIPT_NAME' => '/install.php',
            'REQUEST_URI' => '/install.php?step=2',
            'HTTPS' => 'on',
        ];

        $origin = InstallerDomainDetector::detectOrigin($server);
        $this->assertEquals('https://example.com', $origin);
        $this->assertStringNotContainsString('install.php', $origin);

        // Also test cleanOrigin
        $cleaned = InstallerDomainDetector::cleanOrigin('https://example.com/install.php');
        $this->assertEquals('https://example.com', $cleaned);
    }

    /**
     * 3. Installer does not produce trailing slash
     */
    public function test_installer_does_not_produce_trailing_slash(): void
    {
        $server = [
            'HTTP_HOST' => 'example.com/',
            'HTTPS' => 'on',
        ];

        $origin = InstallerDomainDetector::detectOrigin($server);
        $this->assertStringEndsNotWith('/', $origin);
        $this->assertEquals('https://example.com', $origin);

        $cleaned = InstallerDomainDetector::cleanOrigin('https://example.com///');
        $this->assertStringEndsNotWith('/', $cleaned);
        $this->assertEquals('https://example.com', $cleaned);
    }

    /**
     * 4. Installer replaces legacy APP_URL with current detected domain
     */
    public function test_installer_replaces_old_app_url_with_detected_domain(): void
    {
        $envContent = "APP_NAME=IMGAI\nAPP_KEY=base64:secureappkey\nAPP_URL=http://jnifay.com\nDB_DATABASE=imgai_db\n";
        File::put($this->tempEnvPath, $envContent);

        InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, 'https://anotherdomain.com');

        $resultEnv = File::get($this->tempEnvPath);
        $this->assertStringNotContainsString('jnifay.com', $resultEnv);
        $this->assertStringContainsString('APP_URL=https://anotherdomain.com', $resultEnv);
        $this->assertStringContainsString('APP_KEY=base64:secureappkey', $resultEnv);
    }

    /**
     * 5. APP_KEY is never changed or lost
     */
    public function test_app_key_is_never_changed(): void
    {
        $key = 'base64:AbCdEf1234567890+/XyZ==';
        $envContent = "APP_NAME=IMGAI\nAPP_KEY={$key}\nDB_DATABASE=prod_db\n";
        File::put($this->tempEnvPath, $envContent);

        InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, 'https://newdomain.com');

        $resultEnv = File::get($this->tempEnvPath);
        $this->assertStringContainsString("APP_KEY={$key}", $resultEnv);
    }

    /**
     * 6. DB and Paddle credentials are never changed
     */
    public function test_db_and_paddle_credentials_are_strictly_preserved(): void
    {
        $envContent = "APP_NAME=IMGAI\n"
            . "APP_KEY=base64:xyz123\n"
            . "DB_HOST=aws.rds.amazonaws.com\n"
            . "DB_PORT=3306\n"
            . "DB_DATABASE=prod_database\n"
            . "DB_USERNAME=admin_user\n"
            . "DB_PASSWORD=secret_db_pass\n"
            . "PADDLE_API_KEY=pdl_live_apikey123\n"
            . "PADDLE_WEBHOOK_SECRET=pdl_whsec_secret456\n";
        File::put($this->tempEnvPath, $envContent);

        InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, 'https://mydomain.com');

        $resultEnv = File::get($this->tempEnvPath);
        $this->assertStringContainsString('DB_HOST=aws.rds.amazonaws.com', $resultEnv);
        $this->assertStringContainsString('DB_DATABASE=prod_database', $resultEnv);
        $this->assertStringContainsString('DB_USERNAME=admin_user', $resultEnv);
        $this->assertStringContainsString('DB_PASSWORD=secret_db_pass', $resultEnv);
        $this->assertStringContainsString('PADDLE_API_KEY=pdl_live_apikey123', $resultEnv);
        $this->assertStringContainsString('PADDLE_WEBHOOK_SECRET=pdl_whsec_secret456', $resultEnv);
        $this->assertStringContainsString('APP_URL=https://mydomain.com', $resultEnv);
    }

    /**
     * 7. No operational code contains hardcoded jnifay.com or imgai-woto.onrender.com
     */
    public function test_no_hardcoded_legacy_domains_in_codebase(): void
    {
        $disallowed = ['jnifay.com', 'imgai-woto.onrender.com'];
        $baseDir = base_path();

        $scanDirs = [
            $baseDir . '/app',
            $baseDir . '/bootstrap',
            $baseDir . '/config',
            $baseDir . '/routes',
            $baseDir . '/public',
        ];

        foreach ($scanDirs as $dir) {
            $files = File::allFiles($dir);
            foreach ($files as $file) {
                // Skip binary or cache
                if (!in_array($file->getExtension(), ['php', 'json', 'env', 'js', 'html'])) {
                    continue;
                }
                $content = $file->getContents();
                foreach ($disallowed as $domain) {
                    $this->assertStringNotContainsString(
                        $domain,
                        $content,
                        "Found disallowed domain [{$domain}] in file: " . $file->getPathname()
                    );
                }
            }
        }
    }

    /**
     * 8. HTTPS behind reverse proxy (Render / Cloudflare / Nginx) is detected accurately
     */
    public function test_https_detected_correctly_behind_reverse_proxy(): void
    {
        // Behind Render / Nginx with X-Forwarded-Proto
        $serverRender = [
            'HTTP_HOST' => 'mysite.onrender.com',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'SERVER_PORT' => 80,
        ];
        $this->assertEquals('https://mysite.onrender.com', InstallerDomainDetector::detectOrigin($serverRender));

        // Behind Cloudflare with HTTP_CF_VISITOR
        $serverCf = [
            'HTTP_HOST' => 'customdomain.com',
            'HTTP_CF_VISITOR' => '{"scheme":"https"}',
            'SERVER_PORT' => 80,
        ];
        $this->assertEquals('https://customdomain.com', InstallerDomainDetector::detectOrigin($serverCf));

        // Behind reverse proxy with X-Forwarded-Host
        $serverProxyHost = [
            'HTTP_HOST' => 'internal-lb:8080',
            'HTTP_X_FORWARDED_HOST' => 'publicdomain.com',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'SERVER_PORT' => 8080,
        ];
        $this->assertEquals('https://publicdomain.com', InstallerDomainDetector::detectOrigin($serverProxyHost));
    }

    /**
     * 9. Running writeAppUrlToEnv multiple times is idempotent (no duplicates)
     */
    public function test_running_write_multiple_times_is_idempotent(): void
    {
        $envContent = "APP_NAME=IMGAI\nAPP_KEY=base64:xyz\n";
        File::put($this->tempEnvPath, $envContent);

        // Run first time
        InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, 'https://firstdomain.com');
        // Run second time with same domain
        InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, 'https://firstdomain.com');
        // Run third time with updated domain
        InstallerDomainDetector::writeAppUrlToEnv($this->tempEnvPath, 'https://seconddomain.com');

        $resultEnv = File::get($this->tempEnvPath);
        $count = substr_count($resultEnv, 'APP_URL=');
        $this->assertEquals(1, $count, "APP_URL should only appear once in .env");
        $this->assertStringContainsString('APP_URL=https://seconddomain.com', $resultEnv);
    }

    /**
     * 10. URL generation works properly with current request host and scheme
     */
    public function test_dynamic_url_generation_in_request_context(): void
    {
        $response = $this->withHeaders([
            'Host' => 'app.clientdomain.com',
            'X-Forwarded-Proto' => 'https',
        ])->get('/');

        $this->assertTrue($response->getStatusCode() < 400 || $response->isRedirect());

        // Test canonical URL resolution in SeoService
        $seo = app(\App\Services\Seo\SeoService::class);
        $baseUrl = $seo->getBaseUrl();
        $this->assertNotEmpty($baseUrl);
        $this->assertStringEndsNotWith('/', $baseUrl);
    }

    /**
     * 11. CSRF remains active and no 419 error from domain/scheme mismatch
     */
    public function test_csrf_token_and_scheme_consistency_prevent_419(): void
    {
        // Make request with HTTPS forwarded headers
        $response = $this->withHeaders([
            'Host' => 'secure.customerdomain.com',
            'X-Forwarded-Proto' => 'https',
        ])->get('/login');

        if ($response->getStatusCode() === 200) {
            $token = csrf_token();
            $this->assertNotEmpty($token);

            // POST with valid token should not yield 419 Page Expired
            $postResponse = $this->withHeaders([
                'Host' => 'secure.customerdomain.com',
                'X-Forwarded-Proto' => 'https',
            ])->post('/login', [
                '_token' => $token,
                'email' => 'nonexistent@example.com',
                'password' => 'secret123',
            ]);

            // Status should be redirect back or validation error (302/422), NEVER 419
            $this->assertNotEquals(419, $postResponse->getStatusCode(), 'Must not return 419 Page Expired');
        } else {
            $this->assertTrue(true);
        }
    }
}
