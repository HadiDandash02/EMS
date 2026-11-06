<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Create, edit and remove accounts. Employment details (department, manager) are saved with the account.</p>
        <button type="button" wire:click="create"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            New user
        </button>
    </div>

    <x-filter-bar placeholder="Name, username, email or employee number…" label="Search users" :total="$users->total()" noun="user">
        <div>
            <label for="uf-role" class="mb-1 block text-xs font-medium text-slate-600">Role</label>
            <select id="uf-role" wire:model="roleFilter" class="block w-full rounded-lg border-0 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
                <option value="">All roles</option>
                @foreach ($roles as $r)<option value="{{ $r->value }}">{{ $r->label() }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="uf-dept" class="mb-1 block text-xs font-medium text-slate-600">Department</label>
            <select id="uf-dept" wire:model="departmentFilter" class="block w-full rounded-lg border-0 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-500">
                <option value="">All departments</option>
                @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
        </div>
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($users->isEmpty())
            <x-empty-state title="No users found" text="Try a different search or press Clear." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">User</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Manager</th><th class="px-4 py-3 text-right">Actions</th>
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
                                <td class="px-4 py-3">
                                    @php $roleStyle = match ($u->role->value) { 'super_admin' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200', 'hr' => 'bg-sky-50 text-sky-700 ring-sky-200', 'manager' => 'bg-amber-50 text-amber-700 ring-amber-200', default => 'bg-slate-100 text-slate-700 ring-slate-200' }; @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $roleStyle }}">{{ $u->role->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $u->employee?->department->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $u->employee?->manager?->user->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="edit({{ $u->id }})" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Edit</button>
                                        @if ($u->id !== auth()->id())
                                            <button type="button" wire:click="confirmDelete({{ $u->id }})" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50">Delete</button>
                                        @endif
                                    </div>
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
    <x-modal :show="$formOpen" :title="$userId ? 'Edit user' : 'New user'" subtitle="Fields marked * are required." max-width="max-w-2xl">
        <form wire:submit="save">
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                @foreach ([['name', 'Full name *', 'text'], ['username', 'Username *', 'text'], ['email', 'Email *', 'email'], ['phone', 'Phone', 'tel']] as [$f, $label, $type])
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
                    <x-password-input id="um-password" name="password" :label="$userId ? 'New password (leave blank to keep the current one)' : 'Password *'" autocomplete="new-password" wire:model="password" :error="$errors->first('password')" />
                    <button type="button" @click="gen()" class="mt-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500">Generate a strong password</button>
                </div>

                <div>
                    <label for="um-role" class="block text-sm font-medium text-slate-700">Role *</label>
                    <select id="um-role" wire:model.live="role" @disabled($userId && $role === 'super_admin')
                            class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-50 @error('role') ring-rose-400 @else ring-slate-300 @enderror">
                        @foreach ($roles as $r)
                            @continue($r->value === 'super_admin' && $userId && $role !== 'super_admin')
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                @if ($role !== 'super_admin')
                    <div>
                        <label for="um-dept" class="block text-sm font-medium text-slate-700">Department *</label>
                        <select id="um-dept" wire:model="department_id"
                                class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('department_id') ring-rose-400 @else ring-slate-300 @enderror">
                            <option value="">Select…</option>
                            @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                        @error('department_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="um-job" class="block text-sm font-medium text-slate-700">Job title *</label>
                        <input id="um-job" type="text" wire:model="job_title"
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('job_title') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('job_title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="um-manager" class="block text-sm font-medium text-slate-700">Reports to (manager)</label>
                        <select id="um-manager" wire:model="manager_id"
                                class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('manager_id') ring-rose-400 @else ring-slate-300 @enderror">
                            <option value="">No manager</option>
                            @foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->user->name }}</option>@endforeach
                        </select>
                        @error('manager_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="um-number" class="block text-sm font-medium text-slate-700">Employee number</label>
                        <input id="um-number" type="text" wire:model="employee_number" placeholder="Auto-generated if empty"
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('employee_number') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('employee_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="um-hired" class="block text-sm font-medium text-slate-700">Hired on</label>
                        <input id="um-hired" type="date" wire:model="hired_at" max="{{ now()->toDateString() }}"
                               class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('hired_at') ring-rose-400 @else ring-slate-300 @enderror">
                        @error('hired_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @else
                    <p class="rounded-lg bg-fuchsia-50 px-3 py-2 text-sm text-fuchsia-700 ring-1 ring-inset ring-fuchsia-200 sm:col-span-2">
                        Super admin accounts have no department or manager and do not own tasks.
                    </p>
                @endif
            </div>
            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">{{ $userId ? 'Save changes' : 'Create user' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Delete confirmation --}}
    <x-modal :show="$deleteId !== null" title="Delete user" close="cancelDelete" max-width="max-w-md">
        <div class="space-y-3 px-6 py-5 text-sm text-slate-600">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.6h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
                <p>Permanently delete <strong class="text-slate-900">{{ $deleteName }}</strong>? This cannot be undone.</p>
            </div>
        </div>
        <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
            <button type="button" wire:click="cancelDelete" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">Cancel</button>
            <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-60">Delete</button>
        </div>
    </x-modal>
</div>
