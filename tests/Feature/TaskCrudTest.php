<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Livewire\Tasks\TaskForm;
use App\Livewire\Tasks\TaskList;
use App\Livewire\Tasks\TaskShow;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Create / Read / Update / Delete for tasks, for every role that may do it. */
class TaskCrudTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        $this->admin = new User(['name' => 'Root Admin', 'username' => 'root', 'email' => 'root@x.test', 'password' => 'Secret123!']);
        $this->admin->role = UserRole::SuperAdmin;
        $this->admin->save();
    }

    // ---- DELETE: owners ---------------------------------------------------------------

    public function test_owner_can_delete_their_pending_or_cancelled_task(): void
    {
        $pending = $this->taskFor($this->employeeEmp);
        $cancelled = $this->taskFor($this->employeeEmp, 'cancelled');

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine']);
        $page->call('deleteTask', $pending->id)->assertDispatched('notify', type: 'success', message: 'Task deleted.');
        $page->call('deleteTask', $cancelled->id)->assertDispatched('notify', type: 'success', message: 'Task deleted.');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_deleting_a_task_removes_its_history(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        app(\App\Services\TaskWorkflowService::class)->update($task, $this->employee, ['title' => 'Edited', 'priority' => 'low']);
        $this->assertDatabaseCount('task_histories', 1);

        Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('deleteTask', $task->id);

        $this->assertDatabaseCount('task_histories', 0);
    }

    public function test_owner_cannot_delete_tasks_that_were_started_or_reviewed(): void
    {
        $started = $this->taskFor($this->employeeEmp, 'inProgress');
        $completed = $this->taskFor($this->employeeEmp, 'completed');
        $approved = $this->taskFor($this->employeeEmp, 'approved');

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine']);
        foreach ([$started, $completed, $approved] as $task) {
            $page->call('deleteTask', $task->id)->assertDispatched('notify', type: 'error');
        }

        $this->assertDatabaseCount('tasks', 3);
    }

    public function test_nobody_can_delete_someone_elses_task(): void
    {
        $task = $this->taskFor($this->teammateEmp);

        foreach ([$this->employee, $this->manager, $this->hr] as $user) {
            Livewire::actingAs($user)->test(TaskList::class, ['scope' => $user->isHr() ? 'all' : ($user->isManager() ? 'team' : 'mine')])
                ->call('deleteTask', $task->id)
                ->assertDispatched('notify', type: 'error', message: 'You can only delete your own tasks.');
        }

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_detail_page_delete_redirects_back_to_the_list(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        Livewire::actingAs($this->employee)->test(TaskShow::class, ['task' => $task])
            ->call('deleteTask', $task->id)
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    // ---- super admin: full CRUD on any task -----------------------------------------------

    public function test_super_admin_can_create_a_task_for_any_employee(): void
    {
        Livewire::actingAs($this->admin)->test(TaskForm::class)
            ->dispatch('open-task-form')
            ->set('employee_id', (string) $this->employeeEmp->id)
            ->set('title', 'Assigned by admin')->set('priority', 'high')
            ->call('save')->assertHasNoErrors();

        $task = Task::firstOrFail();
        $this->assertSame($this->employeeEmp->id, $task->employee_id);
        $this->assertSame(TaskStatus::Pending, $task->status);
    }

    public function test_super_admin_must_choose_an_employee(): void
    {
        Livewire::actingAs($this->admin)->test(TaskForm::class)
            ->dispatch('open-task-form')
            ->set('title', 'Orphan task')->set('priority', 'low')
            ->call('save')->assertHasErrors(['employee_id' => 'required']);
    }

    public function test_super_admin_can_edit_and_delete_a_task_in_any_status(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'approved');

        Livewire::actingAs($this->admin)->test(TaskForm::class)
            ->dispatch('open-task-form', taskId: $task->id)
            ->set('title', 'Corrected by admin')->call('save')->assertHasNoErrors();
        $this->assertSame('Corrected by admin', $task->fresh()->title);

        Livewire::actingAs($this->admin)->test(TaskList::class, ['scope' => 'all'])
            ->call('deleteTask', $task->id)->assertDispatched('notify', type: 'success', message: 'Task deleted.');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_regular_users_cannot_pick_the_owner_of_a_task(): void
    {
        Livewire::actingAs($this->employee)->test(TaskForm::class)
            ->dispatch('open-task-form')
            ->set('employee_id', (string) $this->teammateEmp->id)
            ->set('title', 'Mine really')->set('priority', 'low')
            ->call('save')->assertHasNoErrors();

        $this->assertSame($this->employeeEmp->id, Task::firstOrFail()->employee_id);
    }

    // ---- API ----------------------------------------------------------------------------------

    public function test_api_owner_can_delete_pending_task_and_gets_204(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        Sanctum::actingAs($this->employee);

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_api_delete_rules(): void
    {
        $started = $this->taskFor($this->employeeEmp, 'inProgress');
        $others = $this->taskFor($this->teammateEmp);

        Sanctum::actingAs($this->employee);
        $this->deleteJson("/api/tasks/{$started->id}")->assertUnprocessable();
        $this->deleteJson("/api/tasks/{$others->id}")->assertForbidden();
        $this->deleteJson('/api/tasks/99999')->assertNotFound();

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/tasks/{$started->id}")->assertNoContent();
    }

    public function test_api_unauthenticated_delete_is_rejected(): void
    {
        $this->deleteJson('/api/tasks/1')->assertUnauthorized();
    }

    public function test_api_super_admin_creates_for_an_employee_and_must_name_one(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/tasks', $this->validTask())->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        $this->postJson('/api/tasks', $this->validTask(['employee_id' => $this->employeeEmp->id]))
            ->assertCreated()->assertJsonPath('data.employee.id', $this->employeeEmp->id);
    }

    // ---- presentation ---------------------------------------------------------------------------

    public function test_dates_are_displayed_as_dd_mm_yyyy(): void
    {
        $task = Task::factory()->forEmployee($this->employeeEmp)->create(['due_date' => '2027-03-09']);

        $this->actingAs($this->employee)->get($this->p($this->employee, "/tasks/{$task->id}"))->assertOk()->assertSee('09/03/2027');
        Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->assertSee('09/03/2027')->assertDontSee('Mar 9');
    }

    public function test_sample_data_is_english_not_lorem_ipsum(): void
    {
        $titles = Task::factory()->count(30)->forEmployee($this->employeeEmp)->create()->pluck('title');

        foreach ($titles as $title) {
            $this->assertDoesNotMatchRegularExpression('/\b(lorem|ipsum|quia|voluptas|dolor|sit amet|consequatur|et|quo)\b/i', $title);
        }
    }

    public function test_filters_render_searchable_selects_and_the_date_picker(): void
    {
        $this->actingAs($this->hr)->get($this->p($this->hr, '/all-tasks'))->assertOk()
            ->assertSee('Type to search…')
            ->assertSee('dd/mm/yyyy', false);
    }
}
