<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\TemplateLibrary\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\TemplateRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\TemplateLibrary\Service\TemplateImportService;
use WP_REST_Request;
use WP_REST_Response;

class TemplateController extends AbstractController
{
    private TemplateRepository $repo;
    private TemplateImportService $importService;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->repo = $container->make(TemplateRepository::class);
        $this->importService = $container->make(TemplateImportService::class);
    }

    /** GET /npb/v1/templates — List all templates. */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $templates = $this->repo->findBy([], 'name', 'ASC');

        // Strip heavy data field for listing.
        $list = array_map(function ($t) {
            $item = (array) $t;
            unset($item['data']);
            return $item;
        }, $templates);

        return $this->success($list);
    }

    /** GET /npb/v1/templates/{slug} — Template detail. */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $t = $this->repo->findBySlug($request->get_param('slug'));
        if (!$t) return $this->notFound('Template not found.');
        return $this->success($t);
    }

    /** POST /npb/v1/templates/{slug}/import — Import template with business vars. */
    public function import(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $template = $this->repo->findBySlug($slug);
        if (!$template) return $this->notFound('Template not found.');

        $body = $this->getJsonBody($request) ?? [];
        $vars = $body['variables'] ?? [];

        $templateData = is_object($template->data)
            ? json_decode(wp_json_encode($template->data), true)
            : ($template->data ?? []);

        $counts = $this->importService->import($templateData, $vars);

        return $this->success([
            'message'  => "Template '{$template->name}' imported successfully.",
            'imported' => $counts,
        ]);
    }
}
