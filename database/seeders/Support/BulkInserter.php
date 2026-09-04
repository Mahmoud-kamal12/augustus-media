<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\DB;

final class BulkInserter
{
    private array $rows = [];

    public function __construct(
        private string $table,
        private int $chunkSize,
        private bool $ignoreDuplicates = false,
    ) {}

    public function add(array $row): void
    {
        $this->rows[] = $row;
    }

    public function flushIfFull(int $remaining = PHP_INT_MAX): int
    {
        $limit = max(1, min($this->chunkSize, $remaining));

        return count($this->rows) >= $limit ? $this->flush() : 0;
    }

    public function flush(): int
    {
        if ($this->rows === []) {
            return 0;
        }

        $rows = $this->rows;
        $this->rows = [];

        if ($this->ignoreDuplicates) {
            return DB::table($this->table)->insertOrIgnore($rows);
        }

        DB::table($this->table)->insert($rows);

        return count($rows);
    }
}
