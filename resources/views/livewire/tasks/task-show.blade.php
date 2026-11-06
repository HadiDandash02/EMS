<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ url()->previous() === url()->current() ? route('tasks.index') : url()->previous() }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Back') }}
    </a>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 p-6">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold text-slate-900">{{ $task->title }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$task->status" />
                    <x-priority-badge :priority="$task->priority" />
                </div>
            </div>
            @include('livewire.tasks.partials.actions', ['task' => $task])
        </div>

        <div class="space-y-6 p-6">
            @if ($task->rejection_reason && $task->status === \App\Enums\TaskStatus::Rejected)
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                    <p class="text-sm font-semibold text-rose-800">{{ __('Rejected') }}{{ $task->reviewer ? ' '.__('by').' '.$task->reviewer->name : '' }}</p>
                    <p class="mt-1 text-sm text-rose-700">{{ $task->rejection_reason }}</p>
                </div>
            @endif

            <div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Description') }}</h3>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $task->description ?: __('No description provided.') }}</p>
            </div>

            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <x-avatar :user="$task->employee->user" size="md" />
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ __('Assigned to') }}</p>
                    <p class="text-sm font-medium text-slate-900">{{ $task->employee->user->name }} <span class="font-normal text-slate-400">· {{ $task->employee->job_title }}</span></p>
                </div>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    __('Department') => $task->employee->department->name,
                    __('Manager') => $task->employee->manager?->user->name ?? '—',
                    __('Due date') => $task->due_date?->format('d/m/Y') ?? '—',
                    __('Created') => $task->created_at->format('d/m/Y H:i'),
                    __('Completed') => $task->completed_at?->format('d/m/Y H:i') ?? '—',
                    __('Reviewed by') => $task->reviewer?->name ?? '—',
                    __('Reviewed at') => $task->reviewed_at?->format('d/m/Y H:i') ?? '—',
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    {{-- History timeline --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('History') }}</h3>
        <ol class="mt-4 space-y-5 border-s border-slate-200 ps-6">
            @foreach ($task->histories as $h)
                <li class="relative" wire:key="h-{{ $h->id }}">
                    <span class="absolute -start-[31px] mt-1 h-3 w-3 rounded-full ring-4 ring-white {{ match($h->action->value) { 'approved' => 'bg-emerald-500', 'rejected' => 'bg-rose-500', 'cancelled' => 'bg-zinc-400', 'completed' => 'bg-amber-500', default => 'bg-indigo-400' } }}"></span>
                    <p class="text-sm text-slate-800">
                        <span class="font-medium">{{ $h->action->label() }}</span>
                        {{ __('by') }} {{ $h->actor?->name ?? __('unknown user') }}
                        @if ($h->from_status && $h->from_status !== $h->to_status)
                            <span class="text-slate-400">· {{ $h->from_status->label() }} → {{ $h->to_status->label() }}</span>
                        @endif
                    </p>
                    @if ($h->reason)<p class="mt-1 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-600">“{{ $h->reason }}”</p>@endif
                    <p class="mt-0.5 text-xs text-slate-400">{{ $h->created_at->format('d/m/Y H:i') }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</div>
