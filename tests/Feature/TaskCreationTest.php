<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Tasks\TaskForm;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class TaskCreationTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_employee_can_create_a_task(): void
    {
        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->dispatch('open-task-form')
            ->set('title', 'Prepare quarterly report')
            ->set('description', 'Collect numbers.')
            ->set('priority', 'high')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('task-saved');

        $task = Task::firstOrFail();
        $this->assertSame($this->employeeEmp->id, $task->employee_id);
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertDatabaseHas('task_histories', ['task_id' => $task->id, 'action' => 'created', 'actor_id' => $this->employee->id]);
    }

    public function test_invalid_task_data_is_rejected(): void
    {
        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->dispatch('open-task-form')
            ->set('title', 'ab')                       // too short
            ->set('priority', 'urgent')                // not a valid priority
            ->set('due_date', now()->subDay()->toDateString()) // in the past
            ->call('save')
            ->assertHasErrors(['title', 'priority', 'due_date']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_title_is_required(): void
    {
        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->dispatch('open-task-form')
            ->call('save')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_employee_can_edit_own_pending_task(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->dispatch('open-task-form', taskId: $task->id)
            ->set('title', 'Renamed task')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed task', $task->fresh()->title);
    }

    public function test_employee_cannot_edit_a_task_that_is_not_pending(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'inProgress');

        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->dispatch('open-task-form', taskId: $task->id)
            ->set('title', 'Sneaky rename')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertNotSame('Sneaky rename', $task->fresh()->title);
    }

    public function test_employee_cannot_open_the_edit_form_for_someone_elses_task(): void
    {
        $task = $this->taskFor($this->teammateEmp);

        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->dispatch('open-task-form', taskId: $task->id)
            ->assertForbidden();
    }
}
