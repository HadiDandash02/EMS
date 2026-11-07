<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\UserManager;
use App\Livewire\Tasks\TaskList;
use App\Models\Task;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class PaginationBrandingTest extends TestCase
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

    // ---- pagination ------------------------------------------------------------------------------

    public function test_every_table_shows_the_range_the_rows_per_page_and_page_buttons(): void
    {
        Task::factory()->count(25)->forEmployee($this->employeeEmp)->create();

        $html = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->html();

        $this->assertStringContainsString('Showing', $html);
        $this->assertStringContainsString('Rows per page', $html);
        $this->assertStringContainsString('wire:click="gotoPage(2)"', $html);
        $this->assertStringContainsString('wire:click="gotoPage(3)"', $html);   // 25 rows / 10 per page = 3 pages
        $this->assertStringContainsString('wire:click="nextPage"', $html);
    }

    public function test_the_footer_is_visible_even_when_everything_fits_on_one_page(): void
    {
        $this->taskFor($this->employeeEmp);

        $html = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->html();

        $this->assertStringContainsString('Rows per page', $html);
        $this->assertStringNotContainsString('gotoPage(2)', $html);
    }

    public function test_pages_can_be_navigated(): void
    {
        Task::factory()->count(25)->forEmployee($this->employeeEmp)->create();

        $page = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all']);
        $this->assertSame(1, $page->viewData('tasks')->firstItem());

        $page->call('nextPage');
        $this->assertSame(11, $page->viewData('tasks')->firstItem());
        $this->assertSame(2, $page->viewData('tasks')->currentPage());

        $page->call('gotoPage', 3);
        $this->assertSame(5, $page->viewData('tasks')->count());   // 25 = 10 + 10 + 5
    }

    public function test_rows_per_page_can_be_changed_and_resets_to_the_first_page(): void
    {
        Task::factory()->count(30)->forEmployee($this->employeeEmp)->create();

        $page = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->call('gotoPage', 2);

        $page->set('perPage', 25);
        $this->assertSame(25, $page->viewData('tasks')->count());
        $this->assertSame(1, $page->viewData('tasks')->currentPage());
    }

    public function test_a_tampered_rows_per_page_value_falls_back_to_10(): void
    {
        Task::factory()->count(30)->forEmployee($this->employeeEmp)->create();

        $page = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->set('perPage', 100000);

        $this->assertSame(10, $page->viewData('tasks')->count());
    }

    public function test_clearing_filters_keeps_the_chosen_rows_per_page(): void
    {
        Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])
            ->set('perPage', 50)->set('search', 'x')->call('clearFilters')
            ->assertSet('search', '')->assertSet('perPage', 50);
    }

    public function test_admin_tables_paginate_too(): void
    {
        User::factory()->count(15)->create();

        $page = Livewire::actingAs($this->admin)->test(UserManager::class);

        $this->assertSame(10, $page->viewData('users')->count());
        $this->assertGreaterThan(10, $page->viewData('users')->total());
        $this->assertStringContainsString('Rows per page', $page->html());
    }

    // ---- logo and brand ----------------------------------------------------------------------

    public function test_the_logo_and_brand_name_appear_in_the_sidebar_and_login_page(): void
    {
        $this->assertFileExists(public_path('logo.svg'));
        $this->assertFileExists(public_path('favicon.svg'));

        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()
            ->assertSee('TaskFlow')->assertSee('logo.svg', false)
            ->assertSee('Super Admin panel')
            ->assertDontSee('Task Management');

        auth()->logout();
        $this->get('/login')->assertOk()->assertSee('TaskFlow')->assertSee('logo.svg', false);
    }

    // ---- buttons -----------------------------------------------------------------------------

    public function test_primary_actions_use_the_shared_attractive_button_style(): void
    {
        $this->actingAs($this->employee)->get('/employee/tasks')->assertOk()
            ->assertSee('btn btn-primary', false)->assertSee('New task');

        $this->actingAs($this->employee)->get('/employee/tasks/create')->assertOk()
            ->assertSee('btn btn-primary', false)->assertSee('btn btn-secondary', false);

        $css = collect(glob(public_path('build/assets/*.css')))->map(fn ($f) => file_get_contents($f))->implode('');
        $this->assertStringContainsString('.btn-primary', $css);
        $this->assertStringContainsString('.btn-danger', $css);
    }

    // ---- no popups -------------------------------------------------------------------------------

    public function test_pages_contain_no_popup_dialogs_or_browser_confirmations(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $pages = [
            [$this->employee, "/tasks"], [$this->employee, "/tasks/{$task->id}"], [$this->employee, '/tasks/create'],
            [$this->employee, '/profile'], [$this->manager, '/team/tasks'], [$this->hr, '/all-tasks'], [$this->hr, '/employees'],
            [$this->admin, '/users'], [$this->admin, '/departments'], [$this->admin, '/users/create'],
        ];

        foreach ($pages as [$user, $path]) {
            $html = $this->actingAs($user)->get($this->p($user, $path))->assertOk()->getContent();

            $this->assertStringNotContainsString('wire:confirm', $html, "popup confirm on {$path}");
            $this->assertStringNotContainsString('aria-modal', $html, "modal dialog on {$path}");
            $this->assertStringNotContainsString('open-task-form', $html, "popup form trigger on {$path}");
            $this->assertStringNotContainsString('pointer-events-none fixed inset-x-0 top-4', $html, "floating toast on {$path}");
        }
    }

    public function test_messages_appear_inline_on_the_page(): void
    {
        $this->withSession(['success' => 'Task created.'])->actingAs($this->employee)->get('/employee/tasks')
            ->assertOk()->assertSee('role="alert"', false)->assertSee('Task created.');

        $this->withSession(['error' => 'Something went wrong.'])->actingAs($this->employee)->get('/employee/tasks')
            ->assertSee('Something went wrong.');
    }

    public function test_start_shows_an_inline_message_through_the_page_not_a_popup(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])
            ->call('startTask', $task->id)
            ->assertDispatched('notify', type: 'success', message: 'Task started.');
    }

    // ---- strong password UI -------------------------------------------------------------------

    public function test_password_fields_show_a_live_strength_checklist(): void
    {
        foreach ([[$this->employee, '/profile'], [$this->admin, '/users/create']] as [$user, $path]) {
            $this->actingAs($user)->get($this->p($user, $path))->assertOk()
                ->assertSee('At least 10 characters')->assertSee('Upper and lower case letters')
                ->assertSee('A number')->assertSee('A symbol');
        }
    }

    public function test_the_password_policy_rejects_weak_and_accepts_strong_passwords(): void
    {
        $passes = fn (string $pw) => ! validator(['p' => $pw], ['p' => [PasswordPolicy::rule()]])->fails();

        $this->assertFalse($passes('short1!A'));
        $this->assertFalse($passes('nouppercase1!'));
        $this->assertFalse($passes('NOLOWERCASE1!'));
        $this->assertFalse($passes('NoNumbersHere!'));
        $this->assertFalse($passes('NoSymbols12345'));
        $this->assertTrue($passes('Str0ng!Passw0rd'));
    }

    public function test_password_rule_messages_are_translated_to_arabic(): void
    {
        app()->setLocale('ar');

        $message = validator(['password' => 'abcdefghijkl'], ['password' => [PasswordPolicy::rule()]])->errors()->first('password');

        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $message);
    }
}
