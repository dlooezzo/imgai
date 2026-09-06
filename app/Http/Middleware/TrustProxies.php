<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustProxies
{
    /**
     * Trust all proxies (for shared hosting / cPanel / Cloudflare).
     * This ensures HTTPS detection and X-Forwarded-* headers work correctly.
     */
    protected array $proxies = ['*'];

    /**
     * Headers to trust from proxies.
     */
    protected int $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function handle(Request $request, Closure $next): mixed
    {
        // Apply trusted proxies so Laravel correctly detects HTTPS,
        // client IPs, and ports when running behind a proxy/CDN.
        $request->setTrustedProxies(
            $this->proxies,
            $this->headers
        );

        return $next($request);
    }
}
