<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ url()->previous() === url()->current() ? route('tasks.index') : url()->previous() }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">← Back</a>

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
                    <p class="text-sm font-semibold text-rose-800">Rejected{{ $task->reviewer ? ' by '.$task->reviewer->name : '' }}</p>
                    <p class="mt-1 text-sm text-rose-700">{{ $task->rejection_reason }}</p>
                </div>
            @endif

            <div>
                <h3 class="text-sm font-semibold text-slate-900">Description</h3>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $task->description ?: 'No description provided.' }}</p>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Employee' => $task->employee->user->name,
                    'Department' => $task->employee->department->name,
                    'Manager' => $task->employee->manager?->user->name ?? '—',
                    'Due date' => $task->due_date?->format('M j, Y') ?? '—',
                    'Created' => $task->created_at->format('M j, Y H:i'),
                    'Completed' => $task->completed_at?->format('M j, Y H:i') ?? '—',
                    'Reviewed by' => $task->reviewer?->name ?? '—',
                    'Reviewed at' => $task->reviewed_at?->format('M j, Y H:i') ?? '—',
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
        <h3 class="text-sm font-semibold text-slate-900">History</h3>
        <ol class="mt-4 space-y-5 border-l border-slate-200 pl-6">
            @foreach ($task->histories as $h)
                <li class="relative" wire:key="h-{{ $h->id }}">
                    <span class="absolute -left-[31px] mt-1 h-3 w-3 rounded-full ring-4 ring-white {{ match($h->action->value) { 'approved' => 'bg-emerald-500', 'rejected' => 'bg-rose-500', 'cancelled' => 'bg-zinc-400', 'completed' => 'bg-amber-500', default => 'bg-indigo-400' } }}"></span>
                    <p class="text-sm text-slate-800">
                        <span class="font-medium">{{ $h->action->label() }}</span>
                        by {{ $h->actor?->name ?? 'unknown user' }}
                        @if ($h->from_status && $h->from_status !== $h->to_status)
                            <span class="text-slate-400">· {{ $h->from_status->label() }} → {{ $h->to_status->label() }}</span>
                        @endif
                    </p>
                    @if ($h->reason)<p class="mt-1 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-600">“{{ $h->reason }}”</p>@endif
                    <p class="mt-0.5 text-xs text-slate-400">{{ $h->created_at->format('M j, Y H:i') }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</div>
