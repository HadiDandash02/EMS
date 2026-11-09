<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\DeleteDepartment;
use App\Livewire\Admin\DeleteUser;
use App\Livewire\Admin\DepartmentForm;
use App\Livewire\Admin\DepartmentManager;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserManager;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Super admin CRUD: lists + separate pages for create, edit and delete (no popups). */
class AdminCrudTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private const STRONG = 'Str0ng!Passw0rd';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        $this->admin = new User(['name' => 'Root Admin', 'username' => 'root', 'email' => 'root@x.test', 'password' => 'Secret123!']);
        $this->admin->role = UserRole::SuperAdmin;
        $this->admin->save();
    }

    /** @param  array<string, string>  $overrides */
    private function newUser(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nadia Karam', 'username' => 'nadia', 'email' => 'nadia@x.test', 'phone' => '',
            'role' => 'employee', 'department_id' => (string) $this->dept->id, 'manager_id' => (string) $this->managerEmp->id,
            'job_title' => 'Designer', 'employee_number' => 'EMP-9001', 'password' => self::STRONG,
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
        $urls = ['/users', '/users/create', "/users/{$this->employee->id}/edit", "/users/{$this->employee->id}/delete",
            '/departments', '/departments/create', "/departments/{$this->dept->id}/edit", "/departments/{$this->dept->id}/delete"];

        foreach ($urls as $url) {
            $this->get('/admin'.$url)->assertRedirect('/login');   // guests first: actingAs() would persist
        }
        foreach ($urls as $url) {
            foreach ([$this->employee, $this->manager, $this->hr] as $user) {
                $this->actingAs($user)->get($this->p($user, $url))->assertForbidden();
            }
        }
        foreach (['/users', '/users/create', "/users/{$this->employee->id}/edit", '/departments', '/departments/create', "/departments/{$this->dept->id}/edit"] as $url) {
            $this->actingAs($this->admin)->get($this->p($this->admin, $url))->assertOk();
        }
    }

    public function test_livewire_components_are_blocked_for_non_admins(): void
    {
        Livewire::actingAs($this->hr)->test(UserManager::class)->assertForbidden();
        Livewire::actingAs($this->hr)->test(UserForm::class)->assertForbidden();
        Livewire::actingAs($this->employee)->test(DepartmentManager::class)->assertForbidden();
        Livewire::actingAs($this->employee)->test(DepartmentForm::class)->assertForbidden();
        Livewire::actingAs($this->manager)->test(DeleteUser::class, ['user' => $this->teammate])->assertForbidden();
    }

    // ---- users: create / read / update / delete ----------------------------------------

    public function test_super_admin_can_create_a_user_with_an_employee_profile(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserForm::class);
        $this->fill($component, $this->newUser())->call('save')->assertHasNoErrors()->assertRedirect(route('admin.users'));

        $user = User::where('username', 'nadia')->firstOrFail();
        $this->assertSame(UserRole::Employee, $user->role);
        $this->assertTrue(Hash::check(self::STRONG, $user->password));
        $this->assertSame($this->managerEmp->id, $user->employee->manager_id);
        $this->assertMatchesRegularExpression('/^EMP-\d{4}$/', $user->employee->employee_number);
        $this->assertSame('User created.', session('success'));
    }

    public function test_user_form_validation(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserForm::class);
        $this->fill($component, $this->newUser([
            'name' => '', 'username' => 'x', 'email' => $this->manager->email, 'password' => 'abc', 'department_id' => '', 'job_title' => '',
        ]))->call('save')->assertHasErrors(['name', 'username', 'email' => 'unique', 'password', 'department_id', 'job_title']);

        $this->assertDatabaseMissing('users', ['username' => 'x']);
    }

    public function test_the_employee_number_is_required_unique_and_well_formed(): void
    {
        foreach (['' => 'required', 'bad number!' => 'regex', $this->employeeEmp->employee_number => 'unique', 'EMP-1234567890123456789012' => 'max'] as $value => $rule) {
            $component = Livewire::actingAs($this->admin)->test(UserForm::class);
            $this->fill($component, $this->newUser(['employee_number' => (string) $value]))->call('save')->assertHasErrors(['employee_number' => $rule]);
        }

        $this->assertDatabaseMissing('users', ['username' => 'nadia']);
    }

    public function test_the_new_user_form_suggests_the_next_employee_number_but_it_stays_required(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserForm::class);

        $this->assertMatchesRegularExpression('/^EMP-\d{4}$/', $page->get('employee_number'));   // a suggestion

        $page->set('employee_number', '')->set('name', 'X')->call('save')->assertHasErrors(['employee_number' => 'required']);   // clearing it is not allowed
    }

    public function test_reports_to_offers_only_the_managers_of_the_selected_department(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserForm::class);

        $page->assertViewHas('managers', fn ($m) => $m->isEmpty());                    // no department chosen yet

        $page->set('department_id', (string) $this->dept->id);
        $this->assertSame([$this->managerEmp->id], $page->viewData('managers')->pluck('id')->all());   // only Engineering's manager

        $page->set('department_id', (string) $this->otherManagerEmp->department_id);
        $this->assertSame([$this->otherManagerEmp->id], $page->viewData('managers')->pluck('id')->all());
    }

    public function test_changing_the_department_clears_a_manager_who_does_not_belong_to_it(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserForm::class)
            ->set('department_id', (string) $this->dept->id)
            ->set('manager_id', (string) $this->managerEmp->id)
            ->assertSet('manager_id', (string) $this->managerEmp->id);

        $page->set('department_id', (string) $this->otherManagerEmp->department_id)->assertSet('manager_id', '');
    }

    public function test_the_server_refuses_a_manager_from_another_department_even_if_the_form_is_tampered_with(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserForm::class);

        // department Engineering, but the chosen manager works in the other department
        $this->fill($component, $this->newUser(['manager_id' => (string) $this->otherManagerEmp->id]))->call('save')
            ->assertHasErrors(['manager_id' => 'exists']);

        $this->assertDatabaseMissing('users', ['username' => 'nadia']);
    }

    public function test_a_new_users_password_must_be_strong(): void
    {
        foreach (['short1!A', 'longenoughbutnosymbol1A', 'No-Digits-Here!!', 'no-upper-case-1!!', 'NO-LOWER-CASE-1!!', ''] as $weak) {
            $component = Livewire::actingAs($this->admin)->test(UserForm::class);
            $this->fill($component, $this->newUser(['password' => $weak]))->call('save')->assertHasErrors(['password']);
        }

        $this->assertDatabaseMissing('users', ['username' => 'nadia']);
    }

    public function test_a_password_typed_while_editing_must_also_be_strong_but_blank_keeps_the_old_one(): void
    {
        $old = $this->employee->password;

        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->employee])
            ->set('password', 'weak')->call('save')->assertHasErrors(['password']);
        $this->assertSame($old, $this->employee->fresh()->password);

        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->employee])
            ->set('password', self::STRONG)->call('save')->assertHasNoErrors();
        $this->assertTrue(Hash::check(self::STRONG, $this->employee->fresh()->password));
    }

    public function test_username_must_be_unique_ignoring_case(): void
    {
        $component = Livewire::actingAs($this->admin)->test(UserForm::class);
        $this->fill($component, $this->newUser(['username' => strtoupper($this->manager->username)]))->call('save')
            ->assertHasErrors(['username' => 'unique']);
    }

    public function test_the_edit_page_is_prefilled_and_saves_changes(): void
    {
        $oldHash = $this->employee->password;

        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->employee])
            ->assertSet('name', $this->employee->name)
            ->assertSet('job_title', $this->employeeEmp->job_title)
            ->set('name', 'Renamed Person')->set('job_title', 'Lead Dev')->set('role', 'manager')->set('password', '')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.users'));

        $this->employee->refresh();
        $this->assertSame('Renamed Person', $this->employee->name);
        $this->assertSame(UserRole::Manager, $this->employee->role);
        $this->assertSame('Lead Dev', $this->employee->employee->job_title);
        $this->assertSame($oldHash, $this->employee->password);
    }

    public function test_editing_cannot_promote_a_user_to_super_admin(): void
    {
        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->employee])
            ->set('role', 'super_admin')->call('save')->assertHasErrors('role');

        $this->assertSame(UserRole::Employee, $this->employee->fresh()->role);
    }

    public function test_a_manager_with_a_team_cannot_be_demoted(): void
    {
        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->manager])
            ->set('role', 'employee')->call('save')->assertHasErrors('role');
    }

    public function test_super_admin_can_delete_a_user_without_tasks_on_a_confirmation_page(): void
    {
        $lonely = Employee::factory()->create();
        $name = $lonely->user->name; // read before the row disappears

        $this->actingAs($this->admin)->get("/admin/users/{$lonely->user_id}/delete")->assertOk()->assertSee($name);

        Livewire::actingAs($this->admin)->test(DeleteUser::class, ['user' => $lonely->user])
            ->call('delete')->assertRedirect(route('admin.users'));

        $this->assertSame("{$name} was deleted.", session('success'));
        $this->assertDatabaseMissing('users', ['id' => $lonely->user_id]);
        $this->assertDatabaseMissing('employees', ['id' => $lonely->id]);
    }

    public function test_users_with_tasks_managers_with_teams_and_yourself_cannot_be_deleted(): void
    {
        $this->taskFor($this->employeeEmp);

        foreach ([$this->employee, $this->manager, $this->admin] as $user) {
            // the confirmation page refuses to open and explains why
            Livewire::actingAs($this->admin)->test(DeleteUser::class, ['user' => $user])->assertRedirect(route('admin.users'));
            $this->assertNotEmpty(session('error'));
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    public function test_user_list_search_clear_and_filters(): void
    {
        $names = fn ($page) => $page->viewData('users')->pluck('name')->all();
        $page = Livewire::actingAs($this->admin)->test(UserManager::class);

        $page->set('search', $this->manager->name)->call('applyFilters');
        $this->assertSame([$this->manager->name], $names($page));

        $page->call('clearFilters')->assertSet('search', '');
        $this->assertContains($this->hr->name, $names($page));
        $this->assertContains($this->manager->name, $names($page));

        $page->set('roleFilter', 'hr');
        $this->assertContains($this->hr->name, $names($page));
        $this->assertNotContains($this->employee->name, $names($page));
    }

    public function test_the_user_list_links_to_the_form_pages_instead_of_opening_popups(): void
    {
        $html = Livewire::actingAs($this->admin)->test(UserManager::class)->html();

        $this->assertStringContainsString('/admin/users/create', $html);
        $this->assertStringContainsString("/admin/users/{$this->employee->id}/edit", $html);
        $this->assertStringContainsString("/admin/users/{$this->employee->id}/delete", $html);
        $this->assertStringNotContainsString('wire:confirm', $html);
    }

    // ---- departments --------------------------------------------------------------------

    public function test_super_admin_can_create_edit_and_delete_a_department(): void
    {
        Livewire::actingAs($this->admin)->test(DepartmentForm::class)
            ->set('name', 'Legal')->set('code', 'leg')->call('save')->assertHasNoErrors()->assertRedirect(route('admin.departments'));

        $department = Department::where('name', 'Legal')->firstOrFail();
        $this->assertSame('LEG', $department->code);

        Livewire::actingAs($this->admin)->test(DepartmentForm::class, ['department' => $department])
            ->assertSet('name', 'Legal')
            ->set('name', 'Legal & Compliance')->call('save')->assertHasNoErrors();
        $this->assertSame('Legal & Compliance', $department->fresh()->name);

        Livewire::actingAs($this->admin)->test(DeleteDepartment::class, ['department' => $department])->call('delete')->assertRedirect(route('admin.departments'));
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_validation_and_delete_protection(): void
    {
        Livewire::actingAs($this->admin)->test(DepartmentForm::class)
            ->set('name', $this->dept->name)->set('code', 'bad code')->call('save')->assertHasErrors(['name' => 'unique', 'code']);

        // a department that still has staff cannot be deleted: the page refuses to open
        Livewire::actingAs($this->admin)->test(DeleteDepartment::class, ['department' => $this->dept])->assertRedirect(route('admin.departments'));
        $this->assertDatabaseHas('departments', ['id' => $this->dept->id]);
    }
}
