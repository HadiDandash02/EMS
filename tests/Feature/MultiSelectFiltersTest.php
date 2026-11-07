<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\UserManager;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Tasks\TaskList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Every filter dropdown is a multi-select: pick as many values as you like. */
class MultiSelectFiltersTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_every_filter_select_is_multiple_and_scrolls(): void
    {
        foreach (['tasks/task-list', 'admin/user-manager', 'approvals/approval-history', 'employees/employee-list', 'admin/department-manager'] as $view) {
            preg_match_all('/<x-searchable-select[^>]*>/', file_get_contents(resource_path("views/livewire/{$view}.blade.php")), $m);
            foreach ($m[0] as $tag) {
                $this->assertStringContainsString(' multiple ', $tag, "{$view}: filter must allow several values");
            }
        }

        $select = file_get_contents(resource_path('views/components/searchable-select.blade.php'));
        $this->assertStringContainsString('overflow-y-scroll', $select);   // the list always has its own scrollbar
        $this->assertStringContainsString('select-scroll', $select);
    }

    public function test_tasks_can_be_filtered_by_several_statuses_priorities_employees_and_tasks_at_once(): void
    {
        $pending = Task::factory()->forEmployee($this->employeeEmp)->pending()->create(['title' => 'P one', 'priority' => 'low']);
        $done = Task::factory()->forEmployee($this->teammateEmp)->completed()->create(['title' => 'C two', 'priority' => 'high']);
        $other = Task::factory()->forEmployee($this->outsiderEmp)->inProgress()->create(['title' => 'I three', 'priority' => 'medium']);

        $rows = fn ($list) => $list->viewData('tasks')->pluck('title')->sort()->values()->all();
        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);

        $list->set('status', ['PENDING', 'COMPLETED']);
        $this->assertSame(['C two', 'P one'], $rows($list));

        $list->set('status', [])->set('priority', ['low', 'medium']);
        $this->assertSame(['I three', 'P one'], $rows($list));

        $list->set('priority', [])->set('employeeId', [(string) $this->employeeEmp->id, (string) $this->outsiderEmp->id]);
        $this->assertSame(['I three', 'P one'], $rows($list));

        $list->set('employeeId', [])->set('taskId', [(string) $done->id, (string) $other->id]);
        $this->assertSame(['C two', 'I three'], $rows($list));

        $list->assertSet('searched', true);
    }

    public function test_a_single_value_and_junk_values_are_still_handled_safely(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->pending()->create(['title' => 'Only pending']);
        Task::factory()->forEmployee($this->employeeEmp)->completed()->create(['title' => 'Only completed']);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->set('status', 'PENDING');
        $this->assertSame(['Only pending'], $list->viewData('tasks')->pluck('title')->all());

        // unknown values are ignored instead of breaking the query
        $list->set('status', ['NOPE', "' OR 1=1 --"]);
        $this->assertCount(2, $list->viewData('tasks'));
    }

    public function test_users_can_be_filtered_by_several_roles_at_once(): void
    {
        $admin = new User(['name' => 'Root Admin', 'username' => 'root', 'email' => 'root@x.test', 'password' => 'Secret123!']);
        $admin->role = UserRole::SuperAdmin;
        $admin->save();

        $list = Livewire::actingAs($admin)->test(UserManager::class)->set('roleFilter', ['hr', 'manager']);

        $roles = $list->viewData('users')->pluck('role')->map(fn ($r) => $r->value)->unique()->sort()->values()->all();
        $this->assertSame(['hr', 'manager'], $roles);
    }

    public function test_employees_can_be_filtered_by_several_departments(): void
    {
        $list = Livewire::actingAs($this->hr)->test(EmployeeList::class)
            ->set('departmentId', [(string) $this->dept->id, (string) $this->outsiderEmp->department_id]);

        $this->assertSame(\App\Models\Employee::count(), $list->viewData('employees')->total());
    }
}
