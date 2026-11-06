<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Every signed-in user edits ONLY their own profile (the component never takes a user id). */
class EditProfile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $phone = '';

    public $photo = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->username = (string) $user->username;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $this->username = strtolower(trim($this->username));
        $this->email = strtolower(trim($this->email));

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['required', 'regex:/^[a-z0-9_.]{3,30}$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
        ], [
            'username.regex' => 'Use 3-30 letters, numbers, dots or underscores.',
            'phone.regex' => 'Use digits, spaces, + - ( ) only.',
        ]);

        $user->update([...$data, 'phone' => $data['phone'] ?: null]);

        $this->dispatch('notify', type: 'success', message: 'Profile updated.');
    }

    private const PHOTO_RULES = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    /** Validate as soon as a file is chosen; an invalid file is discarded instead of previewed. */
    public function updatedPhoto(): void
    {
        try {
            $this->validateOnly('photo', ['photo' => self::PHOTO_RULES]);
        } catch (ValidationException $e) {
            $this->reset('photo');

            throw $e;
        }
    }

    public function savePhoto(): void
    {
        $this->validate(['photo' => self::PHOTO_RULES]);

        $user = auth()->user();
        $this->deleteStoredPhoto($user);

        $user->avatar_path = $this->photo->store('avatars', 'local');
        $user->save();

        $this->reset('photo');
        $this->dispatch('notify', type: 'success', message: 'Profile picture updated.');
    }

    public function removePhoto(): void
    {
        $user = auth()->user();
        $this->deleteStoredPhoto($user);
        $user->avatar_path = null;
        $user->save();

        $this->reset('photo');
        $this->dispatch('notify', type: 'success', message: 'Profile picture removed.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        auth()->user()->update(['password' => $this->password]); // 'hashed' cast

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('notify', type: 'success', message: 'Password changed.');
    }

    private function deleteStoredPhoto(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
        }
    }

    public function render()
    {
        return view('livewire.profile.edit-profile', [
            'user' => auth()->user()->load('employee.department', 'employee.manager.user'),
        ])->layout('components.layouts.app', ['title' => 'My profile']);
    }
}
