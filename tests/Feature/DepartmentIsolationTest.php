<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\UserForm;
use App\Livewire\Approvals\ApprovalHistory;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Tasks\TaskList;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/**
 * Managers and HR work ONLY inside their own department - in both directions: nobody can reach another
 * department, and nobody from another department can reach theirs. The super admin is not confined.
 *
 * BuildsOrganisation: department "Engineering" has hr, manager, employee, teammate; a second department has
 * otherManager and outsider. Here a third department "Human Resources" is added with its own HR user.
 */
class DepartmentIsolationTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private User $admin;

    private Employee $hrDeptHrEmp;

    private Employee $hrDeptStaffEmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        $this->admin = new User(['name' => 'Root Admin', 'username' => 'root', 'email' => 'root@x.test', 'password' => 'Secret123!']);
        $this->admin->role = UserRole::SuperAdmin;
        $this->admin->save();

        $hrDept = Department::factory()->create(['name' => 'Human Resources', 'code' => 'HR']);
        $this->hrDeptHrEmp = Employee::factory()->hr()->create(['department_id' => $hrDept->id]);
        $this->hrDeptStaffEmp = Employee::factory()->create(['department_id' => $hrDept->id]);
    }

    private function rows($list): array
    {
        return $list->viewData('tasks')->pluck('id')->sort()->values()->all();
    }

    // ---- HR ------------------------------------------------------------------------------------------

    public function test_hr_sees_everything_in_their_department_and_nothing_in_another(): void
    {
        $mine = $this->taskFor($this->employeeEmp);
        $teammate = $this->taskFor($this->teammateEmp);
        $other = $this->taskFor($this->outsiderEmp);
        $hrDeptTask = $this->taskFor($this->hrDeptStaffEmp);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);
        $this->assertSame([$mine->id, $teammate->id], $this->rows($list));

        // details: own department yes, other departments (including the HR department) -> 403
        foreach ([$mine, $teammate] as $task) {
            $this->actingAs($this->hr)->get($this->p($this->hr, "/tasks/{$task->id}"))->assertOk();
        }
        foreach ([$other, $hrDeptTask] as $task) {
            $this->actingAs($this->hr)->get($this->p($this->hr, "/tasks/{$task->id}"))->assertForbidden();
        }
    }

    public function test_the_hr_department_is_isolated_from_the_others_and_vice_versa(): void
    {
        $engineering = $this->taskFor($this->employeeEmp);
        $hrDeptTask = $this->taskFor($this->hrDeptStaffEmp);
        $hrOfHrDept = $this->hrDeptHrEmp->user;

        // the HR user of the HR department: only the HR department
        $list = Livewire::actingAs($hrOfHrDept)->test(TaskList::class, ['scope' => 'all']);
        $this->assertSame([$hrDeptTask->id], $this->rows($list));
        $this->actingAs($hrOfHrDept)->get($this->p($hrOfHrDept, "/tasks/{$engineering->id}"))->assertForbidden();

        // ... and Engineering's HR cannot see the HR department either
        $this->actingAs($this->hr)->get($this->p($this->hr, "/tasks/{$hrDeptTask->id}"))->assertForbidden();
    }

    public function test_hr_employee_list_and_filters_never_show_another_department(): void
    {
        $list = Livewire::actingAs($this->hr)->test(EmployeeList::class);
        $names = $list->viewData('employees')->pluck('id')->all();

        $this->assertContains($this->employeeEmp->id, $names);
        $this->assertNotContains($this->outsiderEmp->id, $names);
        $this->assertNotContains($this->hrDeptStaffEmp->id, $names);
        $this->assertCount(1, $list->viewData('departments'));                    // the department filter offers only their own

        // asking for another department by hand changes nothing
        $list->set('departmentId', [(string) $this->outsiderEmp->department_id]);
        $this->assertSame(0, $list->viewData('employees')->total());
    }

    public function test_hr_approval_history_only_lists_decisions_of_their_department(): void
    {
        $mine = $this->taskFor($this->employeeEmp, 'completed');
        $other = $this->taskFor($this->outsiderEmp, 'completed');
        app(TaskWorkflowService::class)->approve($mine, $this->manager);
        app(TaskWorkflowService::class)->approve($other, $this->otherManager);

        $records = Livewire::actingAs($this->hr)->test(ApprovalHistory::class)->viewData('records');

        $this->assertSame([$mine->id], $records->pluck('task_id')->all());
    }

    // ---- manager ---------------------------------------------------------------------------------------

    public function test_a_manager_cannot_view_or_decide_on_another_department(): void
    {
        $other = $this->taskFor($this->outsiderEmp, 'completed');
        $hrDeptTask = $this->taskFor($this->hrDeptStaffEmp, 'completed');

        foreach ([$other, $hrDeptTask] as $task) {
            $this->actingAs($this->manager)->get($this->p($this->manager, "/tasks/{$task->id}"))->assertForbidden();
            $this->actingAs($this->manager)->get($this->p($this->manager, "/tasks/{$task->id}/confirm/approve"))->assertForbidden();
        }
    }

    public function test_a_team_never_crosses_the_department_border_even_if_the_manager_link_does(): void
    {
        // a person in ANOTHER department whose manager_id points at our manager (bad data or a transfer)
        $strayEmp = Employee::factory()->create(['department_id' => $this->outsiderEmp->department_id, 'manager_id' => $this->managerEmp->id]);
        $stray = $this->taskFor($strayEmp, 'completed');

        $this->actingAs($this->manager)->get($this->p($this->manager, "/tasks/{$stray->id}"))->assertForbidden();
        $list = Livewire::actingAs($this->manager)->test(TaskList::class, ['scope' => 'team']);
        $this->assertNotContains($stray->id, $this->rows($list));

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(TaskWorkflowService::class)->approve($stray, $this->manager);
    }

    public function test_the_api_is_confined_to_the_department_too(): void
    {
        $mine = $this->taskFor($this->employeeEmp);
        $other = $this->taskFor($this->outsiderEmp);

        Sanctum::actingAs($this->hr);
        $this->getJson('/api/tasks')->assertJsonPath('meta.total', 1);
        $this->getJson("/api/tasks/{$mine->id}")->assertOk();
        $this->getJson("/api/tasks/{$other->id}")->assertForbidden();
        $this->getJson('/api/tasks?department_id='.$other->employee->department_id)->assertJsonPath('meta.total', 0);

        Sanctum::actingAs($this->manager);
        $this->getJson("/api/tasks/{$other->id}")->assertForbidden();
        $this->postJson("/api/tasks/{$other->id}/approve")->assertForbidden();
    }

    // ---- super admin -----------------------------------------------------------------------------------

    public function test_the_super_admin_is_not_confined(): void
    {
        $this->taskFor($this->employeeEmp);
        $this->taskFor($this->outsiderEmp);
        $this->taskFor($this->hrDeptStaffEmp);

        $list = Livewire::actingAs($this->admin)->test(TaskList::class, ['scope' => 'all']);
        $this->assertCount(3, $this->rows($list));
        $this->assertGreaterThanOrEqual(3, Livewire::actingAs($this->admin)->test(EmployeeList::class)->viewData('departments')->count());
    }

    public function test_the_super_admin_can_make_a_manager_and_an_hr_user_in_any_department(): void
    {
        $passwords = 'Str0ng!Passw0rd';

        foreach (Department::all() as $i => $department) {
            foreach (['manager', 'hr'] as $role) {
                Livewire::actingAs($this->admin)->test(UserForm::class)
                    ->set('name', ucfirst($role)." of {$department->code}")
                    ->set('username', "{$role}{$i}x")
                    ->set('email', "{$role}{$i}x@x.test")
                    ->set('role', $role)
                    ->set('department_id', (string) $department->id)
                    ->set('job_title', ucfirst($role))
                    ->set('employee_number', strtoupper("E{$i}-{$role}"))
                    ->set('password', $passwords)
                    ->call('save')->assertHasNoErrors();

                $user = User::where('username', "{$role}{$i}x")->firstOrFail();
                $this->assertSame(UserRole::from($role), $user->role);
                $this->assertSame($department->id, $user->employee->department_id);
            }
        }

        // any existing user can be promoted to manager or HR
        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->teammate])->set('role', 'hr')->call('save')->assertHasNoErrors();
        $this->assertSame(UserRole::Hr, $this->teammate->fresh()->role);

        // and a freshly created HR user is confined to the department they were created in
        $newHr = User::where('username', 'hr0x')->firstOrFail();
        $this->assertSame($newHr->employee->department_id, $newHr->confinedDepartmentId());
        $this->assertNull($this->admin->confinedDepartmentId());
    }
    public function test_the_company_wide_switch_gives_hr_every_department_exactly_as_the_brief_words_it(): void
    {
        $mine = $this->taskFor($this->employeeEmp, 'completed');
        $other = $this->taskFor($this->outsiderEmp, 'completed');
        app(TaskWorkflowService::class)->approve($other, $this->otherManager);

        // default: own department only
        $this->assertSame([$mine->id], $this->rows(Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])));
        $this->actingAs($this->hr)->get($this->p($this->hr, "/tasks/{$other->id}"))->assertForbidden();

        // EMS_HR_SEES_ALL_DEPARTMENTS=true: all tasks, all employees, all decisions - and still read only
        config(['ems.hr_sees_all_departments' => true]);

        $this->assertSame([$mine->id, $other->id], $this->rows(Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])));
        $this->actingAs($this->hr)->get($this->p($this->hr, "/tasks/{$other->id}"))->assertOk();
        $this->assertSame(\App\Models\Employee::count(), Livewire::actingAs($this->hr)->test(EmployeeList::class)->viewData('employees')->total());
        $this->assertCount(1, Livewire::actingAs($this->hr)->test(ApprovalHistory::class)->viewData('records')->items());

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);   // HR still cannot approve
        app(TaskWorkflowService::class)->approve($this->taskFor($this->employeeEmp, 'completed'), $this->hr);
    }
}
