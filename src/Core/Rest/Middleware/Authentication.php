<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Rest\Middleware;

use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * JWT authentication for external API consumers (Next.js server, etc.).
 *
 * Supports three auth methods:
 * 1. WordPress Nonce + Cookie (admin UI in wp-admin)
 * 2. WordPress Application Passwords (Basic Auth — Next.js server-to-server)
 * 3. JWT Bearer Token (external integrations)
 *
 * WordPress handles methods 1 and 2 natively. This class adds JWT support.
 */
class Authentication
{
    private const JWT_ALGO = 'HS256';
    private const JWT_EXPIRY = 3600; // 1 hour.
    private const REFRESH_EXPIRY = 604800; // 7 days.

    /**
     * Generate a JWT for a user.
     *
     * @return array{token: string, expires_at: int, refresh_token: string, refresh_expires_at: int}
     */
    public function generateTokens(WP_User $user): array
    {
        $now = time();

        $token = $this->encodeJwt([
            'sub'  => $user->ID,
            'name' => $user->display_name,
            'iat'  => $now,
            'exp'  => $now + self::JWT_EXPIRY,
            'type' => 'access',
        ]);

        $refreshToken = $this->encodeJwt([
            'sub'  => $user->ID,
            'iat'  => $now,
            'exp'  => $now + self::REFRESH_EXPIRY,
            'type' => 'refresh',
        ]);

        return [
            'token'              => $token,
            'expires_at'         => $now + self::JWT_EXPIRY,
            'refresh_token'      => $refreshToken,
            'refresh_expires_at' => $now + self::REFRESH_EXPIRY,
        ];
    }

    /**
     * Verify a JWT and return the user ID. Returns 0 on failure.
     */
    public function verifyToken(string $token): int
    {
        $payload = $this->decodeJwt($token);

        if ($payload === null) {
            return 0;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return 0; // Expired.
        }

        if (($payload['type'] ?? '') !== 'access') {
            return 0; // Not an access token.
        }

        return (int) ($payload['sub'] ?? 0);
    }

    /**
     * Refresh tokens using a refresh token.
     *
     * @return array{token: string, expires_at: int, refresh_token: string, refresh_expires_at: int}|null
     */
    public function refreshTokens(string $refreshToken): ?array
    {
        $payload = $this->decodeJwt($refreshToken);

        if ($payload === null) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null; // Expired.
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $userId = (int) ($payload['sub'] ?? 0);
        $user = get_user_by('ID', $userId);

        if (!$user) {
            return null;
        }

        return $this->generateTokens($user);
    }

    /**
     * Extract Bearer token from Authorization header.
     */
    public function extractBearerToken(WP_REST_Request $request): ?string
    {
        $authHeader = $request->get_header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authHeader, 7));
        return $token ?: null;
    }

    /**
     * WordPress filter to authenticate via JWT on REST API requests.
     * Hook this into 'determine_current_user'.
     */
    public function authenticateRequest(int|false $userId): int|false
    {
        // If already authenticated (nonce, cookie, app password), skip.
        if ($userId) {
            return $userId;
        }

        // Only process on REST API requests.
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $userId;
        }

        // Check for Bearer token.
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $userId;
        }

        $token = trim(substr($authHeader, 7));
        $jwtUserId = $this->verifyToken($token);

        return $jwtUserId ?: $userId;
    }

    // ── JWT Encoding/Decoding ─────────────────────────────────

    /**
     * Encode a JWT payload.
     *
     * @param array<string, mixed> $payload
     */
    private function encodeJwt(array $payload): string
    {
        $header = $this->base64UrlEncode(wp_json_encode(['alg' => self::JWT_ALGO, 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(wp_json_encode($payload));
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$body}", $this->getSecret(), true)
        );

        return "{$header}.{$body}.{$signature}";
    }

    /**
     * Decode and verify a JWT.
     *
     * @return array<string, mixed>|null
     */
    private function decodeJwt(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        // Verify signature.
        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$body}", $this->getSecret(), true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            return null; // Tampered.
        }

        $payload = json_decode($this->base64UrlDecode($body), true);
        return is_array($payload) ? $payload : null;
    }

    /**
     * Get the JWT secret key. Uses WordPress AUTH_KEY for simplicity.
     */
    private function getSecret(): string
    {
        // Use WordPress AUTH_KEY as the JWT secret. Always available.
        return defined('AUTH_KEY') ? AUTH_KEY : 'npb-default-secret-change-me';
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
