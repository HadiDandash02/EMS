<?php

namespace App\Livewire\Admin;

use App\Console\Commands\ScanTranslations;
use App\Livewire\Concerns\HasFilterBar;
use App\Models\Translation;
use App\Support\MachineTranslator;
use App\Support\Sql;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super admin: the interface translations (Arabic) stored in the database.
 *  - edit a translation and Save (the whole system uses it at once),
 *  - "Auto-translate" machine-translates the texts that are still empty (marked "machine" for review),
 *  - "Scan the code" adds the new texts that developers used in the code.
 */
class TranslationManager extends Component
{
    use HasFilterBar, WithPagination;

    private const LOCALE = 'ar';

    /** how many empty texts one click of "Auto-translate" handles (keeps the page fast) */
    private const BATCH = 15;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** all | untranslated | machine */
    #[Url]
    public $show = [];

    /** the text typed in each row: id => translation */
    public array $edits = [];

    public function mount(): void
    {
        Gate::authorize('manage-users');
        $this->searched = true;      // a catalogue is useful at once: show the table without pressing Search
    }

    protected function filterProperties(): array
    {
        return ['search', 'show'];
    }

    public function save(int $id): void
    {
        Gate::authorize('manage-users');

        $row = Translation::where('locale', self::LOCALE)->findOrFail($id);
        $value = trim((string) ($this->edits[$id] ?? ''));

        $this->resetErrorBag("edits.$id");

        if (mb_strlen($value) > 2000) {
            $this->addError("edits.$id", __('Use at most :max characters.', ['max' => 2000]));

            return;
        }

        // :name, :count ... in the English text must survive in the translation (the code fills them in)
        preg_match_all('/:[a-z_]+/i', $row->key, $needed);
        foreach ($needed[0] as $placeholder) {
            if ($value !== '' && ! str_contains($value, $placeholder)) {
                $this->addError("edits.$id", __('Keep the placeholder :placeholder from the English text.', ['placeholder' => $placeholder]));

                return;
            }
        }

        $row->update(['value' => $value === '' ? null : $value, 'machine' => false]);   // a person saved it: no longer a machine text
        $this->edits[$id] = $value;

        $this->dispatch('notify', type: 'success', message: __('Translation saved.'));
    }

    /** Fills the box of ONE row with a machine suggestion; nothing is saved until the admin presses Save. */
    public function suggest(int $id, MachineTranslator $translator): void
    {
        Gate::authorize('manage-users');

        $row = Translation::where('locale', self::LOCALE)->findOrFail($id);

        if ($text = $translator->translate($row->key, self::LOCALE)) {
            $this->edits[$id] = $text;
            $this->dispatch('notify', type: 'success', message: __('Suggestion added - check it and press Save.'));
        } else {
            $this->dispatch('notify', type: 'error', message: __('The translation service did not answer. Try again later.'));
        }
    }

    /** Machine-translates the next batch of empty texts and saves them marked as "machine". */
    public function translateMissing(MachineTranslator $translator): void
    {
        Gate::authorize('manage-users');

        $rows = Translation::where('locale', self::LOCALE)->where('group', '*')
            ->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))
            ->limit(self::BATCH)->get();

        if ($rows->isEmpty()) {
            $this->dispatch('notify', type: 'success', message: __('Every text is already translated.'));

            return;
        }

        $done = 0;
        foreach ($rows as $row) {
            if ($text = $translator->translate($row->key, self::LOCALE)) {
                $row->update(['value' => $text, 'machine' => true]);
                $this->edits[$row->id] = $text;
                $done++;
            }
        }

        $this->dispatch('notify', type: $done > 0 ? 'success' : 'error', message: $done > 0
            ? __(':done of :total texts were translated automatically. Please review them (marked "machine").', ['done' => $done, 'total' => $rows->count()])
            : __('The translation service did not answer. Try again later.'));
    }

    /** Adds the texts used in the code that are not in the table yet. */
    public function scanCode(): void
    {
        Gate::authorize('manage-users');

        $existing = Translation::where('locale', self::LOCALE)->where('group', '*')->pluck('key_hash')->flip();
        $added = 0;

        foreach (ScanTranslations::keysInCode() as $key => $file) {
            if (! $existing->has(sha1($key))) {
                Translation::create(['locale' => self::LOCALE, 'group' => '*', 'key' => $key, 'value' => null]);
                $added++;
            }
        }

        $this->dispatch('notify', type: 'success', message: $added === 0
            ? __('Every text used in the code is already in the list.')
            : __(':count new text(s) were found in the code.', ['count' => $added]));
    }

    public function render()
    {
        Gate::authorize('manage-users');

        $term = trim($this->search);
        $show = collect(is_array($this->show) ? $this->show : [$this->show])->filter()->all();

        $rows = Translation::query()->where('locale', self::LOCALE)->where('group', '*')
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                Sql::whereContains($q, 'key', $term);
                Sql::whereContains($q, 'value', $term, 'or');
            }))
            ->when(in_array('untranslated', $show, true) && ! in_array('machine', $show, true), fn ($q) => $q->where(fn ($q) => $q->whereNull('value')->orWhere('value', '')))
            ->when(in_array('machine', $show, true) && ! in_array('untranslated', $show, true), fn ($q) => $q->where('machine', true))
            ->orderByRaw("case when value is null or value = '' then 0 else 1 end")   // untranslated first
            ->orderBy('key')
            ->paginate($this->rowsPerPage());

        foreach ($rows as $row) {
            $this->edits[$row->id] ??= (string) $row->value;
        }

        return view('livewire.admin.translation-manager', [
            'rows' => $rows,
            'counts' => [
                'all' => Translation::where('locale', self::LOCALE)->where('group', '*')->count(),
                'missing' => Translation::where('locale', self::LOCALE)->where('group', '*')->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))->count(),
                'machine' => Translation::where('locale', self::LOCALE)->where('group', '*')->where('machine', true)->count(),
            ],
        ])->layout('components.layouts.app', ['title' => 'Translations']);
    }
}
