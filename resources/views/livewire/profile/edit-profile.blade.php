<div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-3">
    {{-- Identity card --}}
    <div class="space-y-6 lg:col-span-1">
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="h-24 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
            <div class="-mt-12 px-6 pb-6 text-center">
                @if ($photo && $photo->isPreviewable())
                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="mx-auto h-24 w-24 rounded-full object-cover ring-4 ring-white">
                @else
                    <x-avatar :user="$user" size="xl" class="mx-auto !ring-4" />
                @endif
                <h2 class="mt-3 text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">&#64;{{ $user->username }}</p>
                <span class="mt-2 inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200">{{ $user->role->label() }}</span>

                <div class="mt-5 border-t border-slate-100 pt-5 text-left">
                    <label class="block text-sm font-medium text-slate-700">Profile picture</label>
                    <input type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp"
                           class="mt-2 block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG or WebP · max 2 MB</p>
                    @error('photo')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    <div wire:loading wire:target="photo" class="mt-2 text-sm text-indigo-600">Uploading…</div>

                    <div class="mt-3 flex gap-2">
                        @if ($photo)
                            <button type="button" wire:click="savePhoto" wire:loading.attr="disabled" wire:target="savePhoto"
                                    class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">Save picture</button>
                        @endif
                        @if ($user->avatar_path)
                            <button type="button" wire:click="removePhoto" wire:confirm="Remove your profile picture?"
                                    class="rounded-lg px-3 py-2 text-sm font-medium text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50">Remove</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($user->employee)
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-sm font-semibold text-slate-900">Employment</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    @foreach ([
                        'Employee no.' => $user->employee->employee_number,
                        'Job title' => $user->employee->job_title,
                        'Department' => $user->employee->department->name,
                        'Manager' => $user->employee->manager?->user->name ?? '—',
                        'Hired' => $user->employee->hired_at?->format('d/m/Y') ?? '—',
                    ] as $label => $value)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-right font-medium text-slate-800">{{ $value }}</dd></div>
                    @endforeach
                </dl>
                <p class="mt-4 text-xs text-slate-400">Employment details are managed by the administrator.</p>
            </div>
        @endif
    </div>

    {{-- Forms --}}
    <div class="space-y-6 lg:col-span-2">
        <form wire:submit="saveProfile" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-semibold text-slate-900">Personal information</h3>
                <p class="text-sm text-slate-500">Update your name, username and contact details.</p>
            </div>
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                @php
                    $fields = [
                        ['name', 'Full name', 'text', 'name', ''],
                        ['username', 'Username', 'text', 'username', 'Used to sign in. Letters, numbers, dots and underscores.'],
                        ['email', 'Email', 'email', 'email', ''],
                        ['phone', 'Phone', 'tel', 'tel', ''],
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
                <button type="submit" wire:loading.attr="disabled" wire:target="saveProfile"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveProfile">Save changes</span>
                    <span wire:loading wire:target="saveProfile">Saving…</span>
                </button>
            </div>
        </form>

        <form wire:submit="updatePassword" class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-base font-semibold text-slate-900">Change password</h3>
                <p class="text-sm text-slate-500">At least 8 characters, with letters and numbers.</p>
            </div>
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-password-input id="current_password" name="current_password" label="Current password" wire:model="current_password" :error="$errors->first('current_password')" />
                </div>
                <x-password-input id="new_password" name="password" label="New password" autocomplete="new-password" wire:model="password" :error="$errors->first('password')" />
                <x-password-input id="password_confirmation" name="password_confirmation" label="Confirm new password" autocomplete="new-password" wire:model="password_confirmation" />
            </div>
            <div class="flex justify-end rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="updatePassword">Update password</span>
                    <span wire:loading wire:target="updatePassword">Updating…</span>
                </button>
            </div>
        </form>
    </div>
</div>
