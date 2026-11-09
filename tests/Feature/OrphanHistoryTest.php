<?php

namespace Tests\Feature;

use App\Livewire\Approvals\ApprovalHistory;
use App\Models\TaskHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Regression: HR opened /hr/approvals and got 'Attempt to read property "title" on null' (history row without a task). */
class OrphanHistoryTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_the_approval_history_survives_decisions_whose_task_no_longer_exists(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');
        app(\App\Services\TaskWorkflowService::class)->approve($task, $this->manager);

        // simulate what a MyISAM database leaves behind: a rejection row pointing at a task that is gone
        DB::unprepared('PRAGMA defer_foreign_keys = ON');   // SQLite: allow the orphan inside the test transaction (it is rolled back, never committed)
        DB::table('task_histories')->insert(['task_id' => 99999, 'actor_id' => $this->manager->id, 'action' => 'rejected', 'from_status' => 'COMPLETED', 'to_status' => 'REJECTED', 'reason' => 'ghost', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->hr)->get('/hr/approvals')->assertOk()->assertDontSee('ghost');

        $records = Livewire::actingAs($this->hr)->test(ApprovalHistory::class)->viewData('records');
        $this->assertCount(1, $records->items());
    }

    public function test_deleting_a_task_removes_its_history_without_relying_on_the_database_cascade(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        app(\App\Services\TaskWorkflowService::class)->update($task, $this->employee, ['title' => 'Renamed once', 'priority' => 'low']);
        $this->assertGreaterThan(0, TaskHistory::where('task_id', $task->id)->count());

        app(\App\Services\TaskWorkflowService::class)->delete($task, $this->employee);

        $this->assertSame(0, TaskHistory::where('task_id', $task->id)->count());
    }

    public function test_the_cleanup_migration_removes_only_orphans(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        app(\App\Services\TaskWorkflowService::class)->update($task, $this->employee, ['title' => 'Has a real history', 'priority' => 'low']);
        DB::unprepared('PRAGMA defer_foreign_keys = ON');
        DB::table('task_histories')->insert(['task_id' => 424242, 'action' => 'completed', 'to_status' => 'COMPLETED', 'created_at' => now(), 'updated_at' => now()]);
        $before = TaskHistory::count();

        (include database_path('migrations/2026_11_09_100000_remove_orphan_task_histories.php'))->up();

        $this->assertSame($before - 1, TaskHistory::count());
        $this->assertGreaterThan(0, TaskHistory::where('task_id', $task->id)->count());   // real history untouched
    }
}
