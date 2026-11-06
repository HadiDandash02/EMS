<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TaskList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $this->get('/tasks')->assertRedirect('/login');
        $this->get("/tasks/{$task->id}")->assertRedirect('/login');
    }

    public function test_employee_can_view_own_task(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $this->actingAs($this->employee)->get("/tasks/{$task->id}")->assertOk()->assertSee($task->title);
    }

    public function test_employee_cannot_access_another_employees_task_by_changing_the_id(): void
    {
        $others = $this->taskFor($this->teammateEmp);

        $this->actingAs($this->employee)->get("/tasks/{$others->id}")->assertForbidden();
    }

    public function test_employee_cannot_modify_another_employees_task_through_livewire(): void
    {
        $others = $this->taskFor($this->teammateEmp);

        Livewire::actingAs($this->employee)
            ->test(TaskList::class, ['scope' => 'mine'])
            ->call('cancelTask', $others->id)
            ->assertDispatched('notify', type: 'error', message: 'You can only cancel your own tasks.');

        $this->assertSame('PENDING', $others->fresh()->status->value);
    }

    public function test_employee_task_list_only_contains_their_own_tasks(): void
    {
        $mine = $this->taskFor($this->employeeEmp);
        $theirs = $this->taskFor($this->teammateEmp);

        Livewire::actingAs($this->employee)
            ->test(TaskList::class, ['scope' => 'mine'])
            ->assertSee($mine->title)
            ->assertDontSee($theirs->title);
    }

    public function test_manager_can_view_team_members_task_but_not_outsiders(): void
    {
        $team = $this->taskFor($this->employeeEmp);
        $outside = $this->taskFor($this->outsiderEmp);

        $this->actingAs($this->manager)->get("/tasks/{$team->id}")->assertOk();
        $this->actingAs($this->manager)->get("/tasks/{$outside->id}")->assertForbidden();
    }

    public function test_hr_can_view_any_task(): void
    {
        $task = $this->taskFor($this->outsiderEmp);

        $this->actingAs($this->hr)->get("/tasks/{$task->id}")->assertOk();
    }

    public function test_role_protected_areas_reject_other_roles(): void
    {
        $this->actingAs($this->employee)->get('/team/tasks')->assertForbidden();
        $this->actingAs($this->employee)->get('/hr/tasks')->assertForbidden();
        $this->actingAs($this->employee)->get('/hr/employees')->assertForbidden();
        $this->actingAs($this->employee)->get('/approvals')->assertForbidden();
        $this->actingAs($this->manager)->get('/hr/tasks')->assertForbidden();
        $this->actingAs($this->hr)->get('/team/tasks')->assertForbidden();
    }

    public function test_list_scope_cannot_be_tampered_with_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($this->employee)
            ->test(TaskList::class, ['scope' => 'mine'])
            ->set('scope', 'all');
    }

    public function test_employee_cannot_open_the_hr_scope_even_by_mounting_it_directly(): void
    {
        Livewire::actingAs($this->employee)
            ->test(TaskList::class, ['scope' => 'all'])
            ->assertForbidden();
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = \App\Models\User::create(['name' => 'Eve', 'email' => 'eve@x.test', 'password' => 'secret123', 'role' => 'hr']);

        $this->assertSame('employee', $user->fresh()->role->value);
    }
}
