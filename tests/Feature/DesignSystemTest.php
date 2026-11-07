<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TaskList;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** One standard page skeleton, no Back buttons, and lists that show their filters first. */
class DesignSystemTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    public function test_no_page_has_a_back_button_breadcrumbs_lead_back(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');
        $pending = $this->taskFor($this->employeeEmp);

        $pages = [
            [$this->employee, "/employee/tasks/{$task->id}"],
            [$this->employee, '/employee/tasks/create'],
            [$this->employee, "/employee/tasks/{$pending->id}/confirm/cancel"],
            [$this->manager, "/manager/tasks/{$task->id}/reject"],
        ];

        foreach ($pages as [$user, $url]) {
            $this->actingAs($user)->get($url)->assertOk()
                ->assertSee('aria-label="Breadcrumb"', false)
                ->assertDontSee('>Back<', false);
        }
    }

    public function test_task_pages_use_the_standard_full_width_page_and_summary_card(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $html = $this->actingAs($this->employee)->get("/employee/tasks/{$task->id}")->assertOk()->getContent();

        $this->assertStringContainsString('class="page"', $html);
        $this->assertStringContainsString('class="card', $html);
        $this->assertStringNotContainsString('max-w-4xl', $html);            // the old "half page" wrapper is gone
        $this->assertStringContainsString($task->employee->job_title, $html);  // the side summary
    }

    public function test_the_results_table_stays_hidden_until_search_and_clear_hides_it_again(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Visible after search']);

        $list = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine']);

        $list->assertSet('searched', false)->assertSee('x-show="shown"', false);
        $list->call('applyFilters')->assertSet('searched', true);
        $list->call('clearFilters')->assertSet('searched', false);
    }

    public function test_arriving_with_filters_in_the_url_opens_the_table(): void
    {
        Livewire::withQueryParams(['status' => 'PENDING'])->actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])
            ->assertSet('searched', true);
    }

    public function test_every_list_page_has_a_filter_panel_and_a_collapsible_results_card(): void
    {
        foreach ([[$this->hr, '/hr/all-tasks'], [$this->hr, '/hr/employees'], [$this->hr, '/hr/approvals']] as [$user, $url]) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('Filters')->assertSee('Results');
        }
    }

    public function test_the_design_system_guide_exists_and_points_to_the_style_files(): void
    {
        $guide = file_get_contents(base_path('docs/DESIGN-SYSTEM.md'));

        foreach (['resources/css/app.css', '.btn-primary', '.card', '<x-page>', 'components/button.blade.php'] as $needle) {
            $this->assertStringContainsString($needle, $guide);
        }
    }
}
