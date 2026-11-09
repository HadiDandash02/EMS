<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\DepartmentManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Approvals\ApprovalHistory;
use App\Livewire\Employees\EmployeeList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Every list filters the same way: a searchable select named after what it lists (Task / Employee / User / Department). */
class ConsistentFiltersTest extends TestCase
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

    public function test_no_list_has_a_free_text_search_box_any_more(): void
    {
        foreach ([[$this->hr, '/hr/all-tasks'], [$this->hr, '/hr/employees'], [$this->hr, '/hr/approvals'], [$this->admin, '/admin/users'], [$this->admin, '/admin/departments']] as [$user, $url]) {
            $this->actingAs($user)->get($url)->assertOk()->assertDontSee('id="fb-search"', false)->assertSee('Type to search…');
        }
    }

    public function test_employee_picker_lists_everyone_and_filters_to_one_employee(): void
    {
        $list = Livewire::actingAs($this->hr)->test(EmployeeList::class);

        $options = $list->viewData('employeeOptions');
        $this->assertCount(\App\Models\Employee::where('department_id', $this->dept->id)->count(), $options);   // HR: own department only
        $this->assertNotContains($this->outsiderEmp->user->name.' · '.$this->outsiderEmp->employee_number, $options->all());

        $list->set('employeeId', (string) $this->managerEmp->id)->assertSet('searched', true);
        $this->assertSame([$this->managerEmp->id], $list->viewData('employees')->pluck('id')->all());
    }

    public function test_user_picker_filters_to_one_user(): void
    {
        $list = Livewire::actingAs($this->admin)->test(UserManager::class);

        $this->assertCount(User::count(), $list->viewData('userOptions'));

        $list->set('userId', (string) $this->hr->id);
        $this->assertSame([$this->hr->id], $list->viewData('users')->pluck('id')->all());
    }

    public function test_department_picker_filters_to_one_department(): void
    {
        $list = Livewire::actingAs($this->admin)->test(DepartmentManager::class);

        $list->set('departmentId', (string) $this->dept->id);
        $this->assertSame([$this->dept->id], $list->viewData('departments')->pluck('id')->all());
    }

    public function test_approval_task_picker_lists_only_tasks_with_a_decision_the_user_may_see(): void
    {
        $approved = $this->taskFor($this->employeeEmp, 'completed');
        app(\App\Services\TaskWorkflowService::class)->approve($approved, $this->manager);
        $undecided = $this->taskFor($this->employeeEmp, 'completed');

        $list = Livewire::actingAs($this->manager)->test(ApprovalHistory::class);

        $labels = $list->viewData('taskOptions')->keys()->all();
        $this->assertSame([$approved->id], $labels);
        $this->assertNotContains($undecided->id, $labels);

        $list->set('taskId', (string) $approved->id);
        $this->assertCount(1, $list->viewData('records')->items());
    }
}
