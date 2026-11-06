<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListTasksRequest;
use App\Http\Requests\Api\RejectTaskRequest;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Deliberately thin: validation lives in Form Requests, authorisation in TaskPolicy,
 * business rules + transactions in TaskWorkflowService.
 */
class TaskController extends Controller
{
    public function __construct(private readonly TaskWorkflowService $workflow) {}

    public function index(ListTasksRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $tasks = Task::query()
            ->with(['employee.user', 'employee.department', 'reviewer'])
            ->visibleTo($request->user())           // never returns rows the user may not see
            ->filter($filters)
            ->latest('tasks.created_at')->latest('tasks.id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    public function show(Request $request, Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task->load(['employee.user', 'employee.department', 'reviewer', 'histories.actor']));
    }

    public function store(StoreTaskRequest $request): \Illuminate\Http\JsonResponse
    {
        $task = $this->workflow->create($request->user(), $request->validated());

        return (new TaskResource($this->fresh($task)))->response()->setStatusCode(201);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        return new TaskResource($this->fresh($this->workflow->update($task, $request->user(), $request->validated())));
    }

    public function start(Request $request, Task $task): TaskResource
    {
        return new TaskResource($this->fresh($this->workflow->start($task, $request->user())));
    }

    public function complete(Request $request, Task $task): TaskResource
    {
        return new TaskResource($this->fresh($this->workflow->complete($task, $request->user())));
    }

    public function cancel(Request $request, Task $task): TaskResource
    {
        return new TaskResource($this->fresh($this->workflow->cancel($task, $request->user())));
    }

    public function approve(Request $request, Task $task): TaskResource
    {
        return new TaskResource($this->fresh($this->workflow->approve($task, $request->user())));
    }

    public function reject(RejectTaskRequest $request, Task $task): TaskResource
    {
        return new TaskResource($this->fresh($this->workflow->reject($task, $request->user(), $request->validated('reason'))));
    }

    private function fresh(Task $task): Task
    {
        return $task->load(['employee.user', 'employee.department', 'reviewer', 'histories.actor']);
    }
}
