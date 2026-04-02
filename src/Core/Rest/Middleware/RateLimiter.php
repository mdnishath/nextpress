<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Rest\Middleware;

use NextPressBuilder\Core\SettingsManager;
use WP_REST_Request;
use WP_REST_Response;

/**
 * IP-based rate limiting for REST API endpoints.
 *
 * Uses WordPress transients for storage (works without Redis/Memcached).
 * Three tiers: public, forms, admin — each configurable via settings.
 */
class RateLimiter
{
    private SettingsManager $settings;

    /** @var array{limit: int, remaining: int, reset: int} Last check result. */
    private array $lastResult = ['limit' => 0, 'remaining' => 0, 'reset' => 0];

    public function __construct(SettingsManager $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Check if the request is within rate limits.
     *
     * @param string $tier 'public' | 'forms' | 'admin'
     * @return bool True if allowed, false if rate limited.
     */
    public function check(WP_REST_Request $request, string $tier = 'public'): bool
    {
        $key = $this->buildKey($request, $tier);
        $limit = $this->getLimit($tier);
        $window = 60; // 1 minute window.

        $transientKey = 'npb_rl_' . md5($key);
        $data = get_transient($transientKey);

        if ($data === false) {
            // First request in window.
            $data = ['count' => 1, 'start' => time()];
            set_transient($transientKey, $data, $window);

            $this->lastResult = [
                'limit'     => $limit,
                'remaining' => $limit - 1,
                'reset'     => time() + $window,
            ];

            return true;
        }

        $data = (array) $data;
        $elapsed = time() - ($data['start'] ?? time());

        if ($elapsed >= $window) {
            // Window expired, reset.
            $data = ['count' => 1, 'start' => time()];
            set_transient($transientKey, $data, $window);

            $this->lastResult = [
                'limit'     => $limit,
                'remaining' => $limit - 1,
                'reset'     => time() + $window,
            ];

            return true;
        }

        $count = ($data['count'] ?? 0) + 1;
        $remaining = max(0, $limit - $count);
        $reset = ($data['start'] ?? time()) + $window;

        $this->lastResult = [
            'limit'     => $limit,
            'remaining' => $remaining,
            'reset'     => $reset,
        ];

        if ($count > $limit) {
            return false; // Rate limited.
        }

        // Increment counter.
        $data['count'] = $count;
        set_transient($transientKey, $data, $window - $elapsed);

        return true;
    }

    /**
     * Get rate limit headers to attach to any response.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [
            'X-RateLimit-Limit'     => (string) $this->lastResult['limit'],
            'X-RateLimit-Remaining' => (string) $this->lastResult['remaining'],
            'X-RateLimit-Reset'     => (string) $this->lastResult['reset'],
        ];
    }

    /**
     * Apply rate limit headers to a response.
     */
    public function applyHeaders(WP_REST_Response $response): WP_REST_Response
    {
        foreach ($this->getHeaders() as $key => $value) {
            $response->header($key, $value);
        }
        return $response;
    }

    /**
     * Create a 429 Too Many Requests response.
     */
    public function tooManyRequests(): WP_REST_Response
    {
        $retryAfter = max(1, $this->lastResult['reset'] - time());

        $response = new WP_REST_Response([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
        ], 429);

        $response->header('Retry-After', (string) $retryAfter);
        $this->applyHeaders($response);

        return $response;
    }

    /**
     * Build the rate limit key (unique per IP + tier, or per user for admin).
     */
    private function buildKey(WP_REST_Request $request, string $tier): string
    {
        if ($tier === 'admin') {
            $userId = get_current_user_id();
            return "admin:{$userId}";
        }

        $ip = $this->getClientIp();

        if ($tier === 'forms') {
            // Per-IP per-form rate limiting.
            $route = $request->get_route();
            return "forms:{$ip}:{$route}";
        }

        return "public:{$ip}";
    }

    /**
     * Get the configured limit for a tier.
     */
    private function getLimit(string $tier): int
    {
        return match ($tier) {
            'admin' => $this->settings->getInt('rate_limit_admin', 120),
            'forms' => $this->settings->getInt('rate_limit_forms', 5),
            default => $this->settings->getInt('rate_limit_public', 60),
        };
    }

    /**
     * Get client IP address, accounting for proxies.
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            $ip = $_SERVER[$header] ?? '';
            if ($ip) {
                // X-Forwarded-For can contain multiple IPs.
                $ip = explode(',', $ip)[0];
                $ip = trim($ip);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }
}
