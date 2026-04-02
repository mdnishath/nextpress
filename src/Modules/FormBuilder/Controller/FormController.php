<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\FormBuilder\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\FormRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use NextPressBuilder\Modules\FormBuilder\Service\FormService;
use WP_REST_Request;
use WP_REST_Response;

class FormController extends AbstractController
{
    private FormRepository $repo;
    private FormService $service;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->repo = $container->make(FormRepository::class);
        $this->service = $container->make(FormService::class);
    }

    /** GET /npb/v1/forms/{slug} — Form config for frontend (public). */
    public function getBySlug(WP_REST_Request $request): WP_REST_Response
    {
        $form = $this->repo->findBySlug($request->get_param('slug'));
        if (!$form) return $this->notFound('Form not found.');

        // Public view: only return fields + display settings, not admin settings.
        $settings = is_object($form->settings) ? (array) $form->settings : [];
        return $this->success([
            'slug'          => $form->slug,
            'name'          => $form->name,
            'fields'        => $form->fields,
            'multi_step'    => $form->multi_step,
            'styling'       => $form->styling,
            'submit_button' => $settings['submit_button'] ?? 'Submit',
            'success_message' => $settings['success_message'] ?? 'Thank you!',
        ]);
    }

    /** POST /npb/v1/forms/{slug}/submit — Submit form (public, rate limited). */
    public function submit(WP_REST_Request $request): WP_REST_Response
    {
        $slug = $request->get_param('slug');
        $data = $request->get_json_params();
        if (!is_array($data)) $data = $request->get_body_params();

        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ip = explode(',', $ip)[0];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ref = $_SERVER['HTTP_REFERER'] ?? '';

        $result = $this->service->handleSubmission($slug, $data, trim($ip), $ua, $ref);

        if ($result['success']) {
            return $this->success(['message' => $result['message']], 200);
        }

        $status = str_contains($result['message'] ?? '', 'Too many') ? 429 : 400;
        return $this->error($result['message'], $status, $result['errors'] ?? null);
    }

    /** GET /npb/v1/forms — List all forms (admin). */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $forms = $this->repo->findBy([], 'name', 'ASC');
        return $this->success($forms);
    }

    /** POST /npb/v1/forms — Create form (admin). */
    public function store(WP_REST_Request $request): WP_REST_Response
    {
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        $missing = $this->checkRequired($data, ['name', 'slug', 'fields', 'settings']);
        if ($missing) return $this->error('Missing: ' . implode(', ', $missing));
        if ($this->repo->findBySlug($data['slug'])) return $this->error('Slug already exists.');
        $id = $this->repo->create($data);
        return $this->created($this->repo->find($id));
    }

    /** PUT /npb/v1/forms/{id} — Update form (admin). */
    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->repo->find($id)) return $this->notFound('Form not found.');
        $data = $this->getJsonBody($request);
        if (!$data) return $this->error('Invalid JSON body.');
        unset($data['id'], $data['slug']);
        $this->repo->update($id, $data);
        return $this->success($this->repo->find($id));
    }

    /** DELETE /npb/v1/forms/{id} — Delete form (admin). */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        if (!$this->repo->find($id)) return $this->notFound('Form not found.');
        $this->repo->delete($id);
        return $this->success(['message' => 'Form deleted.']);
    }
}
