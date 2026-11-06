@php
    $item = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-[13px] font-medium transition';
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ __('Create, edit and remove accounts. Employment details (department, manager) are saved with the account.') }}</p>
        <button type="button" wire:click="create"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            {{ __('New user') }}
        </button>
    </div>

    <x-filter-bar :placeholder="__('Name, username, email or employee number…')" :label="__('Search users')" :total="$users->total()" noun="user">
        <x-searchable-select id="uf-role" :label="__('Role')" wire:model="roleFilter" :placeholder="__('All roles')" :all-label="__('All roles')" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])" />
        <x-searchable-select id="uf-dept" :label="__('Department')" wire:model="departmentFilter" :placeholder="__('All departments')" :all-label="__('All departments')" :options="$departments->pluck('name', 'id')" />
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($users->isEmpty())
            <x-empty-state :title="__('No users found')" :text="__('Try a different search or press Clear.')" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100/70 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('User') }}</th><th class="px-4 py-3 text-start">{{ __('Role') }}</th><th class="px-4 py-3 text-start">{{ __('Department') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Manager') }}</th><th class="w-16 px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $u)
                            <tr wire:key="user-{{ $u->id }}" class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$u" />
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">{{ $u->name }} <span class="text-xs font-normal text-slate-400">&#64;{{ $u->username }}</span></p>
                                            <p class="truncate text-xs text-slate-500">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-role-badge :role="$u->role" /></td>
                                <td class="px-4 py-3 text-slate-600">{{ $u->employee?->department->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $u->employee?->manager?->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-row-menu>
                                        <button type="button" role="menuitem" wire:click="edit({{ $u->id }})" class="{{ $item }} hover:bg-slate-50">
                                            <svg class="h-[18px] w-[18px] text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l1-4L17.5 3.5a1.9 1.9 0 012.7 0l.3.3a1.9 1.9 0 010 2.7L8 19l-4 1z"/></svg>
                                            {{ __('Edit') }}
                                        </button>
                                        @if ($u->id !== auth()->id())
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <button type="button" role="menuitem" wire:click="confirmDelete({{ $u->id }})" class="{{ $item }} text-rose-600 hover:bg-rose-50">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48.1 48.1 0 00-3.48-.4m-12 .56c.34-.06.68-.12 1.02-.17m0 0a48.1 48.1 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51.96 51.96 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48.67 48.67 0 00-7.5 0"/></svg>
                                                {{ __('Delete') }}
                                            </button>
                                        @endif
                                    </x-row-menu>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $users->links() }}</div>@endif
        @endif
    </div>

    {{-- Create / edit --}}
    <x-modal :show="$formOpen" :title="$userId ? __('Edit user') : __('New user')" :subtitle="__('Fields marked * are required.')" max-width="max-w-2xl">
        <form wire:submit="save">
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                @foreach ([['name', __('Full name').' *', 'text'], ['username', __('Username').' *', 'text'], ['email', __('Email').' *', 'email'], ['phone', __('Phone'), 'tel']] as [$f, $label, $type])
                    <div>
                        <label for="um-{{ $f }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input id="um-{{ $f }}" type="{{ $type }}" wire:model="{{ $f }}" @if ($f === 'username') autocapitalize="none" @endif
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error($f) ring-rose-400 @else ring-slate-300 @enderror">
                        @error($f)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="sm:col-span-2" x-data="{
                    gen() {
                        const c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789', s = '!@-_#';
                        const r = n => Array.from(crypto.getRandomValues(new Uint32Array(n)));
                        let p = r(14).map(v => c[v % c.length]).join('') + s[r(1)[0] % s.length] + (10 + r(1)[0] % 90);
                        $wire.set('password', p);
                    } }">
                    <x-password-input id="um-password" name="password" :label="$userId ? __('New password (leave blank to keep the current one)') : __('Password').' *'" autocomplete="new-password" wire:model="password" :error="$errors->first('password')" />
                    <button type="button" @click="gen()" class="mt-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500">{{ __('Generate a strong password') }}</button>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Role') }} *</label>
                    @php $roleOptions = collect($roles)->reject(fn ($r) => $r->value === 'super_admin' && $userId && $role !== 'super_admin')->mapWithKeys(fn ($r) => [$r->value => $r->label()]); @endphp
                    @if ($userId && $role === 'super_admin')
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 ring-1 ring-inset ring-slate-200">{{ __('Super Admin') }}</p>
                    @else
                        <x-searchable-select id="um-role" wire:model.live="role" :options="$roleOptions" :error="$errors->has('role')" />
                    @endif
                    @error('role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                @if ($role !== 'super_admin')
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Department') }} *</label>
                        <x-searchable-select id="um-dept" wire:model="department_id" :placeholder="__('Select…')" :options="$departments->pluck('name', 'id')" :error="$errors->has('department_id')" />
                        @error('department_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="um-job" class="block text-sm font-medium text-slate-700">{{ __('Job title') }} *</label>
                        <input id="um-job" type="text" wire:model="job_title"
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('job_title') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('job_title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Reports to (manager)') }}</label>
                        <x-searchable-select id="um-manager" wire:model="manager_id" :placeholder="__('No manager')" :all-label="__('No manager')" :options="$managers->mapWithKeys(fn ($m) => [$m->id => $m->user->name])" :error="$errors->has('manager_id')" />
                        @error('manager_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="um-number" class="block text-sm font-medium text-slate-700">{{ __('Employee number') }}</label>
                        <input id="um-number" type="text" wire:model="employee_number" placeholder="{{ __('Auto-generated if empty') }}"
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('employee_number') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('employee_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-date-input id="um-hired" :label="__('Hired on')" wire:model="hired_at" :max="now()->toDateString()" :error="$errors->has('hired_at')" />
                        @error('hired_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <p class="rounded-lg bg-fuchsia-50 px-3 py-2 text-sm text-fuchsia-700 ring-1 ring-inset ring-fuchsia-200 sm:col-span-2">
                        {{ __('Super admin accounts have no department or manager and do not own tasks.') }}
                    </p>
                @endif
            </div>
            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">{{ __('Cancel') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">{{ $userId ? __('Save changes') : __('Create user') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Delete confirmation --}}
    <x-modal :show="$deleteId !== null" :title="__('Delete user')" close="cancelDelete" max-width="max-w-md">
        <div class="space-y-3 px-6 py-5 text-sm text-slate-600">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
                <p>{{ __('Permanently delete') }} <strong class="text-slate-900">{{ $deleteName }}</strong>? {{ __('This cannot be undone.') }}</p>
            </div>
        </div>
        <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
            <button type="button" wire:click="cancelDelete" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">{{ __('Cancel') }}</button>
            <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-60">{{ __('Delete') }}</button>
        </div>
    </x-modal>
</div>
