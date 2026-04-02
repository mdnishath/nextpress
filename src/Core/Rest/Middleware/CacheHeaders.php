<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Rest\Middleware;

use WP_REST_Response;

/**
 * Applies appropriate Cache-Control headers to REST API responses.
 *
 * Tiers:
 * - 'public'    → Cache-Control: public, max-age=60, s-maxage=300
 * - 'long'      → Cache-Control: public, max-age=300, s-maxage=3600
 * - 'private'   → Cache-Control: no-store (admin endpoints)
 */
class CacheHeaders
{
    /**
     * Apply cache headers to a response based on tier.
     *
     * @param string $tier 'public' | 'long' | 'private'
     */
    public function apply(WP_REST_Response $response, string $tier = 'public'): WP_REST_Response
    {
        $header = match ($tier) {
            'long'    => 'public, max-age=300, s-maxage=3600',
            'private' => 'no-store, no-cache, must-revalidate, max-age=0',
            default   => 'public, max-age=60, s-maxage=300',
        };

        $response->header('Cache-Control', $header);

        if ($tier !== 'private') {
            $response->header('Vary', 'Accept-Encoding');
        }

        return $response;
    }

    /**
     * Add an ETag header based on response data.
     * Enables 304 Not Modified for unchanged content.
     */
    public function addETag(WP_REST_Response $response): WP_REST_Response
    {
        $data = $response->get_data();
        $etag = '"' . md5(wp_json_encode($data)) . '"';

        $response->header('ETag', $etag);

        // Check If-None-Match from client.
        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($clientEtag && $clientEtag === $etag) {
            $response->set_status(304);
            $response->set_data(null);
        }

        return $response;
    }

    /**
     * Apply public caching + ETag in one call.
     */
    public function publicWithETag(WP_REST_Response $response): WP_REST_Response
    {
        $this->apply($response, 'public');
        return $this->addETag($response);
    }

    /**
     * Apply long caching + ETag (for theme, component data).
     */
    public function longWithETag(WP_REST_Response $response): WP_REST_Response
    {
        $this->apply($response, 'long');
        return $this->addETag($response);
    }
}
