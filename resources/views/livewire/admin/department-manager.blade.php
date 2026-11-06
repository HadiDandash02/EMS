<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Departments group employees. A department that still has staff cannot be deleted.</p>
        <button type="button" wire:click="create"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            New department
        </button>
    </div>

    <x-filter-bar placeholder="Department name or code…" label="Search departments" :total="$departments->total()" noun="department" />

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="search,clearFilters,gotoPage,nextPage,previousPage">
        @if ($departments->isEmpty())
            <x-empty-state title="No departments found" text="Try a different search or press Clear." />
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Department</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Employees</th><th class="px-4 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($departments as $d)
                        <tr wire:key="dept-{{ $d->id }}" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $d->name }}</td>
                            <td class="px-4 py-3"><span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ $d->code }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $d->employees_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $d->id }})" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Edit</button>
                                    <button type="button" wire:click="confirmDelete({{ $d->id }})" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($departments->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $departments->links() }}</div>@endif
        @endif
    </div>

    <x-modal :show="$formOpen" :title="$departmentId ? 'Edit department' : 'New department'" max-width="max-w-md">
        <form wire:submit="save">
            <div class="space-y-4 p-6">
                <div>
                    <label for="dp-name" class="block text-sm font-medium text-slate-700">Name *</label>
                    <input id="dp-name" type="text" wire:model="name" class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('name') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dp-code" class="block text-sm font-medium text-slate-700">Code *</label>
                    <input id="dp-code" type="text" wire:model="code" maxlength="10" class="mt-1 block w-full rounded-lg border-0 px-3 py-2 font-mono text-sm uppercase ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('code') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('code')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="button" wire:click="close" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">{{ $departmentId ? 'Save changes' : 'Create department' }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$deleteId !== null" title="Delete department" close="cancelDelete" max-width="max-w-md">
        <p class="px-6 py-5 text-sm text-slate-600">Permanently delete <strong class="text-slate-900">{{ $deleteName }}</strong>? This cannot be undone.</p>
        <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
            <button type="button" wire:click="cancelDelete" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-white">Cancel</button>
            <button type="button" wire:click="delete" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">Delete</button>
        </div>
    </x-modal>
</div>
