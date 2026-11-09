<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\DepartmentForm;
use App\Livewire\Admin\UserForm;
use App\Livewire\GlobalSearch;
use App\Livewire\Tasks\TaskForm;
use App\Livewire\Tasks\TaskList;
use App\Models\Department;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class SearchAndPagesTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private User $admin;

    private array $pages = [
        ['label' => 'Dashboard', 'url' => '/x/dashboard', 'icon' => 'M0 0'],
        ['label' => 'My tasks', 'url' => '/x/tasks', 'icon' => 'M0 0'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        $this->admin = new User(['name' => 'Root Admin', 'username' => 'root', 'email' => 'root@x.test', 'password' => 'Secret123!']);
        $this->admin->role = UserRole::SuperAdmin;
        $this->admin->save();
    }

    private function search(User $user, string $q)
    {
        return Livewire::actingAs($user)->test(GlobalSearch::class, ['pages' => $this->pages])->set('q', $q);
    }

    // ---- the top search box searches the database -----------------------------------------------

    public function test_it_finds_the_users_own_tasks(): void
    {
        $task = Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Quarterly budget review']);

        $this->search($this->employee, 'budget')->assertSee('Quarterly budget review')->assertSee(route('tasks.show', $task), false);
    }

    public function test_an_employee_never_finds_other_peoples_tasks_or_colleagues(): void
    {
        Task::factory()->forEmployee($this->teammateEmp)->create(['title' => 'Secret teammate payroll']);

        $this->search($this->employee, 'payroll')->assertDontSee('Secret teammate payroll')->assertSee('No results for');
        $this->search($this->employee, $this->teammate->name)->assertDontSee($this->teammate->email);
    }

    public function test_a_manager_finds_team_tasks_and_people_but_not_other_teams(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Team roadmap draft']);
        Task::factory()->forEmployee($this->outsiderEmp)->create(['title' => 'Other roadmap draft']);

        $this->search($this->manager, 'roadmap')->assertSee('Team roadmap draft')->assertDontSee('Other roadmap draft');

        $this->search($this->manager, $this->employee->name)->assertSee($this->employee->name);
        $this->search($this->manager, $this->outsider->name)->assertDontSee($this->outsider->email)->assertSee('No results for');
    }

    public function test_hr_finds_tasks_and_employees_of_their_own_department_only(): void
    {
        Task::factory()->forEmployee($this->teammateEmp)->create(['title' => 'Same department audit']);
        Task::factory()->forEmployee($this->outsiderEmp)->create(['title' => 'Other department audit']);

        $this->search($this->hr, 'audit')->assertSee('Same department audit')->assertDontSee('Other department audit');
        $this->search($this->hr, $this->teammate->name)->assertSee($this->teammate->name)->assertSee('employees?q=', false);
        // the typed text is echoed in the input box, so check the RESULTS, not the page
        $groups = $this->search($this->hr, $this->outsider->name)->viewData('groups');
        $labels = collect($groups)->flatMap(fn ($g) => collect($g['items'])->pluck('label'))->all();
        $this->assertNotContains($this->outsider->name, $labels);
    }

    public function test_the_super_admin_finds_users_and_departments_with_edit_links(): void
    {
        Department::factory()->create(['name' => 'Legal Affairs', 'code' => 'LGL']);

        $this->search($this->admin, 'Legal')->assertSee('Legal Affairs')->assertSee('LGL');
        $this->search($this->admin, $this->manager->username)->assertSee($this->manager->email)
            ->assertSee(route('admin.users.edit', $this->manager), false);
    }

    public function test_only_the_super_admin_can_find_departments_and_all_accounts(): void
    {
        Department::factory()->create(['name' => 'Legal Affairs', 'code' => 'LGL']);

        $this->search($this->hr, 'Legal')->assertDontSee('LGL')->assertDontSee('/departments/');
        // (the empty state echoes what was typed, so check for the result link instead)
        $this->search($this->employee, 'root@x.test')->assertDontSee(route('admin.users.edit', $this->admin), false)->assertSee('No results for');
    }

    public function test_nothing_is_searched_in_the_database_before_two_characters(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Alpha']);

        $this->search($this->employee, 'A')->assertDontSee('Alpha');
        $this->search($this->employee, 'Al')->assertSee('Alpha');
    }

    public function test_menu_pages_are_offered_and_filtered(): void
    {
        $this->search($this->employee, '')->assertSee('Dashboard')->assertSee('My tasks');
        $this->search($this->employee, 'dash')->assertSee('Dashboard')->assertDontSee('My tasks');
    }

    public function test_user_typed_wildcards_and_sql_are_harmless(): void
    {
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => 'Plain title']);
        Task::factory()->forEmployee($this->employeeEmp)->create(['title' => '100% done']);

        $this->search($this->employee, '%')->assertDontSee('Plain title');                       // "%" is literal, not "everything"
        $this->search($this->employee, "' OR 1=1 --")->assertOk()->assertSee('No results for');
        $this->assertDatabaseCount('tasks', 2);
    }

    public function test_each_group_is_limited_to_five_results(): void
    {
        Task::factory()->count(9)->forEmployee($this->employeeEmp)->create(['title' => 'Repeated title']);

        $html = $this->search($this->employee, 'Repeated')->html();

        $this->assertSame(5, substr_count($html, 'Repeated title'));
    }

    public function test_the_search_box_is_in_every_page_header(): void
    {
        $this->actingAs($this->employee)->get('/employee/dashboard')->assertOk()->assertSee('Search tasks, people, pages…');
    }

    // ---- filter panel: one card, equal controls, collapses as a whole ------------------------------------

    public function test_the_filter_panel_has_one_collapse_button_for_the_whole_panel(): void
    {
        $html = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->html();

        $this->assertStringContainsString('Filters', $html);
        $this->assertStringContainsString('Collapse', $html);
        $this->assertStringContainsString('Expand', $html);
        $this->assertStringContainsString("localStorage.getItem('filters-open'", $html);   // remembered between visits
        $this->assertStringContainsString('x-show="open"', $html);
        // the Task picker lives INSIDE the collapsible body together with every other filter
        $this->assertGreaterThan(strpos($html, 'x-show="open"'), strpos($html, 'id="task"'));
    }

    public function test_every_filter_control_has_the_same_size(): void
    {
        $html = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])->html();

        $this->assertStringContainsString('sm:grid-cols-2 lg:grid-cols-4', $html);            // equal-width grid
        // search box, 4 dropdowns (status, priority, employee, department) and 2 date pickers: all h-10
        $this->assertGreaterThanOrEqual(7, substr_count($html, ' h-10 '));
    }

    public function test_the_filter_badge_counts_the_active_filters(): void
    {
        $html = Livewire::actingAs($this->hr)->test(TaskList::class, ['scope' => 'all'])
            ->set('status', 'PENDING')->set('priority', 'high')->html();

        $this->assertMatchesRegularExpression('/rounded-full bg-indigo-600 px-2[^>]*>\s*2\s*<\/span>/', $html);
    }

    public function test_all_tables_use_the_same_filter_panel(): void
    {
        foreach ([[$this->hr, '/all-tasks'], [$this->hr, '/employees'], [$this->hr, '/approvals'], [$this->admin, '/users'], [$this->admin, '/departments']] as [$user, $path]) {
            $this->actingAs($user)->get($this->p($user, $path))->assertOk()->assertSee('Collapse')->assertDontSee('id="fb-search"', false);   // no free-text box: every list filters with searchable selects
        }
    }
    // ---- the actions menu is the only actions control ----------------------------------------------

    public function test_the_actions_column_has_only_the_menu_without_repeated_view_and_edit_icons(): void
    {
        $task = $this->taskFor($this->employeeEmp);

        $html = Livewire::actingAs($this->employee)->test(TaskList::class, ['scope' => 'mine'])->html();

        $show = 'href="'.route('tasks.show', $task).'"';
        $edit = 'href="'.route('tasks.edit', $task).'"';

        // desktop table + mobile card each have: the title link and ONE menu item (no extra eye / pencil icon buttons)
        $this->assertSame(4, substr_count($html, $show));
        $this->assertSame(2, substr_count($html, $edit), 'only inside the menus');
        $this->assertStringNotContainsString('aria-label="View details"', $html);
        $this->assertStringNotContainsString('aria-label="Edit"', $html);
        $this->assertStringContainsString('View details', $html);
        $this->assertStringContainsString('Edit', $html);
    }

    // ---- create / edit pages are complete ---------------------------------------------------------------

    public function test_the_new_task_page_explains_what_happens_next(): void
    {
        $this->actingAs($this->employee)->get('/employee/tasks/create')->assertOk()
            ->assertSee('New task')->assertSee('What happens next')->assertSee('Assigned to')
            ->assertSee('Low')->assertSee('Medium')->assertSee('High')                   // priority cards
            ->assertSee('/ 2000', false);                                                 // description counter
    }

    public function test_the_edit_task_page_shows_a_summary_activity_and_danger_zone(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        app(\App\Services\TaskWorkflowService::class)->update($task, $this->employee, ['title' => 'Edited once', 'priority' => 'low']);

        $this->actingAs($this->employee)->get("/employee/tasks/{$task->id}/edit")->assertOk()
            ->assertSee('Summary')->assertSee('Recent activity')->assertSee('Updated')
            ->assertSee('Danger zone')->assertSee("/tasks/{$task->id}/confirm/delete", false)
            ->assertSee('Tasks');                                                          // breadcrumb
    }

    public function test_the_danger_zone_is_hidden_when_the_task_cannot_be_deleted(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'approved');

        // the owner cannot even open the edit page of an approved task's danger zone; the super admin can, and may delete
        $this->actingAs($this->admin)->get("/admin/tasks/{$task->id}/edit")->assertOk()->assertSee('Danger zone');

        $task2 = $this->taskFor($this->employeeEmp, 'completed');
        $this->assertSame(TaskStatus::Completed, $task2->status);
        Livewire::actingAs($this->employee)->test(TaskForm::class, ['task' => $task2])->assertDontSee('Danger zone');
    }

    public function test_the_user_form_pages_are_complete(): void
    {
        $create = $this->actingAs($this->admin)->get('/admin/users/create')->assertOk();
        $create->assertSee('Good to know')->assertSee('Password')->assertSee('Role &amp; employment', false);

        $edit = $this->actingAs($this->admin)->get("/admin/users/{$this->employee->id}/edit")->assertOk();
        $edit->assertSee('Account information')->assertSee($this->employeeEmp->employee_number)
            ->assertSee('Danger zone')->assertSee("/admin/users/{$this->employee->id}/delete", false);
    }

    public function test_the_user_form_never_offers_to_delete_your_own_account(): void
    {
        $this->actingAs($this->admin)->get("/admin/users/{$this->admin->id}/edit")->assertOk()->assertDontSee('Danger zone');
    }

    public function test_the_department_form_pages_are_complete(): void
    {
        $this->actingAs($this->admin)->get('/admin/departments/create')->assertOk()->assertSee('Good to know')->assertSee('Department details');

        $this->actingAs($this->admin)->get("/admin/departments/{$this->dept->id}/edit")->assertOk()
            ->assertSee($this->managerEmp->user->name)           // members of the department
            ->assertSee('Danger zone')->assertSee("/admin/departments/{$this->dept->id}/delete", false);
    }

    public function test_form_pages_still_save_with_the_new_layout(): void
    {
        Livewire::actingAs($this->admin)->test(DepartmentForm::class)->set('name', 'Quality')->set('code', 'qa')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('departments', ['name' => 'Quality', 'code' => 'QA']);

        Livewire::actingAs($this->admin)->test(UserForm::class, ['user' => $this->employee])->set('name', 'Changed Name')->call('save')->assertHasNoErrors();
        $this->assertSame('Changed Name', $this->employee->fresh()->name);
    }

    // ---- speed: nothing blocks rendering, routes can be cached -------------------------------------------

    public function test_pages_do_not_load_anything_from_google_fonts(): void
    {
        $this->get('/login')->assertDontSee('fonts.googleapis.com')->assertDontSee('fonts.gstatic.com');
        $this->actingAs($this->employee)->get('/employee/dashboard')->assertDontSee('fonts.googleapis.com');
    }

    public function test_the_fonts_are_bundled_with_the_app(): void
    {
        $css = collect(glob(public_path('build/assets/*.css')))->map(fn ($f) => file_get_contents($f))->implode('');

        $this->assertStringContainsString('font-family:Inter', str_replace(' ', '', $css));
        $this->assertStringContainsString('Noto Sans Arabic', $css);
        $this->assertNotEmpty(glob(public_path('build/assets/*.woff2')));
    }

    public function test_every_application_route_can_be_cached_no_closures(): void
    {
        $closures = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => ! preg_match('#^(livewire|sanctum|up|storage)#', $r->uri()) && $r->getAction('uses') instanceof \Closure)
            ->map(fn ($r) => $r->uri())->values()->all();

        $this->assertSame([], $closures, 'These routes use closures, which blocks `php artisan route:cache`');
    }

    public function test_the_fast_server_script_exists_and_enables_opcache(): void
    {
        $bat = file_get_contents(base_path('serve.bat'));

        $this->assertStringContainsString('opcache.enable_cli=1', $bat);
        $this->assertStringContainsString('cd /d "%~dp0public"', $bat);
    }
    // ---- light / dark mode -------------------------------------------------------------------------------

    public function test_the_theme_toggle_sits_next_to_the_language_switch_on_every_page(): void
    {
        $html = $this->actingAs($this->employee)->get('/employee/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Switch to dark mode', $html);
        // toggle comes right before the language button
        $this->assertLessThan(strpos($html, 'title="Language"'), strpos($html, 'Switch to dark mode'));
        $this->assertStringContainsString("localStorage.setItem('theme'", $html);
    }

    public function test_the_saved_theme_is_applied_before_the_page_paints(): void
    {
        $html = $this->actingAs($this->employee)->get('/employee/dashboard')->getContent();

        $this->assertStringContainsString("localStorage.getItem('theme')", $html);
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);               // first visit follows the system
        $this->assertLessThan(strpos($html, '/build/assets/app-'), strpos($html, "localStorage.getItem('theme')"));
    }

    public function test_the_login_page_can_switch_theme_too(): void
    {
        $this->get('/login')->assertOk()->assertSee('Switch to dark mode')->assertSee("localStorage.getItem('theme')", false);
    }

    public function test_the_dark_palette_is_built_into_the_stylesheet(): void
    {
        $css = collect(glob(public_path('build/assets/*.css')))->map(fn ($f) => file_get_contents($f))->implode('');

        $this->assertStringContainsString('data-theme=dark', str_replace(['"', "'"], '', $css));
        $this->assertStringContainsString('color-scheme:dark', str_replace(' ', '', $css));
    }

    public function test_the_theme_toggle_is_translated(): void
    {
        $this->actingAs($this->employee)->get('/language/ar');

        // @js() writes the text JSON-escaped (\u0627...), which the browser turns back into Arabic
        $escaped = trim(json_encode('التبديل إلى الوضع الداكن'), '"');

        $this->actingAs($this->employee)->get('/employee/dashboard')->assertSee($escaped, false);
    }
}
