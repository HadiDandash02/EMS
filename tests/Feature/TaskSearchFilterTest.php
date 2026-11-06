<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TaskList;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class TaskSearchFilterTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_hr_can_filter_by_title_status_employee_and_department(): void
    {
        $budget = Task::factory()->forEmployee($this->employeeEmp)->completed()->create(['title' => 'Budget review']);
        $audit = Task::factory()->forEmployee($this->outsiderEmp)->pending()->create(['title' => 'Audit prep']);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);

        $list->assertSee('Budget review')->assertSee('Audit prep');

        $list->set('search', 'budget')->assertSee('Budget review')->assertDontSee('Audit prep');
        $list->set('search', '')->set('status', 'PENDING')->assertSee('Audit prep')->assertDontSee('Budget review');
        $list->set('status', '')->set('employeeId', (string) $this->employeeEmp->id)->assertSee('Budget review')->assertDontSee('Audit prep');
        $list->set('employeeId', '')->set('departmentId', (string) $this->outsiderEmp->department_id)->assertSee('Audit prep')->assertDontSee('Budget review');
    }

    public function test_hr_can_filter_by_date_range(): void
    {
        $old = Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Old task', 'created_at' => now()->subDays(30)]);
        $new = Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Fresh task']);

        Livewire::actingAs($this->hr)
            ->test(TaskList::class, ['scope' => 'all'])
            ->set('dateFrom', now()->subDays(2)->toDateString())
            ->assertSee('Fresh task')
            ->assertDontSee('Old task');
    }

    public function test_search_term_wildcards_are_treated_literally(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Plain title']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => '100% done']);

        Livewire::actingAs($this->hr)
            ->test(TaskList::class, ['scope' => 'all'])
            ->set('search', '%')
            ->assertSee('100% done')
            ->assertDontSee('Plain title');
    }

    public function test_sql_injection_attempt_is_harmless(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Safe task']);

        Livewire::actingAs($this->hr)
            ->test(TaskList::class, ['scope' => 'all'])
            ->set('search', "' OR 1=1; DROP TABLE tasks; --")
            ->assertDontSee('Safe task');

        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_results_are_paginated(): void
    {
        Task::factory()->count(25)->forEmployee($this->employeeEmp)->create();

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);

        $this->assertCount(10, $list->viewData('tasks')->items());
        $this->assertSame(25, $list->viewData('tasks')->total());
    }

    public function test_task_list_does_not_run_n_plus_one_queries(): void
    {
        Employee::factory()->count(6)->create()->each(
            fn ($e) => Task::factory()->count(2)->forEmployee($e)->create()
        );

        $component = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);

        DB::enableQueryLog();
        $component->call('resetFilters');
        $queries = count(DB::getQueryLog());

        // Eager loading keeps this flat (about 10 queries) regardless of row count; N+1 would be 30+.
        $this->assertLessThan(20, $queries, "Too many queries: {$queries}");
    }

    public function test_manager_team_list_excludes_other_teams(): void
    {
        $mine = $this->taskFor($this->employeeEmp, 'completed');
        $theirs = $this->taskFor($this->outsiderEmp, 'completed');

        Livewire::actingAs($this->manager)
            ->test(TaskList::class, ['scope' => 'team'])
            ->assertSee($mine->title)
            ->assertDontSee($theirs->title);
    }
}
