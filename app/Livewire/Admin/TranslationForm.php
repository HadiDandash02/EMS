<?php

namespace App\Livewire\Admin;

use App\Models\Translation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Create a keyword / edit a translation on its own page (/admin/translations/create, /admin/translations/{id}/edit). */
class TranslationForm extends Component
{
    #[Locked]
    public ?int $translationId = null;

    public string $key = '';

    public string $en = '';

    public string $ar = '';

    public function mount(?Translation $translation = null): void
    {
        Gate::authorize('manage-translations');

        if ($translation?->exists) {
            $this->translationId = $translation->id;
            $this->key = $translation->key;
            $this->en = (string) $translation->en;
            $this->ar = (string) $translation->ar;
        }
    }

    public function save()
    {
        Gate::authorize('manage-translations');

        // the key of an existing row is never changed from the browser: it is what the code looks up
        $editing = $this->translationId ? Translation::findOrFail($this->translationId) : null;
        $key = $editing ? $editing->key : trim($this->key);

        $this->validate([
            'key' => $editing ? ['nullable'] : ['required', 'string', 'max:500'],
            'en' => ['nullable', 'string', 'max:2000'],
            'ar' => ['nullable', 'string', 'max:2000'],
        ], [], ['key' => __('Key'), 'en' => __('English'), 'ar' => __('Arabic')]);

        if (! $editing && Translation::where('key_hash', sha1($key))->exists()) {
            throw ValidationException::withMessages(['key' => __('This key already exists.')]);
        }

        // :name, :count ... in the key must survive in every translation (the code fills them in)
        preg_match_all('/:[a-z_]+/i', $key, $needed);
        foreach (['en' => $this->en, 'ar' => $this->ar] as $language => $text) {
            foreach ($needed[0] as $placeholder) {
                if (trim($text) !== '' && ! str_contains($text, $placeholder)) {
                    throw ValidationException::withMessages([$language => __('Keep the placeholder :placeholder from the English text.', ['placeholder' => $placeholder])]);
                }
            }
        }

        $values = ['en' => trim($this->en) ?: ($editing ? null : $key), 'ar' => trim($this->ar) ?: null];

        if ($editing) {
            $editing->update($values);
        } else {
            Translation::create(['key' => $key, 'type' => 'keyword'] + $values);
        }

        session()->flash('success', $editing ? __('Translation saved.') : __('Keyword added.'));

        return $this->redirect(route('admin.translations'), navigate: true);
    }

    public function render()
    {
        Gate::authorize('manage-translations');

        return view('livewire.admin.translation-form', ['translation' => $this->translationId ? Translation::find($this->translationId) : null])
            ->layout('components.layouts.app', ['title' => $this->translationId ? 'Edit translation' : 'Add keyword']);
    }
}