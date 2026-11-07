<div class="page">
    <x-breadcrumbs :items="[[__('Departments'), route('admin.departments')], [$departmentId ? __('Edit department') : __('New department'), null]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $departmentId ? __('Edit department') : __('New department') }}</h2>
            <p class="text-sm text-slate-500">{{ $departmentId ? __('Rename the department or change its code.') : __('Departments group employees and decide who reports where.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <form wire:submit="save" class="card lg:col-span-2">
            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-4">
                <h3 class="font-semibold text-slate-900">{{ __('Department details') }}</h3>
                <p class="text-xs text-slate-500">{{ __('Fields marked * are required.') }}</p>
            </div>

            <div class="grid gap-5 px-6 py-6 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="dp-name" class="block text-sm font-medium text-slate-700">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                    <input id="dp-name" type="text" wire:model="name" autofocus placeholder="{{ __('e.g. Human Resources') }}"
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('name') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dp-code" class="block text-sm font-medium text-slate-700">{{ __('Code') }} <span class="text-rose-500">*</span></label>
                    <input id="dp-code" type="text" wire:model="code" maxlength="10" placeholder="HR"
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 font-mono text-sm uppercase shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('code') ring-rose-400 @else ring-slate-300 @enderror">
                    <p class="mt-1 text-xs text-slate-400">{{ __('2-10 letters or numbers.') }}</p>
                    @error('code')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                <x-button :href="route('admin.departments')" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $departmentId ? __('Save changes') : __('Create department') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </x-button>
            </div>
        </form>

        <aside class="space-y-5">
            @if ($department)
                <section class="card p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Employees') }}</h3>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700">{{ $department->employees_count }}</span>
                    </div>
                    <ul class="mt-4 space-y-3">
                        @forelse ($department->employees as $e)
                            <li class="flex items-center gap-3">
                                <x-avatar :user="$e->user" size="sm" />
                                <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-900">{{ $e->user->name }}</p><p class="truncate text-xs text-slate-500">{{ $e->job_title }}</p></div>
                            </li>
                        @empty
                            <li class="text-sm text-slate-400">{{ __('No employees in this department yet.') }}</li>
                        @endforelse
                    </ul>
                    @if ($department->employees_count > 8)<p class="mt-3 text-xs text-slate-400">+ {{ $department->employees_count - 8 }} {{ __('more') }}</p>@endif
                    <dl class="mt-4 border-t border-slate-100 pt-3 text-sm">
                        <div class="flex justify-between py-1"><dt class="text-slate-500">{{ __('Created') }}</dt><dd class="font-medium text-slate-800">{{ $department->created_at->format('d/m/Y') }}</dd></div>
                        <div class="flex justify-between py-1"><dt class="text-slate-500">{{ __('Last updated') }}</dt><dd class="font-medium text-slate-800">{{ $department->updated_at->diffForHumans() }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5">
                    <h3 class="text-sm font-semibold text-rose-800">{{ __('Danger zone') }}</h3>
                    <p class="mt-1 text-xs text-rose-700/80">{{ __('A department that still has employees cannot be deleted.') }}</p>
                    <x-button :href="route('admin.departments.delete', $department)" variant="danger" size="sm" icon="trash" class="mt-3">{{ __('Delete department') }}</x-button>
                </section>
            @else
                <section class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Good to know') }}</h3>
                    <ul class="mt-3 space-y-3 text-sm text-slate-600">
                        <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>{{ __('The code is short and always upper case, for example ENG or HR.') }}</li>
                        <li class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>{{ __('Assign employees to the department from the user form.') }}</li>
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>