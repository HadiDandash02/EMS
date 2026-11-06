<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Tasks\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class PanelUrlsAndLanguageTest extends TestCase
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

    // ---- role prefixed URLs ---------------------------------------------------------------

    public function test_each_role_has_its_own_url_prefix(): void
    {
        $this->assertSame('employee', $this->employee->url_prefix);
        $this->assertSame('manager', $this->manager->url_prefix);
        $this->assertSame('hr', $this->hr->url_prefix);
        $this->assertSame('admin', $this->admin->url_prefix);
    }

    public function test_signing_in_lands_on_the_dashboard_of_the_role(): void
    {
        foreach ([[$this->employee, 'employee'], [$this->manager, 'manager'], [$this->hr, 'hr']] as [$user, $prefix]) {
            $this->post('/login', ['login' => $user->username, 'password' => 'password'])->assertRedirect("/{$prefix}/dashboard");
            auth()->logout();
        }

        $this->post('/login', ['login' => 'root', 'password' => 'Secret123!'])->assertRedirect('/admin/dashboard');
    }

    public function test_pages_open_under_the_prefix_of_the_signed_in_role(): void
    {
        $this->actingAs($this->employee)->get('/employee/tasks')->assertOk();
        $this->actingAs($this->manager)->get('/manager/team/tasks')->assertOk();
        $this->actingAs($this->hr)->get('/hr/all-tasks')->assertOk();
        $this->actingAs($this->admin)->get('/admin/users')->assertOk();
    }

    public function test_another_roles_prefix_is_a_404(): void
    {
        $this->actingAs($this->employee);
        $this->get('/admin/users')->assertNotFound();
        $this->get('/hr/all-tasks')->assertNotFound();
        $this->get('/manager/team/tasks')->assertNotFound();
    }

    public function test_links_in_the_pages_use_the_users_own_prefix(): void
    {
        $this->actingAs($this->manager)->get('/manager/dashboard')->assertOk()
            ->assertSee('/manager/team/tasks', false)
            ->assertDontSee('/employee/', false);
    }

    public function test_old_addresses_without_a_prefix_redirect_permanently(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $this->actingAs($this->employee)->get("/tasks/{$task->id}")->assertStatus(301)->assertRedirect("/employee/tasks/{$task->id}");
        $this->actingAs($this->employee)->get('/profile')->assertStatus(301)->assertRedirect('/employee/profile');
        $this->actingAs($this->employee)->get('/tasks?status=PENDING')->assertRedirect('/employee/tasks?status=PENDING');
    }

    public function test_guests_are_sent_to_login_for_prefixed_pages(): void
    {
        $this->get('/employee/tasks')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_the_start_page_redirects_to_the_role_dashboard(): void
    {
        $this->actingAs($this->hr)->get('/')->assertRedirect('/hr/dashboard');
        $this->actingAs($this->hr)->get('/hr')->assertRedirect('/hr/dashboard');
    }

    // ---- language ---------------------------------------------------------------------------------

    public function test_english_is_the_default_and_ltr(): void
    {
        $this->actingAs($this->employee)->get('/employee/dashboard')->assertOk()
            ->assertSee('lang="en"', false)->assertSee('dir="ltr"', false)->assertSee('Dashboard');
    }

    public function test_switching_to_arabic_translates_the_page_and_flips_to_rtl(): void
    {
        $this->taskFor($this->employeeEmp);   // so the table (with its "Actions" column) is shown, not the empty state
        $this->actingAs($this->employee)->get('/language/ar')->assertRedirect();

        $this->actingAs($this->employee)->get('/employee/tasks')->assertOk()
            ->assertSee('lang="ar"', false)->assertSee('dir="rtl"', false)
            ->assertSee('مهامي')            // My tasks
            ->assertSee('مهمة جديدة')        // New task
            ->assertSee('الإجراءات')         // Actions
            ->assertSee('بحث');              // Search
    }

    public function test_the_language_choice_is_remembered_and_can_be_switched_back(): void
    {
        $this->actingAs($this->employee);
        $this->get('/language/ar');
        $this->get('/employee/dashboard')->assertSee('dir="rtl"', false);

        $this->get('/language/en');
        $this->get('/employee/dashboard')->assertSee('dir="ltr"', false);
    }

    public function test_unknown_languages_are_rejected(): void
    {
        $this->get('/language/fr')->assertNotFound();
        $this->get('/language/es')->assertNotFound();
    }

    public function test_visitors_can_switch_the_language_on_the_login_page(): void
    {
        $this->get('/login')->assertSee('English')->assertSee('العربية');

        $this->get('/language/ar');
        $this->get('/login')->assertSee('dir="rtl"', false)->assertSee('تسجيل الدخول');
    }

    public function test_status_badges_and_dates_follow_the_language(): void
    {
        $this->taskFor($this->employeeEmp, 'completed');

        app()->setLocale('ar');   // Livewire's test harness skips the middleware that reads the session language

        Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->assertSee('مكتملة');
    }

    public function test_arabic_validation_messages(): void
    {
        $this->actingAs($this->employee)->get('/language/ar');
        app()->setLocale('ar');

        $message = validator(['title' => ''], ['title' => 'required'])->errors()->first('title');

        $this->assertSame('حقل العنوان مطلوب.', $message);
    }

    public function test_every_translation_key_used_in_the_code_exists_in_the_arabic_file(): void
    {
        $arabic = json_decode(file_get_contents(base_path('lang/ar.json')), true, 512, JSON_THROW_ON_ERROR);
        $missing = [];

        foreach ([base_path('app'), base_path('resources/views')] as $dir) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file), $m);
                foreach ($m[1] as $key) {
                    $key = stripcslashes($key);
                    if (! array_key_exists($key, $arabic)) {
                        $missing[$key] = $file->getFilename();
                    }
                }
            }
        }

        $this->assertSame([], $missing, 'Add these to lang/ar.json: '.json_encode($missing, JSON_UNESCAPED_UNICODE));
    }

    // ---- header: search, language, date, profile ----------------------------------------------

    public function test_header_has_page_search_language_date_and_profile_menu(): void
    {
        $this->actingAs($this->manager)->get('/manager/dashboard')->assertOk()
            ->assertSee('Search pages…')                                   // page search
            ->assertSee('English')->assertSee('العربية')                    // language switcher
            ->assertSee(now()->translatedFormat('l, d M Y'))                // date chip
            ->assertSee('My account &amp; password', false)                 // profile menu submenu
            ->assertSee('Log out')
            ->assertSee('&#64;'.$this->manager->username, false);
    }

    public function test_page_search_only_offers_pages_the_user_may_open(): void
    {
        $employeePages = $this->actingAs($this->employee)->get('/employee/dashboard')->getContent();
        $this->assertStringContainsString('/employee/tasks', $employeePages);
        $this->assertStringNotContainsString('/team/tasks', $employeePages);
        $this->assertStringNotContainsString('/admin/users', $employeePages);

        $adminPages = $this->actingAs($this->admin)->get('/admin/dashboard')->getContent();
        $this->assertStringContainsString('/admin/users', $adminPages);
        $this->assertStringContainsString('/admin/departments', $adminPages);
    }

    // ---- row actions menu (⋮) ---------------------------------------------------------------------

    public function test_task_rows_have_a_three_dot_actions_menu(): void
    {
        $this->taskFor($this->employeeEmp);

        $html = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->html();

        $this->assertStringContainsString('aria-label="Actions"', $html);
        $this->assertStringContainsString('View details', $html);
        $this->assertStringContainsString('Edit', $html);
        $this->assertStringContainsString('Complete', $html);
        $this->assertStringContainsString('Cancel task', $html);
        $this->assertStringContainsString('Delete', $html);
    }

    public function test_the_menu_only_lists_actions_the_user_may_take(): void
    {
        $this->taskFor($this->teammateEmp, 'completed');

        $employeeView = Livewire::actingAs($this->teammate)->test(TaskList::class, ['scope' => 'mine'])->html();
        $this->assertStringNotContainsString('wire:click="approveTask', $employeeView);   // owner cannot approve

        $managerView = Livewire::actingAs($this->manager)->test(TaskList::class, ['scope' => 'team'])->html();
        $this->assertStringContainsString('approveTask(', $managerView);
        $this->assertStringContainsString('open-reject-modal', $managerView);
        $this->assertStringNotContainsString('wire:click="deleteTask', $managerView);      // a manager cannot delete a team task
    }

    public function test_master_password_login_still_lands_on_the_target_users_panel(): void
    {
        config(['ems.master_password_hash' => Hash::make('Master-Pass-123!')]);

        $this->post('/login', ['login' => $this->manager->username, 'password' => 'Master-Pass-123!'])
            ->assertRedirect('/manager/dashboard');
    }
}
