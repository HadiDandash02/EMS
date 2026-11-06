<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Tasks\TaskForm;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Creating and editing a task now happens on its own pages (no popups). */
class TaskCreationTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_the_create_and_edit_forms_are_their_own_pages(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $this->actingAs($this->employee)->get('/employee/tasks/create')->assertOk()->assertSee('Create task');
        $this->actingAs($this->employee)->get("/employee/tasks/{$task->id}/edit")->assertOk()->assertSee('Save changes');
    }

    public function test_employee_can_create_a_task(): void
    {
        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
            ->set('title', 'Prepare quarterly report')
            ->set('description', 'Collect numbers.')
            ->set('priority', 'high')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('tasks.index'));

        $task = Task::firstOrFail();
        $this->assertSame($this->employeeEmp->id, $task->employee_id);
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertSame('Task created.', session('success'));
        $this->assertDatabaseHas('task_histories', ['task_id' => $task->id, 'action' => 'created', 'actor_id' => $this->employee->id]);
    }

    public function test_invalid_task_data_is_rejected(): void
    {
        Livewire::actingAs($this->employee)
            ->test(TaskForm::class)
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
            ->call('save')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_employee_can_edit_own_pending_task(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        Livewire::actingAs($this->employee)
            ->test(TaskForm::class, ['task' => $task])
            ->assertSet('title', $task->title)           // the form is pre-filled
            ->set('title', 'Renamed task')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('tasks.show', $task));

        $this->assertSame('Renamed task', $task->fresh()->title);
    }

    public function test_employee_cannot_edit_a_task_that_is_not_pending(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'inProgress');

        Livewire::actingAs($this->employee)
            ->test(TaskForm::class, ['task' => $task])
            ->set('title', 'Sneaky rename')
            ->call('save')
            ->assertRedirect();                           // refused: back to the list with an error message

        $this->assertNotSame('Sneaky rename', $task->fresh()->title);
        $this->assertStringContainsString('Only pending tasks can be edited', session('error'));
    }

    public function test_employee_cannot_open_the_edit_page_for_someone_elses_task(): void
    {
        $task = $this->taskFor($this->teammateEmp);

        Livewire::actingAs($this->employee)->test(TaskForm::class, ['task' => $task])->assertForbidden();
        $this->actingAs($this->employee)->get("/employee/tasks/{$task->id}/edit")->assertForbidden();
    }
}
