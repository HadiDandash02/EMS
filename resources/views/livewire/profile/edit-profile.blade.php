<div class="page">
    <div>
        <h2 class="text-xl font-bold text-slate-900">{{ __('My profile') }}</h2>
        <p class="text-sm text-slate-500">{{ __('Your profile and password.') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Identity card --}}
        <div class="space-y-6 lg:col-span-1">
            <div class="card">
                <div class="h-24 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
                <div class="-mt-12 px-6 pb-6 text-center">
                    @if ($photo && $photo->isPreviewable())
                        <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Preview') }}" class="mx-auto h-24 w-24 rounded-full object-cover ring-4 ring-white">
                    @else
                        <x-avatar :user="$user" size="xl" class="mx-auto !ring-4" />
                    @endif
                    <h2 class="mt-3 text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">&#64;{{ $user->username }}</p>
                    <div class="mt-2"><x-role-badge :role="$user->role" /></div>

                    <dl class="mt-5 divide-y divide-slate-100 border-t border-slate-100 text-sm">
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">{{ __('Email') }}</dt><dd class="truncate font-medium text-slate-800">{{ $user->email }}</dd></div>
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">{{ __('Member since') }}</dt><dd class="font-medium text-slate-800">{{ $user->created_at?->format('d/m/Y') }}</dd></div>
                    </dl>

                    <div class="mt-3 border-t border-slate-100 pt-5 text-start">
                        <label class="block text-sm font-medium text-slate-700">{{ __('Profile picture') }}</label>
                        <input type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp"
                               class="mt-2 block w-full text-sm text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-slate-400">{{ __('JPG, PNG or WebP · max 2 MB') }}</p>
                        @error('photo')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        <div wire:loading wire:target="photo" class="mt-2 text-sm text-indigo-600">{{ __('Uploading…') }}</div>

                        <div class="mt-3 flex gap-2">
                            @if ($photo)
                                <x-button type="button" variant="primary" size="sm" icon="save" wire:click="savePhoto" wire:loading.attr="disabled" wire:target="savePhoto">{{ __('Save picture') }}</x-button>
                            @endif
                            @if ($user->avatar_path)
                                <x-button type="button" variant="soft-danger" size="sm" icon="trash" wire:click="removePhoto">{{ __('Remove') }}</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($user->employee)
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Employment') }}</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach ([
                            __('Employee no.') => $user->employee->employee_number,
                            __('Job title') => $user->employee->job_title,
                            __('Department') => $user->employee->department->name,
                            __('Manager') => $user->employee->manager?->user->name ?? '—',
                            __('Hired') => $user->employee->hired_at?->format('d/m/Y') ?? '—',
                        ] as $label => $value)
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-end font-medium text-slate-800">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs text-slate-400">{{ __('Employment details are managed by the administrator.') }}</p>
                </div>
            @endif
        </div>

        {{-- Forms --}}
        <div class="space-y-6 lg:col-span-2">
            <form wire:submit="saveProfile" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Personal information') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('Update your name, username and contact details.') }}</p>
                </div>
                <div class="grid gap-5 p-6 sm:grid-cols-2">
                    @php
                        $fields = [
                            ['name', __('Full name'), 'text', 'name', ''],
                            ['username', __('Username'), 'text', 'username', __('Used to sign in. Letters, numbers, dots and underscores.')],
                            ['email', __('Email'), 'email', 'email', ''],
                            ['phone', __('Phone'), 'tel', 'tel', ''],
                        ];
                    @endphp
                    @foreach ($fields as [$field, $label, $type, $auto, $hint])
                        <div>
                            <label for="p-{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input id="p-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" autocomplete="{{ $auto }}"
                                   class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error($field) ring-rose-400 @else ring-slate-300 @enderror">
                            @if ($hint && ! $errors->has($field))<p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>@endif
                            @error($field)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end rounded-b-2xl bg-slate-50 px-6 py-4">
                    <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="saveProfile">
                        <span wire:loading.remove wire:target="saveProfile">{{ __('Save changes') }}</span>
                        <span wire:loading wire:target="saveProfile">{{ __('Saving…') }}</span>
                    </x-button>
                </div>
            </form>

            <form wire:submit="updatePassword" id="change-password" class="scroll-mt-24 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Change password') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('At least 10 characters, with upper and lower case letters, a number and a symbol.') }}</p>
                </div>
                <div class="grid gap-5 p-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-password-input id="current_password" name="current_password" :label="__('Current password')" wire:model="current_password" :error="$errors->first('current_password')" />
                    </div>
                    <x-password-input id="new_password" name="password" :label="__('New password')" autocomplete="new-password" meter wire:model="password" :error="$errors->first('password')" />
                    <x-password-input id="password_confirmation" name="password_confirmation" :label="__('Confirm new password')" autocomplete="new-password" wire:model="password_confirmation" />
                </div>
                <div class="flex justify-end rounded-b-2xl bg-slate-50 px-6 py-4">
                    <x-button type="submit" variant="primary" icon="key" wire:loading.attr="disabled" wire:target="updatePassword">
                        <span wire:loading.remove wire:target="updatePassword">{{ __('Update password') }}</span>
                        <span wire:loading wire:target="updatePassword">{{ __('Updating…') }}</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
