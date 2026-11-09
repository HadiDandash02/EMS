@php
    $editing = (bool) $taskId;
    $me = auth()->user();
    $tones = [
        'low' => 'peer-checked:border-slate-500 peer-checked:bg-slate-50 peer-checked:text-slate-800',
        'medium' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800',
        'high' => 'peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-800',
    ];
    $dots = ['low' => 'bg-slate-400', 'medium' => 'bg-amber-500', 'high' => 'bg-rose-500'];
@endphp
<div class="page">
    <x-form-errors />
    <x-breadcrumbs :items="[[__('Tasks'), $listUrl], [$editing ? __('Edit task') : __('New task'), null]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">{{ $editing ? __('Edit task') : __('New task') }}</h2>
            <p class="text-sm text-slate-500">{{ $editing ? __('Update the details of this task. Only pending tasks can be edited by their owner.') : __('Describe the work. It starts as Pending and you can begin it whenever you are ready.') }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ============ Form ============ --}}
        <form novalidate data-validate wire:submit="save" class="card lg:col-span-2">
            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 via-white to-white px-6 py-4">
                <h3 class="font-semibold text-slate-900">{{ __('Task details') }}</h3>
                <p class="text-xs text-slate-500">{{ __('Fields marked * are required.') }}</p>
            </div>

            <div class="space-y-6 px-6 py-6">
                @if ($isAdmin && ! $editing)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Employee') }} <span class="text-rose-500">*</span></label>
                        <x-searchable-select id="tf-owner" wire:model="employee_id" :placeholder="__('Select an employee…')" :options="$owners" :error="$errors->has('employee_id')" />
                        <p class="mt-1 text-xs text-slate-400">{{ __('The task will be assigned to this employee.') }}</p>
                        @error('employee_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label for="tf-title" class="block text-sm font-medium text-slate-700">{{ __('Title') }} <span class="text-rose-500">*</span></label>
                    <input id="tf-title" type="text" wire:model="title" required minlength="3" maxlength="150" autofocus placeholder="{{ __('e.g. Prepare the monthly sales report') }}"
                           class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('title') ring-rose-400 @else ring-slate-300 @enderror">
                    @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div x-data="{ n: 0 }" x-init="n = $refs.d.value.length">
                    <div class="flex items-center justify-between">
                        <label for="tf-desc" class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                        <span class="text-xs text-slate-400"><span x-text="n"></span> / 2000</span>
                    </div>
                    <textarea id="tf-desc" x-ref="d" rows="6" wire:model="description" maxlength="2000" @input="n = $event.target.value.length"
                              placeholder="{{ __('What needs to be done? Add any details the reviewer should know.') }}"
                              class="mt-1 block w-full rounded-lg border-0 px-3 py-2.5 text-sm shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-indigo-500 @error('description') ring-rose-400 @else ring-slate-300 @enderror"></textarea>
                    @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700">{{ __('Priority') }} <span class="text-rose-500">*</span></span>
                    <div class="mt-2 grid grid-cols-3 gap-3" role="radiogroup">
                        @foreach ($priorities as $p)
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="priority" value="{{ $p->value }}" class="peer sr-only">
                                <span class="flex flex-col items-center gap-1.5 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 {{ $tones[$p->value] }}">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $dots[$p->value] }}"></span>
                                    {{ $p->label() }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('priority')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="max-w-xs">
                    <x-date-input id="tf-due" :label="__('Due date')" wire:model="due_date" :min="now()->toDateString()" :error="$errors->has('due_date')" />
                    <p class="mt-1 text-xs text-slate-400">{{ __('Optional. Today or later.') }}</p>
                    @error('due_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                <x-button :href="$cancelUrl" variant="secondary" icon="x">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="primary" icon="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $editing ? __('Save changes') : __('Create task') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </x-button>
            </div>
        </form>

        {{-- ============ Side panel ============ --}}
        <aside class="space-y-5">
            @if ($task)
                <section class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Summary') }}</h3>
                    <div class="mt-4 flex items-center gap-3">
                        <x-avatar :user="$task->employee->user" size="md" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $task->employee->user->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $task->employee->job_title }} · {{ $task->employee->department->name }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 divide-y divide-slate-100 text-sm">
                        <div class="flex items-center justify-between py-2"><dt class="text-slate-500">{{ __('Status') }}</dt><dd><x-status-badge :status="$task->status" /></dd></div>
                        <div class="flex items-center justify-between py-2"><dt class="text-slate-500">{{ __('Priority') }}</dt><dd><x-priority-badge :priority="$task->priority" /></dd></div>
                        <div class="flex justify-between py-2"><dt class="text-slate-500">{{ __('Due date') }}</dt><dd class="font-medium text-slate-800">{{ $task->due_date?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-slate-500">{{ __('Created') }}</dt><dd class="font-medium text-slate-800">{{ $task->created_at->format('d/m/Y H:i') }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-slate-500">{{ __('Last updated') }}</dt><dd class="font-medium text-slate-800">{{ $task->updated_at->diffForHumans() }}</dd></div>
                        @if ($task->reviewer)
                            <div class="flex justify-between py-2"><dt class="text-slate-500">{{ __('Reviewed by') }}</dt><dd class="font-medium text-slate-800">{{ $task->reviewer->name }}</dd></div>
                        @endif
                    </dl>
                    <a href="{{ route('tasks.show', $task) }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('View details') }} →</a>
                </section>

                <section class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Recent activity') }}</h3>
                    <ol class="mt-4 space-y-4 border-s border-slate-200 ps-4">
                        @forelse ($task->histories->take(4) as $h)
                            <li class="relative">
                                <span class="absolute -start-[21px] mt-1.5 h-2.5 w-2.5 rounded-full bg-indigo-400 ring-4 ring-white"></span>
                                <p class="text-sm text-slate-800"><span class="font-medium">{{ $h->action->label() }}</span> {{ __('by') }} {{ $h->actor?->name ?? __('unknown user') }}</p>
                                <p class="text-xs text-slate-400">{{ $h->created_at->format('d/m/Y H:i') }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-slate-400">{{ __('No activity yet.') }}</li>
                        @endforelse
                    </ol>
                </section>

                @can('delete', $task)
                    @if ($me->isSuperAdmin() || in_array($task->status, [\App\Enums\TaskStatus::Pending, \App\Enums\TaskStatus::Cancelled], true))
                        <section class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5">
                            <h3 class="text-sm font-semibold text-rose-800">{{ __('Danger zone') }}</h3>
                            <p class="mt-1 text-xs text-rose-700/80">{{ __('Deleting a task also removes its history. This cannot be undone.') }}</p>
                            <x-button :href="route('tasks.confirm', [$task, 'delete'])" variant="danger" size="sm" icon="trash" class="mt-3">{{ __('Delete task') }}</x-button>
                        </section>
                    @endif
                @endcan
            @else
                <section class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Assigned to') }}</h3>
                    <div class="mt-4 flex items-center gap-3">
                        @if ($isAdmin)
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0"/></svg>
                            </span>
                            <p class="text-sm text-slate-600">{{ __('The employee you choose on the left.') }}</p>
                        @else
                            <x-avatar :user="$me" size="md" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $me->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ __('You') }}@if ($me->employee) · {{ $me->employee->department->name }}@endif</p>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="card p-5">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('What happens next') }}</h3>
                    <ol class="mt-4 space-y-4 text-sm">
                        @foreach ([
                            ['Pending', 'You can still edit or cancel it.', 'bg-slate-400'],
                            ['In progress', 'Start working on it.', 'bg-blue-500'],
                            ['Completed', 'Send it to your manager for approval.', 'bg-amber-500'],
                            ['Approved', 'Your manager approves it, or rejects it with a reason.', 'bg-emerald-500'],
                        ] as [$step, $text, $dot])
                            <li class="flex gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>
                                <div><p class="font-medium text-slate-800">{{ __($step) }}</p><p class="text-xs text-slate-500">{{ __($text) }}</p></div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </aside>
    </div>
</div>
