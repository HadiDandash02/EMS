<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasFilterBar;
use App\Models\Translation;
use App\Support\Sql;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super admin: General Configurations > Translations. The catalogue stored in the database, one row per key:
 *   # | Key | English | Arabic | Actions (Edit / Delete, each on its own page)
 * Filters: Translated To English (yes / no), Translated To Arabic (yes / no), Type (code / keyword) and a search box.
 * Requires the "manage-translations" privilege (held by the super admin).
 */
class TranslationManager extends Component
{
    use HasFilterBar, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** the "Translated To English / Arabic" filters: ['yes'], ['no'] (both or none = no filter) */
    #[Url]
    public $inEnglish = [];

    #[Url]
    public $inArabic = [];

    /** 'code' / 'keyword' */
    #[Url]
    public $type = [];

    public function mount(): void
    {
        Gate::authorize('manage-translations');
    }

    protected function filterProperties(): array
    {
        return ['search', 'inEnglish', 'inArabic', 'type'];
    }

    /** yes / no pairs: choosing only one of them filters, choosing both (or none) means "no filter" */
    private function yesNo($value): ?string
    {
        $v = collect(is_array($value) ? $value : [$value])->intersect(['yes', 'no'])->unique()->values();

        return $v->count() === 1 ? $v->first() : null;
    }

    public function render()
    {
        Gate::authorize('manage-translations');

        $term = trim($this->search);
        $types = array_values(array_intersect(collect(is_array($this->type) ? $this->type : [$this->type])->all(), Translation::TYPES));

        $rows = Translation::query()
            // `key` is a reserved word in MySQL: the column is always written with its table name (translations.key)
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                Sql::whereContains($q, 'translations.key', $term);
                Sql::whereContains($q, 'translations.en', $term, 'or');
                Sql::whereContains($q, 'translations.ar', $term, 'or');
            }))
            ->when($this->yesNo($this->inEnglish) === 'yes', fn ($q) => $q->translated('en'))
            ->when($this->yesNo($this->inEnglish) === 'no', fn ($q) => $q->untranslated('en'))
            ->when($this->yesNo($this->inArabic) === 'yes', fn ($q) => $q->translated('ar'))
            ->when($this->yesNo($this->inArabic) === 'no', fn ($q) => $q->untranslated('ar'))
            ->when($types !== [], fn ($q) => $q->whereIn('translations.type', $types))
            ->orderBy('translations.key')
            ->paginate($this->rowsPerPage());

        return view('livewire.admin.translation-manager', ['rows' => $rows])
            ->layout('components.layouts.app', ['title' => 'Translations']);
    }
}