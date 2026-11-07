<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\DepartmentManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Approvals\ApprovalHistory;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Tasks\TaskList;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class TableSortingExportTest extends TestCase
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

    /** @return list<string> the titles in the order the table shows them */
    private function titles($page): array
    {
        return collect($page->viewData('tasks')->items())->pluck('title')->all();
    }

    private function csv($page): array
    {
        $download = $page->effects['download'] ?? null;
        $this->assertNotNull($download, 'no file was downloaded');

        $text = base64_decode($download['content']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $text, 'missing UTF-8 BOM (Excel would garble Arabic)');

        return array_map('str_getcsv', array_filter(explode("\n", substr($text, 3))));
    }

    // ---- sorting -------------------------------------------------------------------------------------

    public function test_clicking_a_header_sorts_ascending_then_descending(): void
    {
        foreach (['Banana', 'Apple', 'Cherry'] as $t) {
            Task::factory()->forEmployee($this->employeeEmp)->create(['title' => $t]);
        }

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine']);

        $page->call('sortBy', 'title');
        $this->assertSame(['Apple', 'Banana', 'Cherry'], $this->titles($page));
        $page->assertSet('sortField', 'title')->assertSet('sortDirection', 'asc');

        $page->call('sortBy', 'title');
        $this->assertSame(['Cherry', 'Banana', 'Apple'], $this->titles($page));
        $page->assertSet('sortDirection', 'desc');

        $page->call('sortBy', 'title');                       // third click flips back
        $page->assertSet('sortDirection', 'asc');
    }

    public function test_sorting_a_new_column_starts_ascending_and_returns_to_page_one(): void
    {
        Task::factory()->count(15)->forEmployee($this->employeeEmp)->create();

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])
            ->call('sortBy', 'title')->call('sortBy', 'title')->call('gotoPage', 2);

        $page->call('sortBy', 'status')->assertSet('sortField', 'status')->assertSet('sortDirection', 'asc');
        $this->assertSame(1, $page->viewData('tasks')->currentPage());
    }

    public function test_status_and_priority_sort_by_their_logical_order_not_alphabetically(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'a-approved', 'status' => TaskStatus::Approved, 'priority' => 'low']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'b-pending', 'status' => TaskStatus::Pending, 'priority' => 'high']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'c-completed', 'status' => TaskStatus::Completed, 'priority' => 'medium']);

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('sortBy', 'status');
        $this->assertSame(['b-pending', 'c-completed', 'a-approved'], $this->titles($page));   // workflow order

        $page->call('sortBy', 'priority');
        $this->assertSame(['a-approved', 'c-completed', 'b-pending'], $this->titles($page));   // low < medium < high
    }

    public function test_tasks_without_a_due_date_always_sort_last(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'no-date', 'due_date' => null]);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'early', 'due_date' => now()->addDay()]);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'late', 'due_date' => now()->addMonth()]);

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('sortBy', 'due');
        $this->assertSame(['early', 'late', 'no-date'], $this->titles($page));

        $page->call('sortBy', 'due');                                                              // descending
        $this->assertSame(['late', 'early', 'no-date'], $this->titles($page));
    }

    public function test_tasks_can_be_sorted_by_the_employees_name(): void
    {
        $this->employee->forceFill(['name' => 'Zed Employee'])->save();
        $this->teammate->forceFill(['name' => 'Adam Teammate'])->save();
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'by-zed']);
        Task::factory()->forEmployee($this->teammateEmp)->create(['title' => 'by-adam']);

        $page = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->call('sortBy', 'employee');

        $this->assertSame(['by-adam', 'by-zed'], $this->titles($page));
    }

    public function test_unknown_sort_fields_from_the_browser_are_ignored(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create();

        Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])
            ->call('sortBy', 'password; DROP TABLE tasks')->assertSet('sortField', '')
            ->set('sortField', 'tasks.id; --')->assertOk();                                       // tampered value: harmless

        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_other_tables_sort_too(): void
    {
        Department::factory()->create(['name' => 'Zeta Dept', 'code' => 'ZZZ']);
        Department::factory()->create(['name' => 'Alpha Dept', 'code' => 'AAA']);

        $depts = Livewire::actingAs($this->admin)->test(DepartmentManager::class)->call('sortBy', 'code')
            ->viewData('departments')->pluck('code')->all();
        $sorted = $depts;
        sort($sorted);
        $this->assertSame($sorted, $depts);

        $users = Livewire::actingAs($this->admin)->test(UserManager::class)->call('sortBy', 'username')->call('sortBy', 'username')
            ->viewData('users')->pluck('username')->all();
        $desc = $users;
        rsort($desc);
        $this->assertSame($desc, $users);

        $employees = Livewire::actingAs($this->hr)->test(EmployeeList::class)->call('sortBy', 'tasks')->assertOk();
        $this->assertNotNull($employees->viewData('employees'));

        $this->taskFor($this->employeeEmp, 'completed');
        app(TaskWorkflowService::class)->approve(Task::first(), $this->manager);
        Livewire::actingAs($this->hr)->test(ApprovalHistory::class)->call('sortBy', 'date')->call('sortBy', 'task')->call('sortBy', 'by')->assertOk();
    }

    // ---- export ----------------------------------------------------------------------------------------

    public function test_the_task_export_is_a_csv_of_exactly_what_the_user_sees(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Mine one']);
        Task::factory()->forEmployee($this->teammateEmp)->create(['title' => 'Not mine']);

        $page = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('exportCsv');
        $page->assertFileDownloaded('tasks-'.now()->format('Y-m-d').'.csv');

        $rows = $this->csv($page);
        $this->assertSame('#', $rows[0][0]);
        $this->assertContains('Task', $rows[0]);
        $this->assertCount(2, $rows);                                       // header + the employee's only task
        $this->assertSame('Mine one', $rows[1][1]);
        $this->assertStringNotContainsString('Not mine', json_encode($rows));
    }

    public function test_the_export_respects_filters_and_sorting_across_all_pages(): void
    {
        Task::factory()->count(14)->forEmployee($this->employeeEmp)->create(['status' => TaskStatus::Pending]);
        Task::factory()->count(3)->forEmployee($this->employeeEmp)->create(['status' => TaskStatus::Completed]);

        $all = $this->csv(Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->call('exportCsv'));
        $this->assertCount(18, $all);                                       // 17 rows (more than one page of 10) + header

        $filtered = $this->csv(Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->set('status', 'COMPLETED')->call('exportCsv'));
        $this->assertCount(4, $filtered);
    }

    public function test_a_manager_can_only_export_their_teams_tasks(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Team task']);
        Task::factory()->forEmployee($this->outsiderEmp)->create(['title' => 'Other team']);

        $rows = $this->csv(Livewire::actingAs($this->manager)->test(TaskList::class, ['scope' => 'team'])->set('status', '')->call('exportCsv'));

        $this->assertStringContainsString('Team task', json_encode($rows));
        $this->assertStringNotContainsString('Other team', json_encode($rows));
    }

    public function test_exported_cells_cannot_run_as_spreadsheet_formulas(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => '=HYPERLINK("http://evil.test","click")']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => '+1+1']);

        $rows = $this->csv(Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('exportCsv'));
        $titles = array_column($rows, 1);

        foreach (array_slice($titles, 1) as $title) {
            $this->assertStringStartsWith("'", $title);
        }
    }

    public function test_export_headers_are_translated(): void
    {
        $this->taskFor($this->employeeEmp);
        app()->setLocale('ar');

        $rows = $this->csv(Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('exportCsv'));

        $this->assertContains('المهمة', $rows[0]);
        $this->assertContains('الحالة', $rows[0]);
    }

    public function test_every_table_can_export(): void
    {
        $this->taskFor($this->employeeEmp, 'completed');
        app(TaskWorkflowService::class)->approve(Task::first(), $this->manager);

        $this->csv(Livewire::actingAs($this->hr)->test(EmployeeList::class)->call('exportCsv'));
        $this->csv(Livewire::actingAs($this->hr)->test(ApprovalHistory::class)->call('exportCsv'));
        $users = $this->csv(Livewire::actingAs($this->admin)->test(UserManager::class)->call('exportCsv'));
        $this->csv(Livewire::actingAs($this->admin)->test(DepartmentManager::class)->call('exportCsv'));

        $this->assertContains('Username', $users[0]);
        $this->assertStringNotContainsString('password', strtolower(json_encode($users)));     // never exported
    }

    public function test_only_the_super_admin_can_export_users(): void
    {
        Livewire::actingAs($this->hr)->test(UserManager::class)->assertForbidden();
    }

    // ---- toolbar and layout ---------------------------------------------------------------------------

    public function test_tables_have_a_toolbar_sortable_headers_and_numbered_rows(): void
    {
        $this->taskFor($this->employeeEmp);

        $html = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->html();

        $this->assertStringContainsString('Search table', $html);
        $this->assertStringContainsString('Columns', $html);
        $this->assertStringContainsString('wire:click="exportCsv"', $html);
        $this->assertStringContainsString('wire:click="sortBy(\'title\')"', $html);
        $this->assertStringContainsString('wire:click="sortBy(\'due\')"', $html);
        $this->assertStringContainsString('aria-sort="none"', $html);
        $this->assertStringContainsString('from-indigo-600', $html);          // coloured header row
        $this->assertStringContainsString('data-col="status"', $html);        // column chooser hooks
        $this->assertStringContainsString('View details', $html);              // eye / pencil / ⋯ actions
    }

    public function test_the_active_sort_column_is_announced_to_screen_readers(): void
    {
        $this->taskFor($this->employeeEmp);

        $asc = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('sortBy', 'title')->html();
        $this->assertStringContainsString('aria-sort="ascending"', $asc);

        $desc = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->call('sortBy', 'title')->call('sortBy', 'title')->html();
        $this->assertStringContainsString('aria-sort="descending"', $desc);
    }

    public function test_sorting_survives_in_the_url_for_sharing_and_refresh(): void
    {
        $this->taskFor($this->employeeEmp);

        $this->actingAs($this->employee)->get('/employee/tasks?sort=title&dir=desc')->assertOk()->assertSee('aria-sort="descending"', false);
    }
}
