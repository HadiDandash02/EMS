<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Click-to-sort column headers and "Export" (CSV) for a Livewire table.
 *
 * The component declares which fields may be sorted (a whitelist - the field name comes from the
 * browser) and how to sort them; the export reuses the SAME filtered + sorted query, so the file
 * contains exactly what the user is looking at (all pages), and never more than they may see.
 */
trait SortsAndExports
{
    #[Url(as: 'sort', except: '')]
    public string $sortField = '';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    /** @return list<string> fields the headers may sort by */
    abstract protected function sortableFields(): array;

    /** The filtered and sorted query WITHOUT pagination (used by the export). */
    abstract protected function exportQuery(): Builder;

    /** @return array<string, callable(mixed): (string|int|float|null)> translated header => value extractor */
    abstract protected function exportColumns(): array;

    abstract protected function exportFilename(): string;

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableFields(), true)) {
            return;                                   // unknown field from the browser: ignore
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /** @return array{0: ?string, 1: 'asc'|'desc'} */
    protected function activeSort(): array
    {
        $field = in_array($this->sortField, $this->sortableFields(), true) ? $this->sortField : null;

        return [$field, $this->sortDirection === 'desc' ? 'desc' : 'asc'];
    }

    /** ORDER BY CASE col WHEN 'a' THEN 0 ... so enums sort by their logical order, not alphabetically. */
    protected function orderByList(Builder $query, string $column, array $orderedValues, string $direction): Builder
    {
        $cases = collect($orderedValues)->values()->map(fn ($v, $i) => "WHEN '".str_replace("'", '', $v)."' THEN {$i}")->implode(' ');

        return $query->orderByRaw("CASE {$column} {$cases} ELSE 999 END {$direction}");
    }

    public function exportCsv(): StreamedResponse
    {
        $columns = $this->exportColumns();
        $query = $this->exportQuery();

        return response()->streamDownload(function () use ($columns, $query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                       // UTF-8 BOM so Excel shows Arabic correctly
            fputcsv($out, array_keys($columns));

            foreach ($query->limit(10000)->get() as $row) {
                fputcsv($out, array_map(fn ($extract) => $this->csvSafe($extract($row)), $columns));
            }
            fclose($out);
        }, $this->exportFilename().'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stop spreadsheet "formula injection": a cell starting with = + - @ would be run as a formula. */
    private function csvSafe(mixed $value): string|int|float
    {
        $value = $value ?? '';

        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
