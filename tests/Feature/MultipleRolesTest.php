<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserManager;
use App\Livewire\Tasks\TaskList;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** A user may hold several roles: the first is the main role, the others are extra roles, and the powers add up. */
class MultipleRolesTest extends TestCase
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

    private function giveRoles(User $user, UserRole $main, UserRole ...$extra): User
    {
        $user->role = $main;
        $user->extra_roles = array_map(fn ($r) => $r->value, $extra) ?: null;
        $user->save();

        return $user->fresh();
    }

    public function test_a_user_reports_all_their_roles_and_the_main_one_comes_first(): void
    {
        $user = $this->giveRoles($this->manager, UserRole::Manager, UserRole::Hr);

        $this->assertSame(['manager', 'hr'], $user->roles()->map->value->all());
        $this->assertTrue($user->hasRole(UserRole::Hr));
        $this->assertTrue($user->hasRole(UserRole::Manager));
        $this->assertFalse($user->hasRole(UserRole::SuperAdmin));
        $this->assertSame('Manager · HR', $user->roleLabels());
        $this->assertSame(['manager', 'hr'], $user->panelPrefixes());
        $this->assertSame('manager', $user->url_prefix);                          // the main role decides the default prefix
    }

    public function test_a_manager_who_is_also_hr_opens_both_areas_and_both_prefixes(): void
    {
        $user = $this->giveRoles($this->manager, UserRole::Manager, UserRole::Hr);
        Task::factory()->forEmployee($this->employeeEmp)->create();

        $this->actingAs($user)->get('/manager/team/tasks')->assertOk();          // manager area
        $this->actingAs($user)->get('/hr/all-tasks')->assertOk();                 // HR area
        $this->actingAs($user)->get('/hr/employees')->assertOk();
        $this->actingAs($user)->get('/hr/dashboard')->assertOk();                 // the HR prefix is accepted
        $this->actingAs($user)->get('/admin/users')->assertNotFound();            // a prefix that is not one of his roles
        $this->actingAs($this->employee)->get('/hr/all-tasks')->assertNotFound(); // a plain employee still cannot
    }

    public function test_the_powers_of_all_roles_are_combined_in_what_the_user_sees(): void
    {
        $user = $this->giveRoles($this->manager, UserRole::Manager, UserRole::Hr);
        $own = Task::factory()->forEmployee($this->managerEmp)->create();
        $team = Task::factory()->forEmployee($this->employeeEmp)->create();
        $department = Task::factory()->forEmployee($this->hrEmp)->create();          // same department, not his team
        $elsewhere = Task::factory()->forEmployee($this->outsiderEmp)->create();      // another department

        $ids = Task::visibleTo($user)->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$own, $team, $department])->pluck('id')->sort()->values()->all(), $ids);
        $this->assertNotContains($elsewhere->id, $ids);

        // the same person as ONLY a manager does not see the department's other tasks
        $onlyManager = $this->giveRoles($user, UserRole::Manager);
        $this->assertNotContains($department->id, Task::visibleTo($onlyManager)->pluck('id')->all());
        $this->assertContains($team->id, Task::visibleTo($onlyManager)->pluck('id')->all());
    }

    public function test_an_hr_user_who_is_also_a_manager_can_approve_their_team_but_not_other_people(): void
    {
        $user = $this->giveRoles($this->hr, UserRole::Hr, UserRole::Manager);
        $report = $this->taskFor($this->employeeEmp, 'completed');

        $this->employeeEmp->update(['manager_id' => $this->hrEmp->id]);              // the HR user now manages this employee
        app(TaskWorkflowService::class)->approve($report, $user);
        $this->assertSame(TaskStatus::Approved, $report->fresh()->status);

        $notHisTeam = $this->taskFor($this->teammateEmp, 'completed');
        $this->expectException(AuthorizationException::class);
        app(TaskWorkflowService::class)->approve($notHisTeam, $user);
    }

    public function test_roles_are_not_mass_assignable(): void
    {
        $user = User::create(['name' => 'Eve', 'email' => 'eve@x.test', 'password' => 'secret123', 'extra_roles' => ['hr']]);

        $this->assertSame([], $user->fresh()->roles()->skip(1)->map->value->all());
    }

    // ---- the admin form ----------------------------------------------------------------------------

    private function newUser(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nadia Karam', 'username' => 'nadia', 'email' => 'nadia@x.test', 'department_id' => (string) $this->dept->id,
            'job_title' => 'Lead', 'employee_number' => 'EMP-7001', 'password' => 'Str0ng!Passw0rd',
        ], $overrides);
    }

    private function fill($page, array $data)
    {
        foreach ($data as $key => $value) {
            $page->set($key, $value);
        }

        return $page;
    }

    public function test_the_admin_creates_a_user_with_several_roles_and_the_first_is_the_main_one(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserForm::class);
        $this->fill($page, $this->newUser(['roles' => ['manager', 'hr', 'employee']]))->call('save')->assertHasNoErrors();

        $user = User::where('username', 'nadia')->firstOrFail();
        $this->assertSame(UserRole::Manager, $user->role);
        $this->assertSame(['hr', 'employee'], $user->extra_roles);
        $this->assertSame('Manager · HR · Employee', $user->roleLabels());
    }

    public function test_editing_the_roles_is_prefilled_and_can_add_or_remove_roles(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->hr])->assertSet('roles', ['hr']);

        $page->set('roles', ['hr', 'manager'])->call('save')->assertHasNoErrors();
        $this->assertSame(['hr', 'manager'], $this->hr->fresh()->roles()->map->value->all());

        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->hr->fresh()])->assertSet('roles', ['hr', 'manager'])
            ->set('roles', ['hr'])->call('save')->assertHasNoErrors();
        $this->assertSame(['hr'], $this->hr->fresh()->roles()->map->value->all());
    }

    public function test_role_validation_requires_valid_roles_and_never_combines_the_super_admin(): void
    {
        $component = fn () => Livewire::actingAs($this->admin)->test(UserForm::class);

        $this->fill($component(), $this->newUser(['roles' => []]))->call('save')->assertHasErrors(['roles']);
        $this->fill($component(), $this->newUser(['roles' => ['wizard']]))->call('save')->assertHasErrors(['roles.0']);
        $this->fill($component(), $this->newUser(['roles' => ['employee', 'super_admin']]))->call('save')->assertHasErrors(['roles']);

        $this->assertDatabaseMissing('users', ['username' => 'nadia']);
    }

    public function test_a_manager_with_a_team_cannot_lose_the_manager_role_but_can_gain_others(): void
    {
        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->manager])
            ->set('roles', ['employee'])->call('save')->assertHasErrors('roles');

        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->manager])
            ->set('roles', ['manager', 'hr'])->call('save')->assertHasNoErrors();
    }

    public function test_the_manager_dropdown_offers_people_with_the_manager_role_as_an_extra_role_too(): void
    {
        $this->giveRoles($this->hr, UserRole::Hr, UserRole::Manager);               // the HR user is now also a manager

        $page = Livewire::actingAs($this->admin)->test(UserForm::class)->set('department_id', (string) $this->dept->id);

        $this->assertEqualsCanonicalizing([$this->managerEmp->id, $this->hrEmp->id], $page->viewData('managers')->pluck('id')->all());
    }

    public function test_the_role_filter_finds_users_by_main_or_extra_role(): void
    {
        $this->giveRoles($this->employee, UserRole::Employee, UserRole::Hr);

        $page = Livewire::actingAs($this->admin)->test(UserManager::class)->set('roleFilter', ['hr']);
        $names = $page->viewData('users')->pluck('id')->all();

        $this->assertContains($this->employee->id, $names);                          // HR as an extra role
        $this->assertContains($this->hr->id, $names);                                // HR as the main role
        $this->assertNotContains($this->manager->id, $names);
    }

    public function test_the_api_login_returns_all_roles(): void
    {
        $this->giveRoles($this->manager, UserRole::Manager, UserRole::Hr);

        $this->postJson('/api/login', ['login' => $this->manager->username, 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.role', 'manager')->assertJsonPath('user.roles', ['manager', 'hr']);
    }
}
