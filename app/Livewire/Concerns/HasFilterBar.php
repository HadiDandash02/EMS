<?php

namespace App\Livewire\Concerns;

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

    /** @return list<string> public properties that make up the filters */
    abstract protected function filterProperties(): array;

    /** Rows per page, restricted to the choices the footer offers (the value comes from the browser). */
    protected function rowsPerPage(): int
    {
        return in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 10;
    }

    public function search(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset($this->filterProperties());   // rows per page is a display choice, it stays
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        // Programmatic/URL changes must also go back to page 1.
        if ($property === 'perPage' || in_array($property, $this->filterProperties(), true)) {
            $this->resetPage();
        }
    }
}
