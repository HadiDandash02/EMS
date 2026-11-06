<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private const MASTER = 'Sup3r-Secret-Master!';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        config(['ems.master_password_hash' => Hash::make(self::MASTER)]);

        $this->admin = new User(['name' => 'Super Admin', 'email' => 'superadmin@x.test', 'password' => self::MASTER]);
        $this->admin->role = UserRole::SuperAdmin;
        $this->admin->save();
    }

    public function test_super_admin_password_logs_you_in_as_any_user(): void
    {
        $this->post('/login', ['email' => $this->employee->email, 'password' => self::MASTER])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($this->employee);
    }

    public function test_the_users_own_password_still_works(): void
    {
        $this->post('/login', ['email' => $this->employee->email, 'password' => 'password'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($this->employee);
    }

    public function test_wrong_password_and_unknown_email_fail(): void
    {
        $this->post('/login', ['email' => $this->employee->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'ghost@x.test', 'password' => self::MASTER])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_master_login_is_audited(): void
    {
        Log::spy();

        $this->post('/login', ['email' => $this->manager->email, 'password' => self::MASTER]);

        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $ctx) => $message === 'Master password login' && $ctx['as_user_id'] === $this->manager->id);
    }

    public function test_feature_is_off_when_no_hash_is_configured(): void
    {
        config(['ems.master_password_hash' => null]);

        $this->post('/login', ['email' => $this->employee->email, 'password' => self::MASTER])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_master_password_works_through_the_api_too(): void
    {
        $this->postJson('/api/login', ['email' => $this->manager->email, 'password' => self::MASTER])
            ->assertOk()
            ->assertJsonPath('user.id', $this->manager->id);
    }

    public function test_super_admin_can_view_everything_but_cannot_approve(): void
    {
        $task = $this->taskFor($this->outsiderEmp, 'completed');

        $this->actingAs($this->admin)->get("/tasks/{$task->id}")->assertOk();
        $this->actingAs($this->admin)->get('/hr/tasks')->assertOk();
        $this->actingAs($this->admin)->get('/hr/employees')->assertOk();
        $this->actingAs($this->admin)->get('/approvals')->assertOk();
        $this->actingAs($this->admin)->get('/dashboard')->assertOk();

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/tasks')->assertOk()->assertJsonPath('meta.total', 1);
        $this->postJson("/api/tasks/{$task->id}/approve")->assertForbidden();
    }

    public function test_regular_users_cannot_enter_admin_areas(): void
    {
        $this->actingAs($this->employee)->get('/hr/employees')->assertForbidden();
    }

    public function test_login_page_exposes_no_demo_credentials(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Demo accounts')->assertDontSee('password: password');
    }

}
