<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\FormBuilder\Service;

/**
 * Layered spam protection for form submissions.
 *
 * 5 layers: honeypot, time-based, rate limiting, reCAPTCHA v3, Cloudflare Turnstile.
 * Each layer is independently configurable per form.
 */
class SpamProtectionService
{
    /**
     * Run all enabled spam checks. Returns null if clean, error message if spam.
     *
     * @param array<string, mixed> $data      Submitted form data.
     * @param array<string, mixed> $settings  Form spam protection settings.
     * @param string               $ip        Client IP address.
     */
    public function check(array $data, array $settings, string $ip): ?string
    {
        // 1. Honeypot (always active).
        if ($this->checkHoneypot($data)) {
            return 'spam_detected';
        }

        // 2. Time-based.
        $minTime = $settings['min_submit_time'] ?? 3;
        if ($this->checkTimeBased($data, $minTime)) {
            return 'Form submitted too quickly. Please try again.';
        }

        // 3. Rate limiting (IP-based).
        $maxPerHour = $settings['max_submissions_per_hour'] ?? 5;
        $formSlug = $settings['form_slug'] ?? 'default';
        if ($this->checkRateLimit($ip, $formSlug, $maxPerHour)) {
            return 'Too many submissions. Please try again later.';
        }

        return null; // Clean.
    }

    /**
     * Honeypot: hidden field that should be empty.
     */
    private function checkHoneypot(array $data): bool
    {
        // The frontend renders a hidden field named 'npb_hp_field'.
        // Bots fill it in. Humans don't see it.
        return !empty($data['npb_hp_field'] ?? '');
    }

    /**
     * Time-based: reject submissions under N seconds.
     */
    private function checkTimeBased(array $data, int $minSeconds): bool
    {
        $timestamp = (int) ($data['npb_form_timestamp'] ?? 0);
        if ($timestamp === 0) {
            return false; // No timestamp, skip check.
        }
        return (time() - $timestamp) < $minSeconds;
    }

    /**
     * Rate limiting: max N submissions per IP per form per hour.
     */
    private function checkRateLimit(string $ip, string $formSlug, int $maxPerHour): bool
    {
        $key = 'npb_form_rl_' . md5($ip . $formSlug);
        $data = get_transient($key);

        if ($data === false) {
            set_transient($key, ['count' => 1, 'start' => time()], 3600);
            return false;
        }

        $data = (array) $data;
        $count = ($data['count'] ?? 0) + 1;

        if ($count > $maxPerHour) {
            return true; // Rate limited.
        }

        $data['count'] = $count;
        $elapsed = time() - ($data['start'] ?? time());
        set_transient($key, $data, max(1, 3600 - $elapsed));

        return false;
    }

    /**
     * Verify reCAPTCHA v3 token (if configured).
     *
     * @return bool True if valid or not configured.
     */
    public function verifyRecaptcha(string $token, string $secretKey, float $threshold = 0.5): bool
    {
        if (empty($token) || empty($secretKey)) {
            return true; // Not configured, skip.
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => ['secret' => $secretKey, 'response' => $token],
        ]);

        if (is_wp_error($response)) {
            return true; // Network error, don't block.
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return ($result['success'] ?? false) && ($result['score'] ?? 0) >= $threshold;
    }

    /**
     * Verify Cloudflare Turnstile token (if configured).
     */
    public function verifyTurnstile(string $token, string $secretKey): bool
    {
        if (empty($token) || empty($secretKey)) {
            return true;
        }

        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => ['secret' => $secretKey, 'response' => $token],
        ]);

        if (is_wp_error($response)) {
            return true;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return $result['success'] ?? false;
    }
}
