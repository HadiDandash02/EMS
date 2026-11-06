<?php

namespace App\Livewire\Concerns;

/**
 * Behaviour behind <x-filter-bar>: "Search" applies the typed filters, "Clear" resets them.
 * A component using this trait must also use WithPagination and implement filterProperties().
 */
trait HasFilterBar
{
    /** @return list<string> public properties that make up the filters */
    abstract protected function filterProperties(): array;

    public function search(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset($this->filterProperties());
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        // Programmatic/URL changes must also go back to page 1.
        if (in_array($property, $this->filterProperties(), true)) {
            $this->resetPage();
        }
    }
}
