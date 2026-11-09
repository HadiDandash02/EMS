<?php

namespace App\Livewire\Admin;

use App\Models\Translation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** "Delete this translation?" on its own page (/admin/translations/{id}/delete). */
class DeleteTranslation extends Component
{
    #[Locked]
    public int $translationId;

    public function mount(Translation $translation): void
    {
        Gate::authorize('manage-translations');

        $this->translationId = $translation->id;
    }

    public function delete()
    {
        Gate::authorize('manage-translations');

        Translation::findOrFail($this->translationId)->delete();

        session()->flash('success', __('Translation deleted.'));

        return $this->redirect(route('admin.translations'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.delete-translation', ['translation' => Translation::findOrFail($this->translationId)])
            ->layout('components.layouts.app', ['title' => 'Delete translation']);
    }
}