<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/**
 * Validation exists on BOTH sides: the browser tells the user at once (resources/js/form-validation.js, driven by
 * standard HTML attributes), and the server still validates everything (see BackendValidationTest and the form tests).
 */
class FrontEndValidationTest extends TestCase
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

    public function test_every_form_with_fields_has_front_end_validation_switched_on(): void
    {
        foreach (glob(resource_path('views/{auth,livewire/*}/*.blade.php'), GLOB_BRACE) as $file) {
            preg_match_all('/<form\b[^>]*>/', file_get_contents($file), $forms);
            foreach ($forms[0] as $tag) {
                if (str_contains($tag, 'logout') || str_contains($tag, 'applyFilters')) {
                    continue;   // log-out has no fields; the filter panel has optional filters only
                }
                $this->assertStringContainsString('data-validate', $tag, basename($file).': form without front-end validation');
                $this->assertStringContainsString('novalidate', $tag);
            }
        }
    }

    public function test_the_script_and_its_translated_messages_are_on_every_page_that_has_a_form(): void
    {
        $this->assertFileExists(resource_path('js/form-validation.js'));
        $this->assertStringContainsString("import './form-validation'", file_get_contents(resource_path('js/app.js')));

        $this->get('/login')->assertOk()->assertSee('window.validationMessages', false)->assertSee('please complete it', false);
        $this->actingAs($this->admin)->get('/admin/users/create')->assertOk()->assertSee('window.validationMessages', false);
    }

    public function test_the_new_user_form_declares_its_rules_as_html_constraints(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/users/create')->assertOk()->getContent();

        foreach ([
            'id="uf-name"' => ['required', 'minlength="2"'],
            'id="uf-username"' => ['required', 'pattern="[A-Za-z0-9_.]{3,30}"'],
            'id="uf-email"' => ['required', 'type="email"'],
            'id="uf-job"' => ['required'],
            'id="uf-number"' => ['required', 'pattern="[A-Za-z0-9\-]+"'],
            'id="uf-password"' => ['required', 'data-strong'],
            'id="uf-dept-value"' => ['required'],            // the required department select
        ] as $anchor => $needles) {
            $this->assertMatchesRegularExpression('/<[^>]*'.preg_quote($anchor, '/').'[^>]*>/', $html, "missing {$anchor}");
            preg_match('/<[^>]*'.preg_quote($anchor, '/').'[^>]*>/', $html, $tag);
            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $tag[0], "{$anchor} lacks {$needle}");
            }
        }
    }

    public function test_the_reject_task_and_department_forms_declare_constraints_too(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');

        $this->actingAs($this->manager)->get($this->p($this->manager, "/tasks/{$task->id}/reject"))->assertOk()
            ->assertSee('minlength="5"', false)->assertSee('required', false);
        $this->actingAs($this->admin)->get('/admin/departments/create')->assertOk()
            ->assertSee('pattern="[A-Za-z0-9]{2,10}"', false);
    }

    public function test_the_server_still_validates_whatever_the_browser_sends(): void
    {
        // front-end checks can be bypassed (devtools, curl): the back end must refuse the same bad data on its own
        \Livewire\Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\UserForm::class)
            ->set('employee_number', '')->call('save')
            ->assertHasErrors(['name' => 'required', 'username' => 'required', 'email' => 'required', 'employee_number' => 'required', 'department_id' => 'required', 'password' => 'required']);
    }
}
