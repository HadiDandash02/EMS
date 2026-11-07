@php
    $isAdminAccount = $userId && $role === 'super_admin';
    $roleOptions = collect($roles)->reject(fn ($r) => $r->value === 'super_admin' && $userId)->mapWithKeys(fn ($r) => [$r->value => $r->label()]);
    $field = 'mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500';
@endphp
<div class="page">
    <x-breadcrumbs :items="[[__('Users'), route('admin.users')], [$userId ? __('Edit user') : __('New user'), null]]" />
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $userId ? __('Edit user') : __('New user') }}</h2>
            <p class="text-sm text-slate-500">{{ $userId ? __('Update the account, password and employment details.') : __('Create an account and its employment details in one step.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
    <form wire:submit="save" class="space-y-6 lg:col-span-2">
{{-- Account --}}
        <section class="card">
            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-4"><h3 class="font-semibold text-slate-900">{{ __('Account') }}</h3></div>
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                @foreach ([['name', __('Full name').' *', 'text'], ['username', __('Username').' *', 'text'], ['email', __('Email').' *', 'email'], ['phone', __('Phone'), 'tel']] as [$f, $label, $type])
                    <div>
                        <label for="uf-{{ $f }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input id="uf-{{ $f }}" type="{{ $type }}" wire:model="{{ $f }}" @if ($f === 'username') autocapitalize="none" @endif
                               class="{{ $field }} @error($f) ring-rose-400 @else ring-slate-300 @enderror">
                        @error($f)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Security --}}
        <section class="card">
            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-4">
                <h3 class="font-semibold text-slate-900">{{ __('Password') }}</h3>
                <p class="text-xs text-slate-500">{{ $userId ? __('Leave blank to keep the current password.') : __('A strong password is required.') }}</p>
            </div>
            <div class="p-6" x-data="{
                gen() {
                    const pick = s => s[crypto.getRandomValues(new Uint32Array(1))[0] % s.length];
                    const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%^&*-_'];
                    const all = sets.join('');
                    let chars = sets.map(pick);                                   // one of each kind, always
                    while (chars.length < 16) chars.push(pick(all));
                    for (let i = chars.length - 1; i > 0; i--) { const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1); [chars[i], chars[j]] = [chars[j], chars[i]]; }
                    const p = chars.join('');
                    $wire.set('password', p);
                    window.dispatchEvent(new CustomEvent('password-generated', { detail: p }));
                } }">
                <x-password-input id="uf-password" name="password" :label="$userId ? __('New password').' ('.__('optional').')' : __('Password').' *'" autocomplete="new-password" meter wire:model="password" :error="$errors->first('password')" />
                <x-button type="button" variant="soft" size="sm" icon="key" class="mt-3" x-on:click="gen()">{{ __('Generate a strong password') }}</x-button>
            </div>
        </section>

        {{-- Role & employment --}}
        <section class="card">
            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-4"><h3 class="font-semibold text-slate-900">{{ __('Role & employment') }}</h3></div>
            <div class="grid gap-5 p-6 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Role') }} *</label>
                    @if ($isAdminAccount)
                        <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Super Admin') }}</p>
                    @else
                        <x-searchable-select id="uf-role" wire:model.live="role" :options="$roleOptions" :error="$errors->has('role')" />
                    @endif
                    @error('role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                @if ($role !== 'super_admin')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Department') }} *</label>
                        <x-searchable-select id="uf-dept" wire:model="department_id" :placeholder="__('Select…')" :options="$departments->pluck('name', 'id')" :error="$errors->has('department_id')" />
                        @error('department_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="uf-job" class="block text-sm font-medium text-slate-700">{{ __('Job title') }} *</label>
                        <input id="uf-job" type="text" wire:model="job_title" class="{{ $field }} @error('job_title') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('job_title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Reports to (manager)') }}</label>
                        <x-searchable-select id="uf-manager" wire:model="manager_id" :placeholder="__('No manager')" :all-label="__('No manager')" :options="$managers->mapWithKeys(fn ($m) => [$m->id => $m->user->name])" :error="$errors->has('manager_id')" />
                        @error('manager_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="uf-number" class="block text-sm font-medium text-slate-700">{{ __('Employee number') }}</label>
                        <input id="uf-number" type="text" wire:model="employee_number" placeholder="{{ __('Auto-generated if empty') }}" class="{{ $field }} @error('employee_number') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('employee_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-date-input id="uf-hired" :label="__('Hired on')" wire:model="hired_at" :max="now()->toDateString()" :error="$errors->has('hired_at')" />
                        @error('hired_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <p class="rounded-lg bg-fuchsia-50 px-3 py-2.5 text-sm text-fuchsia-700 ring-1 ring-inset ring-fuchsia-200 sm:col-span-2">
                        {{ __('Super admin accounts have no department or manager and do not own tasks.') }}
                    </p>
                @endif
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.users')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
            <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $userId ? __('Save changes') : __('Create user') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </x-button>
        </div>
    </form>

    {{-- ============ Side panel ============ --}}
    <aside class="space-y-5">
        {{-- live preview of the account being typed --}}
        <section class="card">
            <div class="h-16 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
            <div class="-mt-8 px-5 pb-5 text-center">
                @if ($subject)
                    <x-avatar :user="$subject" size="xl" class="mx-auto !ring-4" />
                @else
                    <span class="mx-auto inline-flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-3xl font-semibold text-white ring-4 ring-white"
                          x-data x-text="($wire.name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase()).join('') || '?'"></span>
                @endif
                <p class="mt-3 text-base font-semibold text-slate-900" x-data x-text="$wire.name || @js($subject?->name ?? __('New user'))"></p>
                <p class="text-sm text-slate-500" x-data x-text="'@' + ($wire.username || 'username')"></p>
                <p class="truncate text-xs text-slate-400" x-data x-text="$wire.email || 'email@example.com'"></p>
                @if ($subject)<div class="mt-3"><x-role-badge :role="$subject->role" /></div>@endif
            </div>
        </section>

        @if ($subject)
            <section class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Account information') }}</h3>
                <dl class="mt-3 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ __('Created') }}</dt><dd class="font-medium text-slate-800">{{ $subject->created_at->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ __('Last updated') }}</dt><dd class="font-medium text-slate-800">{{ $subject->updated_at->diffForHumans() }}</dd></div>
                    @if ($subject->employee)
                        <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ __('Employee no.') }}</dt><dd class="font-medium text-slate-800">{{ $subject->employee->employee_number }}</dd></div>
                        <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ __('Department') }}</dt><dd class="font-medium text-slate-800">{{ $subject->employee->department->name }}</dd></div>
                        <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ __('Manager') }}</dt><dd class="font-medium text-slate-800">{{ $subject->employee->manager?->user->name ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ __('Tasks') }}</dt><dd class="font-medium text-slate-800">{{ $taskCount }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($subject->id !== auth()->id())
                <section class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5">
                    <h3 class="text-sm font-semibold text-rose-800">{{ __('Danger zone') }}</h3>
                    <p class="mt-1 text-xs text-rose-700/80">{{ __('Deleting an account cannot be undone. Accounts that own tasks cannot be deleted.') }}</p>
                    <x-button :href="route('admin.users.delete', $subject)" variant="danger" size="sm" icon="trash" class="mt-3">{{ __('Delete user') }}</x-button>
                </section>
            @endif
        @else
            <section class="card p-5">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Good to know') }}</h3>
                <ul class="mt-3 space-y-3 text-sm text-slate-600">
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>{{ __('The username is used to sign in and is always lower case.') }}</li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>{{ __('Use the generator for a strong password and share it securely.') }}</li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>{{ __('Employees, managers and HR need a department. Super admins do not.') }}</li>
                </ul>
            </section>
        @endif
    </aside>
    </div>
</div>
