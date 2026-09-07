<?php

namespace App\Services\Installer;

class InstallerDomainDetector
{
    /**
     * Detect canonical origin (e.g. https://example.com) strictly from HTTP request headers.
     * Guaranteed:
     * - Protocol is accurately detected across Render, Cloudflare, Nginx reverse proxies.
     * - No script paths (/install.php).
     * - No trailing slashes.
     * - No default ports (:80, :443).
     * - Validated RFC-compliant host.
     *
     * @param array<string, mixed>|null $server
     * @return string Canonical origin (e.g. "https://example.com")
     */
    public static function detectOrigin(?array $server = null): string
    {
        $s = $server !== null ? $server : $_SERVER;

        // 1. Detect HTTPS Scheme
        $isHttps = false;

        if (!empty($s['HTTP_X_FORWARDED_PROTO'])) {
            $protoParts = explode(',', (string)$s['HTTP_X_FORWARDED_PROTO']);
            if (strtolower(trim($protoParts[0])) === 'https') {
                $isHttps = true;
            }
        }

        if (!$isHttps && !empty($s['HTTP_X_FORWARDED_SSL']) && strtolower((string)$s['HTTP_X_FORWARDED_SSL']) === 'on') {
            $isHttps = true;
        }

        if (!$isHttps && !empty($s['HTTP_CF_VISITOR'])) {
            $cfVisitor = (string)$s['HTTP_CF_VISITOR'];
            if (str_contains(strtolower($cfVisitor), '"scheme":"https"')) {
                $isHttps = true;
            }
        }

        if (!$isHttps && !empty($s['HTTPS']) && strtolower((string)$s['HTTPS']) !== 'off') {
            $isHttps = true;
        }

        $serverPort = (int)($s['SERVER_PORT'] ?? 0);
        $forwardedPort = (int)($s['HTTP_X_FORWARDED_PORT'] ?? 0);
        if (!$isHttps && ($serverPort === 443 || $forwardedPort === 443)) {
            $isHttps = true;
        }

        $scheme = $isHttps ? 'https' : 'http';

        // 2. Detect Host Header (prioritize reverse proxy headers)
        $rawHost = '';
        if (!empty($s['HTTP_X_FORWARDED_HOST'])) {
            $forwardedHosts = explode(',', (string)$s['HTTP_X_FORWARDED_HOST']);
            $rawHost = trim($forwardedHosts[0]);
        }

        if (empty($rawHost)) {
            $rawHost = (string)($s['HTTP_HOST'] ?? ($s['SERVER_NAME'] ?? 'localhost'));
        }

        // 3. Strip paths, query strings, and script fragments if present in header
        $rawHost = trim(explode('/', $rawHost)[0]);
        $rawHost = trim(explode('?', $rawHost)[0]);

        // 4. Strip standard port numbers (80 and 443), and custom ports if running on standard proxy
        $hostOnly = preg_replace('/:(80|443)$/', '', $rawHost);
        // If host contains custom non-standard port (e.g. :8000 on local dev), preserve only if non-standard
        $port = '';
        if (preg_match('/:([0-9]+)$/', $hostOnly, $m)) {
            $p = (int)$m[1];
            if ($p !== 80 && $p !== 443) {
                $port = ':' . $p;
            }
            $hostOnly = preg_replace('/:([0-9]+)$/', '', $hostOnly);
        }

        // 5. Sanitize and validate RFC hostname
        $cleanHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $hostOnly);
        if (empty($cleanHost) || str_contains($cleanHost, '..') || str_starts_with($cleanHost, '.') || str_ends_with($cleanHost, '.')) {
            $cleanHost = 'localhost';
        }

        // 6. Build clean origin
        $origin = $scheme . '://' . $cleanHost . $port;

        return rtrim($origin, '/');
    }

    /**
     * Clean and format any given URL into a clean canonical origin (e.g. https://example.com).
     * Strips paths (/install.php), trailing slashes, and redundant ports.
     */
    public static function cleanOrigin(string $url, string $defaultScheme = 'https'): string
    {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }

        // If no scheme present, prepend defaultScheme
        if (!preg_match('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $url)) {
            $url = $defaultScheme . '://' . ltrim($url, '/');
        }

        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return '';
        }

        $scheme = strtolower($parsed['scheme'] ?? $defaultScheme);
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) && !in_array($parsed['port'], [80, 443]) ? ':' . $parsed['port'] : '';

        return rtrim($scheme . '://' . $host . $port, '/');
    }

    /**
     * Safely and idempotently write or update APP_URL in a .env file.
     * Guarantees:
     * - Replaces existing APP_URL if present.
     * - Inserts APP_URL if missing.
     * - Does NOT duplicate APP_URL if run multiple times.
     * - Preserves APP_KEY, DB credentials, Paddle secrets, and all other environment variables.
     *
     * @param string $envFilePath Path to .env file
     * @param string $appUrl Canonical origin to write
     * @return bool True if successful
     */
    public static function writeAppUrlToEnv(string $envFilePath, string $appUrl): bool
    {
        $appUrl = rtrim(trim($appUrl), '/');
        if (empty($appUrl)) {
            return false;
        }

        if (!file_exists($envFilePath)) {
            return false;
        }

        $content = file_get_contents($envFilePath);
        if ($content === false) {
            return false;
        }

        $targetLine = "APP_URL=" . $appUrl;

        // 1. If APP_URL= already exists in .env, replace it cleanly
        if (preg_match('/^APP_URL=.*$/m', $content)) {
            $updatedContent = preg_replace('/^APP_URL=.*$/m', $targetLine, $content);
        } else {
            // 2. If APP_URL= does not exist, insert right after APP_DEBUG or APP_KEY or at top
            if (preg_match('/^(APP_DEBUG=.*)$/m', $content)) {
                $updatedContent = preg_replace('/^(APP_DEBUG=.*)$/m', "$1\n" . $targetLine, $content, 1);
            } elseif (preg_match('/^(APP_KEY=.*)$/m', $content)) {
                $updatedContent = preg_replace('/^(APP_KEY=.*)$/m', "$1\n" . $targetLine, $content, 1);
            } else {
                $updatedContent = $targetLine . "\n" . ltrim($content);
            }
        }

        if ($updatedContent === null) {
            return false;
        }

        return file_put_contents($envFilePath, $updatedContent, LOCK_EX) !== false;
    }
}
