<x-page :title="$task->title" :crumbs="[[__('Tasks'), $task->listUrlFor(auth()->user())], [__('Task').' #'.$task->id, null]]">
    <x-slot:meta>
        <x-status-badge :status="$task->status" />
        <x-priority-badge :priority="$task->priority" />
    </x-slot:meta>
    <x-slot:actions>@include('livewire.tasks.partials.actions', ['task' => $task])</x-slot:actions>

    @if ($task->rejection_reason && $task->status === \App\Enums\TaskStatus::Rejected)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-sm font-semibold text-rose-800">{{ __('Rejected') }}{{ $task->reviewer ? ' '.__('by').' '.$task->reviewer->name : '' }}</p>
            <p class="mt-1 text-sm text-rose-700">{{ $task->rejection_reason }}</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card :title="__('Description')">
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $task->description ?: __('No description provided.') }}</p>
            </x-card>

            <x-card :title="__('History')" :hint="__('Everything that happened to this task, newest last.')">
                <ol class="space-y-5 border-s border-slate-200 ps-6">
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
            </x-card>
        </div>

        <aside>@include('livewire.tasks.partials.summary', ['task' => $task])</aside>
    </div>
</x-page>
