<?php

declare(strict_types=1);

namespace NextPressBuilder\Core\Repository;

use NextPressBuilder\Core\Sanitizer;

/**
 * Base repository providing CRUD operations on custom tables.
 *
 * All data access goes through repositories — no direct $wpdb queries in modules.
 * Handles sanitization, pagination, filtering, and JSON column helpers.
 */
abstract class AbstractRepository
{
    protected \wpdb $wpdb;
    protected string $table;
    protected string $primaryKey = 'id';
    protected Sanitizer $sanitizer;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'npb_' . $this->tableName();
        $this->sanitizer = new Sanitizer();
    }

    /**
     * The table name without prefix (e.g., 'pages', 'sections').
     */
    abstract protected function tableName(): string;

    /**
     * Define sanitization rules: ['column' => 'sanitizer_method'].
     *
     * @return array<string, string>
     */
    abstract protected function sanitizeRules(): array;

    /**
     * Define which columns contain JSON data.
     *
     * @return string[]
     */
    protected function jsonColumns(): array
    {
        return [];
    }

    // ── CRUD ──────────────────────────────────────────────────

    /**
     * Find a record by primary key.
     */
    public function find(int $id): ?object
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = %d",
                $id
            )
        );

        return $row ? $this->decodeJsonColumns($row) : null;
    }

    /**
     * Find records matching conditions.
     *
     * @param array<string, mixed> $conditions ['column' => value]
     * @return object[]
     */
    public function findBy(
        array $conditions = [],
        string $orderBy = 'id',
        string $order = 'ASC',
        ?int $limit = null,
    ): array {
        $where = $this->buildWhere($conditions);
        $orderBy = sanitize_sql_orderby("{$orderBy} {$order}") ?: 'id ASC';

        $sql = "SELECT * FROM {$this->table}";
        if ($where['clause']) {
            $sql .= " WHERE {$where['clause']}";
        }
        $sql .= " ORDER BY {$orderBy}";
        if ($limit !== null) {
            $sql .= $this->wpdb->prepare(' LIMIT %d', $limit);
        }

        $query = $where['values']
            ? $this->wpdb->prepare($sql, ...$where['values'])
            : $sql;

        $rows = $this->wpdb->get_results($query);

        return array_map(fn($row) => $this->decodeJsonColumns($row), $rows ?: []);
    }

    /**
     * Find one record matching conditions.
     *
     * @param array<string, mixed> $conditions
     */
    public function findOne(array $conditions): ?object
    {
        $results = $this->findBy($conditions, 'id', 'ASC', 1);
        return $results[0] ?? null;
    }

    /**
     * Find a record by slug.
     */
    public function findBySlug(string $slug): ?object
    {
        return $this->findOne(['slug' => $slug]);
    }

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $data
     * @return int The inserted ID.
     */
    public function create(array $data): int
    {
        $data = $this->sanitizeData($data);
        $data = $this->encodeJsonColumns($data);

        $this->wpdb->insert($this->table, $data);

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Update a record by primary key.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->sanitizeData($data);
        $data = $this->encodeJsonColumns($data);

        $result = $this->wpdb->update(
            $this->table,
            $data,
            [$this->primaryKey => $id]
        );

        return $result !== false;
    }

    /**
     * Delete a record by primary key.
     */
    public function delete(int $id): bool
    {
        $result = $this->wpdb->delete(
            $this->table,
            [$this->primaryKey => $id],
            ['%d']
        );

        return $result !== false;
    }

    // ── Query Helpers ─────────────────────────────────────────

    /**
     * Paginate results.
     *
     * @param array<string, mixed> $conditions
     * @return array{items: object[], total: int, page: int, per_page: int, total_pages: int}
     */
    public function paginate(
        int $page = 1,
        int $perPage = 20,
        array $conditions = [],
        string $orderBy = 'id',
        string $order = 'DESC',
    ): array {
        $total = $this->count($conditions);
        $totalPages = (int) ceil($total / max($perPage, 1));
        $page = max(1, min($page, max($totalPages, 1)));
        $offset = ($page - 1) * $perPage;

        $where = $this->buildWhere($conditions);
        $orderBy = sanitize_sql_orderby("{$orderBy} {$order}") ?: 'id DESC';

        $sql = "SELECT * FROM {$this->table}";
        if ($where['clause']) {
            $sql .= " WHERE {$where['clause']}";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT %d OFFSET %d";

        $values = array_merge($where['values'], [$perPage, $offset]);
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, ...$values)
        );

        return [
            'items'       => array_map(fn($row) => $this->decodeJsonColumns($row), $rows ?: []),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Count records matching conditions.
     *
     * @param array<string, mixed> $conditions
     */
    public function count(array $conditions = []): int
    {
        $where = $this->buildWhere($conditions);

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($where['clause']) {
            $sql .= " WHERE {$where['clause']}";
        }

        $query = $where['values']
            ? $this->wpdb->prepare($sql, ...$where['values'])
            : $sql;

        return (int) $this->wpdb->get_var($query);
    }

    /**
     * Check if a record exists matching conditions.
     *
     * @param array<string, mixed> $conditions
     */
    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }

    // ── JSON Helpers ──────────────────────────────────────────

    /**
     * Encode JSON columns for storage.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function encodeJsonColumns(array $data): array
    {
        foreach ($this->jsonColumns() as $col) {
            if (isset($data[$col]) && (is_array($data[$col]) || is_object($data[$col]))) {
                $data[$col] = wp_json_encode($data[$col]);
            }
        }
        return $data;
    }

    /**
     * Decode JSON columns after retrieval.
     */
    private function decodeJsonColumns(object $row): object
    {
        foreach ($this->jsonColumns() as $col) {
            if (isset($row->{$col}) && is_string($row->{$col})) {
                $decoded = json_decode($row->{$col}, false);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row->{$col} = $decoded;
                }
            }
        }
        return $row;
    }

    // ── Internal Helpers ──────────────────────────────────────

    /**
     * Build a WHERE clause from conditions.
     *
     * @param array<string, mixed> $conditions
     * @return array{clause: string, values: array<mixed>}
     */
    private function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['clause' => '', 'values' => []];
        }

        $parts = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $parts[] = "{$column} IS NULL";
            } elseif (is_int($value)) {
                $parts[] = "{$column} = %d";
                $values[] = $value;
            } elseif (is_float($value)) {
                $parts[] = "{$column} = %f";
                $values[] = $value;
            } else {
                $parts[] = "{$column} = %s";
                $values[] = (string) $value;
            }
        }

        return [
            'clause' => implode(' AND ', $parts),
            'values' => $values,
        ];
    }

    /**
     * Sanitize data using the defined rules.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeData(array $data): array
    {
        $rules = $this->sanitizeRules();

        foreach ($data as $key => $value) {
            if (isset($rules[$key]) && $value !== null && !is_array($value) && !is_object($value)) {
                $method = $rules[$key];
                if (method_exists($this->sanitizer, $method)) {
                    $data[$key] = $this->sanitizer->{$method}($value);
                }
            }
        }

        return $data;
    }

    /**
     * Get the full table name.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the last database error.
     */
    public function lastError(): string
    {
        return $this->wpdb->last_error;
    }
}
