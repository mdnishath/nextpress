<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

class SubmissionRepository extends AbstractRepository
{
    protected function tableName(): string { return 'form_submissions'; }

    protected function sanitizeRules(): array
    {
        return [
            'ip_address' => 'text',
            'user_agent' => 'text',
            'referrer'   => 'url',
            'status'     => 'text',
        ];
    }

    protected function jsonColumns(): array
    {
        return ['data'];
    }

    /** @return object[] */
    public function findByForm(int $formId, string $status = ''): array
    {
        $conditions = ['form_id' => $formId];
        if ($status) {
            $conditions['status'] = $status;
        }
        return $this->findBy($conditions, 'created_at', 'DESC');
    }

    public function markAs(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function countUnread(int $formId): int
    {
        return $this->count(['form_id' => $formId, 'status' => 'unread']);
    }
}
