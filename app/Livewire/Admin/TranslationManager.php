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
 * Super admin: the translation catalogue (General Configurations > Translations). One row per key with its
 * English and Arabic text, stored in the database:
 *   Key | English | Arabic | Actions     filters: translated to English / Arabic (yes / no), type (code / keyword), search
 *  - Save writes both texts, Suggest asks the translation service for an Arabic suggestion,
 *  - "Auto-translate" fills the next empty Arabic texts (marked "machine" for review),
 *  - "Scan the code" adds the new keys used in the code, "Add keyword" creates a key by hand.
 */
class TranslationManager extends Component
{
    use HasFilterBar, WithPagination;

    /** how many empty texts one click of "Auto-translate" handles (keeps the page fast) */
    private const BATCH = 15;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** the "Translated to English / Arabic" filters: ['yes'], ['no'] (both or none = no filter) */
    #[Url]
    public $inEnglish = [];

    #[Url]
    public $inArabic = [];

    /** 'code' / 'keyword' */
    #[Url]
    public $type = [];

    /** the texts typed in each row: id => ['en' => ..., 'ar' => ...] */
    public array $edits = [];

    /** the "Add keyword" form */
    public string $newKey = '';

    public string $newEn = '';

    public string $newAr = '';

    public bool $adding = false;

    public function mount(): void
    {
        Gate::authorize('manage-users');
        $this->searched = true;      // a catalogue is useful at once: show the table without pressing Search
    }

    protected function filterProperties(): array
    {
        return ['search', 'inEnglish', 'inArabic', 'type'];
    }

    public function save(int $id): void
    {
        Gate::authorize('manage-users');

        $row = Translation::findOrFail($id);
        $en = trim((string) ($this->edits[$id]['en'] ?? ''));
        $ar = trim((string) ($this->edits[$id]['ar'] ?? ''));

        $this->resetErrorBag("edits.$id");

        foreach (['en' => $en, 'ar' => $ar] as $language => $text) {
            if (mb_strlen($text) > 2000) {
                $this->addError("edits.$id.$language", __('Use at most :max characters.', ['max' => 2000]));

                return;
            }

            // :name, :count ... in the key must survive in every translation (the code fills them in)
            preg_match_all('/:[a-z_]+/i', $row->key, $needed);
            foreach ($needed[0] as $placeholder) {
                if ($text !== '' && ! str_contains($text, $placeholder)) {
                    $this->addError("edits.$id.$language", __('Keep the placeholder :placeholder from the English text.', ['placeholder' => $placeholder]));

                    return;
                }
            }
        }

        $arabicChanged = $ar !== (string) $row->ar;

        $row->update([
            'en' => $en === '' ? null : $en,
            'ar' => $ar === '' ? null : $ar,
            'machine' => $arabicChanged ? false : $row->machine,   // a person edited the Arabic: no longer a machine text
        ]);

        $this->dispatch('notify', type: 'success', message: __('Translation saved.'));
    }

    /** Fills the Arabic box of ONE row with a machine suggestion; nothing is saved until the admin presses Save. */
    public function suggest(int $id, MachineTranslator $translator): void
    {
        Gate::authorize('manage-users');

        $row = Translation::findOrFail($id);
        $source = trim((string) ($this->edits[$id]['en'] ?? '')) ?: ($row->en ?: $row->key);

        if ($text = $translator->translate($source, 'ar')) {
            $this->edits[$id]['ar'] = $text;
            $this->dispatch('notify', type: 'success', message: __('Suggestion added - check it and press Save.'));
        } else {
            $this->dispatch('notify', type: 'error', message: __('The translation service did not answer. Try again later.'));
        }
    }

    /** Machine-translates the next batch of empty Arabic texts and saves them marked as "machine". */
    public function translateMissing(MachineTranslator $translator): void
    {
        Gate::authorize('manage-users');

        $rows = Translation::untranslated('ar')->limit(self::BATCH)->get();

        if ($rows->isEmpty()) {
            $this->dispatch('notify', type: 'success', message: __('Every text is already translated.'));

            return;
        }

        $done = 0;
        foreach ($rows as $row) {
            if ($text = $translator->translate((string) ($row->en ?: $row->key), 'ar')) {
                $row->update(['ar' => $text, 'machine' => true]);
                $this->edits[$row->id]['ar'] = $text;
                $done++;
            }
        }

        $this->dispatch('notify', type: $done > 0 ? 'success' : 'error', message: $done > 0
            ? __(':done of :total texts were translated automatically. Please review them (marked "machine").', ['done' => $done, 'total' => $rows->count()])
            : __('The translation service did not answer. Try again later.'));
    }

    /** Adds the keys used in the code that are not in the table yet. */
    public function scanCode(): void
    {
        Gate::authorize('manage-users');

        $existing = Translation::pluck('key_hash')->flip();
        $added = 0;

        foreach (ScanTranslations::keysInCode() as $key => $file) {
            if (! $existing->has(sha1($key))) {
                Translation::create(['key' => $key, 'type' => 'code', 'en' => $key, 'ar' => null]);
                $added++;
            }
        }

        $this->dispatch('notify', type: 'success', message: $added === 0
            ? __('Every text used in the code is already in the list.')
            : __(':count new text(s) were found in the code.', ['count' => $added]));
    }

    /** Creates a key by hand (type "keyword"). */
    public function addKeyword(): void
    {
        Gate::authorize('manage-users');

        $data = $this->validate([
            'newKey' => ['required', 'string', 'max:500'],
            'newEn' => ['nullable', 'string', 'max:2000'],
            'newAr' => ['nullable', 'string', 'max:2000'],
        ], [], ['newKey' => __('Key'), 'newEn' => __('English'), 'newAr' => __('Arabic')]);

        if (Translation::where('key_hash', sha1(trim($data['newKey'])))->exists()) {
            $this->addError('newKey', __('This key already exists.'));

            return;
        }

        Translation::create([
            'key' => trim($data['newKey']), 'type' => 'keyword',
            'en' => trim((string) $data['newEn']) ?: trim($data['newKey']),
            'ar' => trim((string) $data['newAr']) ?: null,
        ]);

        $this->reset('newKey', 'newEn', 'newAr', 'adding');
        $this->dispatch('notify', type: 'success', message: __('Keyword added.'));
    }

    /** Only keywords created by hand can be removed; keys found in the code come back with the next scan. */
    public function deleteKeyword(int $id): void
    {
        Gate::authorize('manage-users');

        Translation::where('type', 'keyword')->whereKey($id)->delete();
        unset($this->edits[$id]);
        $this->dispatch('notify', type: 'success', message: __('Keyword deleted.'));
    }

    /** yes / no pairs: choosing only one of them filters, choosing both (or none) means "no filter" */
    private function yesNo($value): ?string
    {
        $v = collect(is_array($value) ? $value : [$value])->intersect(['yes', 'no'])->unique()->values();

        return $v->count() === 1 ? $v->first() : null;
    }

    public function render()
    {
        Gate::authorize('manage-users');

        $term = trim($this->search);
        $types = array_values(array_intersect(collect(is_array($this->type) ? $this->type : [$this->type])->all(), Translation::TYPES));

        $rows = Translation::query()
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                Sql::whereContains($q, 'key', $term);
                Sql::whereContains($q, 'en', $term, 'or');
                Sql::whereContains($q, 'ar', $term, 'or');
            }))
            ->when($this->yesNo($this->inEnglish) === 'yes', fn ($q) => $q->translated('en'))
            ->when($this->yesNo($this->inEnglish) === 'no', fn ($q) => $q->untranslated('en'))
            ->when($this->yesNo($this->inArabic) === 'yes', fn ($q) => $q->translated('ar'))
            ->when($this->yesNo($this->inArabic) === 'no', fn ($q) => $q->untranslated('ar'))
            ->when($types !== [], fn ($q) => $q->whereIn('type', $types))
            ->orderByRaw("case when ar is null or ar = '' then 0 else 1 end")   // untranslated first
            ->orderBy('key')
            ->paginate($this->rowsPerPage());

        foreach ($rows as $row) {
            $this->edits[$row->id] ??= ['en' => (string) $row->en, 'ar' => (string) $row->ar];
        }

        return view('livewire.admin.translation-manager', ['rows' => $rows])
            ->layout('components.layouts.app', ['title' => 'Translations']);
    }
}
