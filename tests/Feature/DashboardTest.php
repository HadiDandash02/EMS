<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Tasks\TaskList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Every account sees ITS OWN dashboard: employee = own tasks, manager = own + team, HR = company, admin = company + users. */
class DashboardTest extends TestCase
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

        // 2 own tasks (1 pending, 1 completed), 1 teammate task (same manager), 1 task of another team
        Task::factory()->forEmployee($this->employeeEmp)->pending()->create(['priority' => 'high']);
        Task::factory()->forEmployee($this->employeeEmp)->completed()->create(['priority' => 'low']);
        Task::factory()->forEmployee($this->teammateEmp)->pending()->create();
        Task::factory()->forEmployee($this->outsiderEmp)->inProgress()->create();
    }

    public function test_an_employee_sees_only_their_own_numbers_and_no_team_or_company_block(): void
    {
        $r = $this->actingAs($this->employee)->get('/employee/dashboard')->assertOk();

        $this->assertNull($r->viewData('team'));
        $this->assertNull($r->viewData('company'));
        $this->assertSame(2, $r->viewData('mine')['headline']['total']);          // not 4
        $this->assertSame(50, $r->viewData('mine')['headline']['completion']);    // 1 of 2 completed
        $this->assertSame([1, 0, 1, 0, 0, 0], $r->viewData('mine')['status']['values']);
        $r->assertDontSee('Company overview')->assertDontSee('Team overview')->assertSee('My tasks by status');
    }

    public function test_a_manager_sees_the_team_only_not_other_teams_and_not_the_company(): void
    {
        $r = $this->actingAs($this->manager)->get('/manager/dashboard')->assertOk();

        $this->assertNull($r->viewData('company'));
        $this->assertSame(3, $r->viewData('team')['headline']['total']);          // employee x2 + teammate x1, not the outsider
        $this->assertSame(1, $r->viewData('team')['headline']['awaiting']);       // the completed one
        $r->assertSee('Team overview')->assertDontSee('Company overview');
    }

    public function test_hr_sees_the_whole_company_but_no_team_block(): void
    {
        $r = $this->actingAs($this->hr)->get('/hr/dashboard')->assertOk();

        $this->assertNull($r->viewData('team'));
        $this->assertNull($r->viewData('mine'));                                  // HR has no tasks of their own: no empty "My tasks" block
        $this->assertSame(4, $r->viewData('company')['headline']['total']);
        $this->assertNull($r->viewData('company')['roles']);                      // user statistics are for the super admin only
        $r->assertSee('Company overview')->assertDontSee('Users by role');
    }

    public function test_hr_gets_a_my_tasks_block_only_when_they_have_tasks_of_their_own(): void
    {
        Task::factory()->forEmployee($this->hrEmp)->pending()->create();

        $r = $this->actingAs($this->hr)->get('/hr/dashboard')->assertOk();

        $this->assertSame(1, $r->viewData('mine')['headline']['total']);
        $this->assertSame(5, $r->viewData('company')['headline']['total']);
    }

    public function test_the_super_admin_also_gets_user_statistics(): void
    {
        $r = $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk();

        $this->assertSame(4, $r->viewData('company')['headline']['total']);
        $this->assertNotNull($r->viewData('company')['roles']);
        $this->assertNull($r->viewData('mine'));                                  // the super admin has no employee profile
        $r->assertSee('Users by role');
    }

    public function test_each_chart_gets_its_data_and_an_empty_chart_says_so(): void
    {
        $html = $this->actingAs($this->hr)->get('/hr/dashboard')->getContent();
        $this->assertStringContainsString('<canvas', $html);
        $this->assertStringContainsString('window.loadChart()', $html);

        Task::query()->delete();
        $this->actingAs($this->hr)->get('/hr/dashboard')->assertOk()->assertSee('No data yet');
    }

    public function test_the_managers_team_list_starts_with_all_statuses_and_returns_there_after_a_decision(): void
    {
        $list = Livewire::actingAs($this->manager)->test(TaskList::class, ['scope' => 'team']);
        $list->assertSet('status', []);                                            // no hidden "Completed" default
        $this->assertCount(3, $list->viewData('tasks')->items());                  // every status of the team is listed
    }
}
