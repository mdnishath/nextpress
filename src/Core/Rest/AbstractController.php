<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Rest;

use NextPressBuilder\Core\Capability;
use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Sanitizer;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Base controller for all REST API endpoints.
 *
 * Provides standardized response helpers, permission callbacks,
 * and request parsing utilities that all module controllers extend.
 */
abstract class AbstractController
{
    protected Container $container;
    protected Sanitizer $sanitizer;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->sanitizer = $container->make(Sanitizer::class);
    }

    // ── Response Helpers ──────────────────────────────────────

    /**
     * Return a success response.
     */
    protected function success(mixed $data = null, int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data'    => $data,
        ], $status);
    }

    /**
     * Return a created response (201).
     */
    protected function created(mixed $data = null): WP_REST_Response
    {
        return $this->success($data, 201);
    }

    /**
     * Return an error response.
     *
     * @param array<string, string[]>|null $errors Field-level validation errors.
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): WP_REST_Response
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return new WP_REST_Response($body, $status);
    }

    /**
     * Return a 404 response.
     */
    protected function notFound(string $message = 'Resource not found.'): WP_REST_Response
    {
        return $this->error($message, 404);
    }

    /**
     * Return a 401 unauthorized response.
     */
    protected function unauthorized(string $message = 'Authentication required.'): WP_REST_Response
    {
        return $this->error($message, 401);
    }

    /**
     * Return a 403 forbidden response.
     */
    protected function forbidden(string $message = 'You do not have permission to perform this action.'): WP_REST_Response
    {
        return $this->error($message, 403);
    }

    /**
     * Return a paginated response.
     *
     * @param array{items: array, total: int, page: int, per_page: int, total_pages: int} $paginated
     */
    protected function paginated(array $paginated): WP_REST_Response
    {
        $response = new WP_REST_Response([
            'success' => true,
            'data'    => $paginated['items'],
            'meta'    => [
                'total'       => $paginated['total'],
                'page'        => $paginated['page'],
                'per_page'    => $paginated['per_page'],
                'total_pages' => $paginated['total_pages'],
            ],
        ], 200);

        $response->header('X-WP-Total', (string) $paginated['total']);
        $response->header('X-WP-TotalPages', (string) $paginated['total_pages']);

        return $response;
    }

    // ── Permission Callbacks ──────────────────────────────────

    /**
     * Public endpoint — no auth needed.
     */
    public function isPublic(): bool
    {
        return true;
    }

    /**
     * Requires npb_edit_pages capability.
     */
    public function canEditPages(): bool
    {
        return Capability::canEditPages();
    }

    /**
     * Requires npb_manage_themes capability.
     */
    public function canManageThemes(): bool
    {
        return Capability::canManageThemes();
    }

    /**
     * Requires npb_manage_forms capability.
     */
    public function canManageForms(): bool
    {
        return Capability::canManageForms();
    }

    /**
     * Requires npb_manage_settings capability.
     */
    public function canManageSettings(): bool
    {
        return Capability::canManageSettings();
    }

    /**
     * Requires npb_manage_templates capability.
     */
    public function canManageTemplates(): bool
    {
        return Capability::currentUserCan(Capability::MANAGE_TEMPLATES);
    }

    /**
     * Requires npb_manage_components capability.
     */
    public function canManageComponents(): bool
    {
        return Capability::currentUserCan(Capability::MANAGE_COMPONENTS);
    }

    /**
     * Requires npb_manage_navigation capability.
     */
    public function canManageNavigation(): bool
    {
        return Capability::currentUserCan(Capability::MANAGE_NAVIGATION);
    }

    /**
     * Requires npb_manage_seo capability.
     */
    public function canManageSeo(): bool
    {
        return Capability::currentUserCan(Capability::MANAGE_SEO);
    }

    // ── Request Helpers ───────────────────────────────────────

    /**
     * Get pagination params from request.
     *
     * @return array{page: int, per_page: int}
     */
    protected function getPagination(WP_REST_Request $request): array
    {
        return [
            'page'     => max(1, (int) ($request->get_param('page') ?? 1)),
            'per_page' => min(100, max(1, (int) ($request->get_param('per_page') ?? 20))),
        ];
    }

    /**
     * Get sort params from request.
     *
     * @param string[] $allowedColumns
     * @return array{order_by: string, order: string}
     */
    protected function getSorting(WP_REST_Request $request, array $allowedColumns = ['id'], string $defaultOrder = 'DESC'): array
    {
        $orderBy = $request->get_param('order_by') ?? 'id';
        $order = strtoupper($request->get_param('order') ?? $defaultOrder);

        if (!in_array($orderBy, $allowedColumns, true)) {
            $orderBy = 'id';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = $defaultOrder;
        }

        return ['order_by' => $orderBy, 'order' => $order];
    }

    /**
     * Get JSON body from request, returns null if invalid.
     *
     * @return array<string, mixed>|null
     */
    protected function getJsonBody(WP_REST_Request $request): ?array
    {
        $body = $request->get_json_params();
        return is_array($body) ? $body : null;
    }

    /**
     * Validate required fields exist in data.
     *
     * @param array<string, mixed> $data
     * @param string[] $required
     * @return string[] Missing field names.
     */
    protected function checkRequired(array $data, array $required): array
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}
