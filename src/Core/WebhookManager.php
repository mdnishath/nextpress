<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Manages outgoing webhooks (Next.js revalidation, etc.).
 *
 * Queue-based delivery with retry logic and logging.
 */
class WebhookManager
{
    public function __construct(
        private readonly SettingsManager $settings,
    ) {}

    /**
     * Send a webhook to the configured Next.js revalidation endpoint.
     *
     * @param string[] $paths   Affected frontend paths to revalidate.
     * @param string   $reason  Why revalidation was triggered.
     */
    public function triggerRevalidation(array $paths, string $reason = 'content_updated'): bool
    {
        $url = $this->settings->getString( 'nextjs_revalidation_url' );
        $secret = $this->settings->getString( 'nextjs_revalidation_secret' );

        if ( empty( $url ) || empty( $secret ) ) {
            return false;
        }

        $payload = [
            'secret'    => $secret,
            'paths'     => $paths,
            'reason'    => $reason,
            'timestamp' => time(),
        ];

        return $this->send( $url, $payload );
    }

    /**
     * Send a webhook POST request.
     *
     * @param array<string, mixed> $payload
     */
    public function send(string $url, array $payload, int $attempt = 1): bool
    {
        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent'   => 'NextPressBuilder/' . NPB_VERSION,
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            $this->logDelivery( $url, $payload, false, $response->get_error_message(), $attempt );

            // Retry with exponential backoff (1s, 5s, 30s).
            if ( $attempt < 3 ) {
                $delays = [ 1 => 1, 2 => 5 ];
                $delay = $delays[ $attempt ] ?? 5;

                wp_schedule_single_event(
                    time() + $delay,
                    'npb_webhook_retry',
                    [ $url, $payload, $attempt + 1 ]
                );
            }

            return false;
        }

        $statusCode = wp_remote_retrieve_response_code( $response );
        $success = $statusCode >= 200 && $statusCode < 300;

        $this->logDelivery( $url, $payload, $success, "HTTP {$statusCode}", $attempt );

        return $success;
    }

    /**
     * Log a webhook delivery attempt.
     *
     * @param array<string, mixed> $payload
     */
    private function logDelivery(string $url, array $payload, bool $success, string $message, int $attempt): void
    {
        $log = $this->settings->getArray( 'webhook_log', [] );

        // Keep only last 50 entries.
        if ( count( $log ) >= 50 ) {
            $log = array_slice( $log, -49 );
        }

        $log[] = [
            'url'       => $url,
            'paths'     => $payload['paths'] ?? [],
            'reason'    => $payload['reason'] ?? '',
            'success'   => $success,
            'message'   => $message,
            'attempt'   => $attempt,
            'timestamp' => gmdate( 'Y-m-d H:i:s' ),
        ];

        $this->settings->set( 'webhook_log', $log );
    }

    /**
     * Get the webhook delivery log.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLog(): array
    {
        return $this->settings->getArray( 'webhook_log', [] );
    }

    /**
     * Clear the webhook log.
     */
    public function clearLog(): void
    {
        $this->settings->set( 'webhook_log', [] );
    }

    /**
     * Register the retry cron hook.
     */
    public function registerHooks(HookManager $hooks): void
    {
        $hooks->addAction( 'npb_webhook_retry', [ $this, 'handleRetry' ], 10, 3 );
    }

    /**
     * Handle a retry attempt from the cron scheduler.
     *
     * @param array<string, mixed> $payload
     */
    public function handleRetry(string $url, array $payload, int $attempt): void
    {
        $this->send( $url, $payload, $attempt );
    }
}
