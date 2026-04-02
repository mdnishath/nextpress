<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\FormBuilder\Controller;

use NextPressBuilder\Core\Container;
use NextPressBuilder\Core\Repository\SubmissionRepository;
use NextPressBuilder\Core\Rest\AbstractController;
use WP_REST_Request;
use WP_REST_Response;

class SubmissionController extends AbstractController
{
    private SubmissionRepository $repo;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->repo = $container->make(SubmissionRepository::class);
    }

    /** GET /npb/v1/forms/{id}/submissions — List submissions (admin). */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $formId = (int) $request->get_param('id');
        $status = $request->get_param('status') ?? '';
        $pagination = $this->getPagination($request);

        $conditions = ['form_id' => $formId];
        if ($status) $conditions['status'] = $status;

        return $this->paginated(
            $this->repo->paginate($pagination['page'], $pagination['per_page'], $conditions, 'created_at', 'DESC')
        );
    }

    /** GET /npb/v1/forms/{id}/submissions/{sid} — Single submission (admin). */
    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $sub = $this->repo->find((int) $request->get_param('sid'));
        return $sub ? $this->success($sub) : $this->notFound('Submission not found.');
    }

    /** PUT /npb/v1/forms/{id}/submissions/{sid} — Update status (admin). */
    public function updateStatus(WP_REST_Request $request): WP_REST_Response
    {
        $sid = (int) $request->get_param('sid');
        $sub = $this->repo->find($sid);
        if (!$sub) return $this->notFound('Submission not found.');

        $data = $this->getJsonBody($request);
        $status = $data['status'] ?? '';
        $allowed = ['unread', 'read', 'starred', 'archived', 'spam'];
        if (!in_array($status, $allowed, true)) {
            return $this->error('Invalid status. Allowed: ' . implode(', ', $allowed));
        }

        $this->repo->markAs($sid, $status);
        return $this->success($this->repo->find($sid));
    }

    /** DELETE /npb/v1/forms/{id}/submissions/{sid} — Delete submission (admin). */
    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $sid = (int) $request->get_param('sid');
        if (!$this->repo->find($sid)) return $this->notFound('Submission not found.');
        $this->repo->delete($sid);
        return $this->success(['message' => 'Submission deleted.']);
    }

    /** GET /npb/v1/forms/{id}/submissions/export — CSV export (admin). */
    public function export(WP_REST_Request $request): WP_REST_Response
    {
        $formId = (int) $request->get_param('id');
        $submissions = $this->repo->findByForm($formId);

        if (empty($submissions)) {
            return $this->success(['csv' => '', 'count' => 0]);
        }

        // Build CSV.
        $allKeys = [];
        foreach ($submissions as $sub) {
            $data = is_object($sub->data) ? (array) $sub->data : ($sub->data ?? []);
            foreach (array_keys($data) as $k) {
                if (!in_array($k, $allKeys, true)) $allKeys[] = $k;
            }
        }

        $csv = implode(',', array_merge(['id', 'status', 'created_at'], $allKeys)) . "\n";

        foreach ($submissions as $sub) {
            $data = is_object($sub->data) ? (array) $sub->data : ($sub->data ?? []);
            $row = [$sub->id, $sub->status, $sub->created_at];
            foreach ($allKeys as $k) {
                $v = $data[$k] ?? '';
                $row[] = '"' . str_replace('"', '""', is_array($v) ? implode('; ', $v) : (string) $v) . '"';
            }
            $csv .= implode(',', $row) . "\n";
        }

        return $this->success(['csv' => $csv, 'count' => count($submissions)]);
    }
}
