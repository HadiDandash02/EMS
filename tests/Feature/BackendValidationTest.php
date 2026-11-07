<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\UserForm;
use App\Livewire\Tasks\TaskForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Validation is enforced and explained by the SERVER; the browser's own bubbles are switched off (novalidate). */
class BackendValidationTest extends TestCase
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

    public function test_every_form_switches_off_browser_validation(): void
    {
        foreach (glob(resource_path('views/{auth,livewire/*}/*.blade.php'), GLOB_BRACE) as $file) {
            preg_match_all('/<form\b[^>]*>/', file_get_contents($file), $forms);
            foreach ($forms[0] as $tag) {
                if (str_contains($tag, 'logout')) {
                    continue;   // the log-out button has no fields
                }
                $this->assertStringContainsString('novalidate', $tag, basename($file).' has a form that relies on browser validation');
            }
        }
    }

    public function test_creating_a_user_with_missing_fields_tells_the_admin_what_to_complete(): void
    {
        $page = Livewire::actingAs($this->admin)->test(UserForm::class)->call('save');

        $page->assertHasErrors(['name' => 'required', 'username' => 'required', 'email' => 'required', 'password' => 'required', 'department_id' => 'required', 'job_title' => 'required']);
        $page->assertSee('Please complete the required fields and fix the highlighted problems, then try again.')
            ->assertSee('is required – please complete it.');

        $this->assertDatabaseCount('users', User::count());   // nothing was created
    }

    public function test_creating_a_task_without_a_title_is_refused_by_the_server(): void
    {
        Livewire::actingAs($this->employee)->test(TaskForm::class)->call('save')
            ->assertHasErrors(['title' => 'required'])
            ->assertSee('Please complete the required fields');
    }

    public function test_login_with_empty_fields_is_refused_by_the_server_with_a_clear_message(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['login', 'password']);

        $this->followingRedirects()->from('/login')->post('/login', [])->assertSee('Please complete the required fields');
    }

    public function test_the_api_also_validates_on_the_server(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->employee);

        $this->postJson('/api/tasks', [])->assertUnprocessable()->assertJsonValidationErrors(['title', 'priority']);
    }
}
