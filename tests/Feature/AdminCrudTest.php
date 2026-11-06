<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\DepartmentManager;
use App\Livewire\Admin\UserManager;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class AdminCrudTest extends TestCase
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

    private function newUser(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nadia Karam', 'username' => 'nadia', 'email' => 'nadia@x.test', 'phone' => '',
            'role' => 'employee', 'department_id' => (string) $this->dept->id, 'manager_id' => (string) $this->managerEmp->id,
            'job_title' => 'Designer', 'password' => 'Strong123',
        ], $overrides);
    }

    private function fill($component, array $data)
    {
        foreach ($data as $key => $value) {
            $component->set($key, $value);
        }

        return $component;
    }

    // ---- access -------------------------------------------------------------------

    public function test_only_the_super_admin_can_open_the_admin_pages(): void
    {
        $urls = ['/users', '/departments'];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login');   // guests first: actingAs() would persist
        }
        foreach ($urls as $url) {
            foreach ([$this->employee, $this->manager, $this->hr] as $user) {
                $this->actingAs($user)->get($this->p($user, $url))->assertForbidden();
            }
            $this->actingAs($this->admin)->get($this->p($this->admin, $url))->assertOk();
        }
    }

    public function test_livewire_actions_are_blocked_for_non_admins(): void
    {
        Livewire::actingAs($this->hr)->test(UserManager::class)->assertForbidden();
        Livewire::actingAs($this->employee)->test(DepartmentManager::class)->assertForbidden();
    }

    // ---- users: create / read / update / delete ----------------------------------

    public function test_super_admin_can_create_a_user_with_an_employee_profile(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserManager::class)->call('create');
        $this->fill($component, $this->newUser())->call('save')->assertHasNoErrors();

        $user = User::where('username', 'nadia')->firstOrFail();
        $this->assertSame(UserRole::Employee, $user->role);
        $this->assertTrue(Hash::check('Strong123', $user->password));
        $this->assertSame($this->managerEmp->id, $user->employee->manager_id);
        $this->assertMatchesRegularExpression('/^EMP-\d{4}$/', $user->employee->employee_number);
    }

    public function test_user_form_validation(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserManager::class)->call('create');
        $this->fill($component, $this->newUser([
            'name' => '', 'username' => 'x', 'email' => $this->manager->email, 'password' => 'abc', 'department_id' => '', 'job_title' => '',
        ]))->call('save')->assertHasErrors(['name', 'username', 'email' => 'unique', 'password', 'department_id', 'job_title']);

        $this->assertDatabaseMissing('users', ['username' => 'x']);
    }

    public function test_username_must_be_unique_ignoring_case(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserManager::class)->call('create');
        $this->fill($component, $this->newUser(['username' => strtoupper($this->manager->username)]))->call('save')
            ->assertHasErrors(['username' => 'unique']);
    }

    public function test_super_admin_can_edit_a_user_and_keeps_the_password_when_left_blank(): void
    {
        $oldHash = $this->employee->password;

        $component = Livewire::actingAs($this->admin)->test(UserManager::class)->call('edit', $this->employee->id);
        $component->set('name', 'Renamed Person')->set('job_title', 'Lead Dev')->set('role', 'manager')->set('password', '')
            ->call('save')->assertHasNoErrors();

        $this->employee->refresh();
        $this->assertSame('Renamed Person', $this->employee->name);
        $this->assertSame(UserRole::Manager, $this->employee->role);
        $this->assertSame('Lead Dev', $this->employee->employee->job_title);
        $this->assertSame($oldHash, $this->employee->password);
    }

    public function test_editing_cannot_promote_a_user_to_super_admin(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserManager::class)->call('edit', $this->employee->id);
        $component->set('role', 'super_admin')->call('save')->assertHasErrors('role');

        $this->assertSame(UserRole::Employee, $this->employee->fresh()->role);
    }

    public function test_a_manager_with_a_team_cannot_be_demoted(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserManager::class)->call('edit', $this->manager->id);
        $component->set('role', 'employee')->call('save')->assertHasErrors('role');
    }

    public function test_super_admin_can_delete_a_user_without_tasks(): void
    {
        $lonely = Employee::factory()->create();
        $name = $lonely->user->name; // read before the row disappears

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->call('confirmDelete', $lonely->user_id)->call('delete')
            ->assertDispatched('notify', type: 'success', message: "{$name} was deleted.");

        $this->assertDatabaseMissing('users', ['id' => $lonely->user_id]);
        $this->assertDatabaseMissing('employees', ['id' => $lonely->id]);
    }

    public function test_users_with_tasks_managers_with_teams_and_yourself_cannot_be_deleted(): void
    {
        $this->taskFor($this->employeeEmp);

        $page = Livewire::actingAs($this->admin)->test(UserManager::class);

        $page->call('confirmDelete', $this->employee->id)->call('delete')->assertDispatched('notify', type: 'error');
        $page->call('confirmDelete', $this->manager->id)->call('delete')->assertDispatched('notify', type: 'error');
        $page->call('confirmDelete', $this->admin->id)->call('delete')->assertDispatched('notify', type: 'error', message: 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $this->employee->id]);
        $this->assertDatabaseHas('users', ['id' => $this->manager->id]);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_user_list_search_and_clear(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserManager::class);

        $page->set('search', $this->manager->name)->call('search')
            ->assertSee($this->manager->name)->assertDontSee($this->hr->name);

        $page->call('clearFilters')->assertSet('search', '')->assertSee($this->manager->name)->assertSee($this->hr->name);
    }

    public function test_user_list_filters_by_role(): void
    {
        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('roleFilter', 'hr')->assertSee($this->hr->name)->assertDontSee($this->employee->name);
    }

    // ---- departments --------------------------------------------------------------

    public function test_super_admin_can_create_edit_and_delete_a_department(): void
    {
        $page = Livewire::actingAs($this->admin)->test(DepartmentManager::class)->call('create');
        $page->set('name', 'Legal')->set('code', 'leg')->call('save')->assertHasNoErrors();

        $department = Department::where('name', 'Legal')->firstOrFail();
        $this->assertSame('LEG', $department->code);

        $page->call('edit', $department->id)->set('name', 'Legal & Compliance')->call('save')->assertHasNoErrors();
        $this->assertSame('Legal & Compliance', $department->fresh()->name);

        $page->call('confirmDelete', $department->id)->call('delete');
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_validation_and_delete_protection(): void
    {
        $page = Livewire::actingAs($this->admin)->test(DepartmentManager::class)->call('create');
        $page->set('name', $this->dept->name)->set('code', 'bad code')->call('save')->assertHasErrors(['name' => 'unique', 'code']);

        $page->call('confirmDelete', $this->dept->id)->call('delete')->assertDispatched('notify', type: 'error');
        $this->assertDatabaseHas('departments', ['id' => $this->dept->id]);
    }
}
