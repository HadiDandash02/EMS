<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

/**
 * Behaviour behind <x-filter-bar> and <x-table-footer>:
 * "Search" applies the typed filters, "Clear" resets them, and the footer chooses rows per page.
 * A component using this trait must also use WithPagination and implement filterProperties().
 */
trait HasFilterBar
{
    #[Url(as: 'per', except: 10)]
    public int $perPage = 10;

    /**
     * Is the results table open? Every list shows its FILTERS first and keeps the table collapsed until the user
     * presses Search (or arrives with filters already in the URL). The table card has its own Collapse/Expand button.
     */
    public bool $searched = false;

    /** @return list<string> public properties that make up the filters */
    abstract protected function filterProperties(): array;

    /**
     * Multi-select filters hold a LIST of values (a single value still works). Returns clean positive ids.
     *
     * @return list<int>
     */
    protected function ids(mixed $value): array
    {
        return collect(Arr::wrap($value))->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
    }

    /**
     * Same for enum-backed filters: keeps only the values that really exist in the enum.
     *
     * @param  class-string<\BackedEnum>  $enum
     * @return list<string>
     */
    protected function enumValues(mixed $value, string $enum): array
    {
        return collect(Arr::wrap($value))->map(fn ($v) => $enum::tryFrom((string) $v)?->value)->filter()->unique()->values()->all();
    }

    /** Rows per page, restricted to the choices the footer offers (the value comes from the browser). */
    protected function rowsPerPage(): int
    {
        return in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
    }

    /** Livewire trait hook: arriving with filters in the URL (or a default filter) opens the table. */
    public function renderingHasFilterBar(): void
    {
        if (! $this->searched && collect($this->filterProperties())->contains(fn ($p) => filled($this->{$p}))) {
            $this->searched = true;
        }
    }

    public function applyFilters(): void
    {
        $this->searched = true;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset($this->filterProperties());   // rows per page is a display choice, it stays
        $this->searched = false;                  // back to: filters first, table closed
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        // Programmatic/URL changes must also go back to page 1.
        $property = Str::before($property, '.');   // "status.0" -> "status" (array filters)

        if ($property === 'perPage' || in_array($property, $this->filterProperties(), true)) {
            $this->resetPage();
        }

        if (in_array($property, $this->filterProperties(), true)) {
            $this->searched = true;
        }
    }
}
