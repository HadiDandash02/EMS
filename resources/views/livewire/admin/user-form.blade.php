@php
    $isAdminAccount = $userId && $role === 'super_admin';
    $roleOptions = collect($roles)->reject(fn ($r) => $r->value === 'super_admin' && $userId)->mapWithKeys(fn ($r) => [$r->value => $r->label()]);
    $field = 'mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500';
@endphp
<div class="mx-auto max-w-4xl space-y-5">
    <x-button :href="route('admin.users')" variant="secondary" size="sm" icon="back" class="rtl:flex-row-reverse">{{ __('Back') }}</x-button>

    <form wire:submit="save" class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900">{{ $userId ? __('Edit user') : __('New user') }}</h2>
            <p class="text-sm text-slate-500">{{ __('Fields marked * are required.') }}</p>
        </div>

        {{-- Account --}}
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
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
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
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
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
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
</div>
