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

    /** Titles in the table rows (the Task picker also embeds every title in the page, so the raw HTML is not a reliable check). */
    private function rows($list): array
    {
        return $list->viewData('tasks')->pluck('title')->all();
    }

    public function test_hr_can_filter_by_title_status_employee_and_department(): void
    {
        $budget = Task::factory()->forEmployee($this->employeeEmp)->completed()->create(['title' => 'Budget review']);
        $audit = Task::factory()->forEmployee($this->outsiderEmp)->pending()->create(['title' => 'Audit prep']);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);

        $this->assertEqualsCanonicalizing(['Budget review', 'Audit prep'], $this->rows($list));

        $this->assertSame(['Budget review'], $this->rows($list->set('search', 'budget')));
        $this->assertSame(['Audit prep'], $this->rows($list->set('search', '')->set('status', 'PENDING')));
        $this->assertSame(['Budget review'], $this->rows($list->set('status', '')->set('employeeId', (string) $this->employeeEmp->id)));
        $this->assertSame(['Audit prep'], $this->rows($list->set('employeeId', '')->set('departmentId', (string) $this->outsiderEmp->department_id)));
    }

    public function test_hr_can_filter_by_date_range(): void
    {
        $old = Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Old task', 'created_at' => now()->subDays(30)]);
        $new = Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Fresh task']);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->set('dateFrom', now()->subDays(2)->toDateString());

        $this->assertSame(['Fresh task'], $this->rows($list));
    }

    public function test_search_term_wildcards_are_treated_literally(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Plain title']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => '100% done']);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->set('search', '%');

        $this->assertSame(['100% done'], $this->rows($list));
    }

    public function test_sql_injection_attempt_is_harmless(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Safe task']);

        $list = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->set('search', "' OR 1=1; DROP TABLE tasks; --");

        $this->assertSame([], $this->rows($list));

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
        $component->call('clearFilters');
        $queries = count(DB::getQueryLog());

        // Eager loading keeps this flat (about 10 queries) regardless of row count; N+1 would be 30+.
        $this->assertLessThan(20, $queries, "Too many queries: {$queries}");
    }

    public function test_manager_team_list_excludes_other_teams(): void
    {
        $mine = Task::factory()->forEmployee($this->employeeEmp)->completed()->create(["title" => "Alpha team task"]);
        $theirs = Task::factory()->forEmployee($this->outsiderEmp)->completed()->create(["title" => "Zulu other team task"]);

        Livewire::actingAs($this->manager)
            ->test(TaskList::class, ['scope' => 'team'])
            ->assertSee($mine->title)
            ->assertDontSee($theirs->title);
    }

    public function test_the_task_filter_is_a_searchable_select_listing_only_the_tasks_each_role_may_see(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Mine alpha']);
        Task::factory()->forEmployee($this->teammateEmp)->create(['title' => 'Teammate bravo']);
        Task::factory()->forEmployee($this->outsiderEmp)->create(['title' => 'Outsider charlie']);

        // the old free-text "Search title" box is gone; the filter is a searchable select named "Task"
        $this->actingAs($this->employee)->get('/employee/tasks')->assertOk()->assertSee('All tasks')->assertDontSee('Search title');

        $options = fn ($user, string $scope) => Livewire::actingAs($user)->test(TaskList::class, ['scope' => $scope])->viewData('taskOptions')->values()->implode('|');

        $mine = $options($this->employee, 'mine');
        $this->assertStringContainsString('Mine alpha', $mine);
        $this->assertStringNotContainsString('bravo', $mine);
        $this->assertStringNotContainsString('charlie', $mine);

        $team = $options($this->manager, 'team');                         // the manager sees only the team
        $this->assertStringContainsString('Mine alpha', $team);
        $this->assertStringContainsString('Teammate bravo', $team);
        $this->assertStringNotContainsString('charlie', $team);

        $all = $options($this->hr, 'all');                                // HR sees everything, with the employee name
        foreach (['Mine alpha', 'Teammate bravo', 'Outsider charlie', $this->outsiderEmp->user->name] as $needle) {
            $this->assertStringContainsString($needle, $all);
        }
    }

    public function test_picking_a_task_shows_only_that_task_and_a_tampered_id_shows_nothing(): void
    {
        $mine = Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Mine alpha']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Mine delta']);
        $theirs = Task::factory()->forEmployee($this->teammateEmp)->create(['title' => 'Teammate bravo']);

        $list = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine']);
        $this->assertSame(['Mine alpha'], $this->rows($list->set('taskId', (string) $mine->id)));

        // someone else's id typed into the URL still cannot leak a row
        $this->assertSame([], $this->rows($list->set('taskId', (string) $theirs->id)));
        $list->assertSee('No tasks found');

        $list->call('clearFilters')->assertSet('taskId', []);
        $this->assertEqualsCanonicalizing(['Mine alpha', 'Mine delta'], $this->rows($list));
    }
}
