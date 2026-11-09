<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** Five wrong passwords lock that sign-in for five minutes (website and API). */
class LoginLockoutTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    private User $victim;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
        $this->victim = $this->employee;       // factory password is "password"
    }

    private function wrong(string $login = null)
    {
        return $this->from('/login')->post('/login', ['login' => $login ?? $this->victim->username, 'password' => 'definitely-wrong']);
    }

    public function test_the_user_is_warned_with_the_attempts_left(): void
    {
        $this->wrong()->assertSessionHasErrors('login');   // 1st wrong password

        $this->assertStringContainsString('These credentials do not match our records.', session('errors')->first('login'));
        $this->assertStringContainsString('4 attempts left', session('errors')->first('login'));

        $this->wrong();
        $this->wrong();
        $this->assertStringContainsString('2 attempts left', session('errors')->first('login'));

        $this->wrong();
        $this->assertStringContainsString('1 attempt left', session('errors')->first('login'));
    }

    public function test_the_fifth_wrong_password_locks_sign_in_for_five_minutes_even_for_the_right_password(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }
        $this->assertStringContainsString('locked for 5 minutes', session('errors')->first('login'));
        $this->assertGreaterThan(0, session('login_locked_until') - time());

        // now even the CORRECT password is refused
        $this->from('/login')->post('/login', ['login' => $this->victim->username, 'password' => 'password'])
            ->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertStringContainsString('Too many failed sign-in attempts', session('errors')->first('login'));
    }

    public function test_the_lock_ends_after_five_minutes(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }
        $this->assertGuest();

        $this->travel(290)->seconds();                                               // 4:50 later: still locked
        $this->post('/login', ['login' => $this->victim->username, 'password' => 'password']);
        $this->assertGuest();

        $this->travel(15)->seconds();                                                // past 5:00 in total
        $this->post('/login', ['login' => $this->victim->username, 'password' => 'password'])->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($this->victim);
    }

    public function test_a_successful_login_resets_the_counter(): void
    {
        foreach (range(1, 4) as $i) {
            $this->wrong();
        }
        $this->post('/login', ['login' => $this->victim->username, 'password' => 'password'])->assertRedirect('/employee/dashboard');
        auth()->logout();

        $this->wrong();                                                               // counts as the FIRST failure again
        $this->assertStringContainsString('4 attempts left', session('errors')->first('login'));
    }

    public function test_the_lock_is_per_login_name_so_other_users_are_not_affected(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        $this->post('/login', ['login' => $this->manager->username, 'password' => 'password'])->assertRedirect('/manager/dashboard');
    }

    public function test_the_login_page_shows_a_countdown_and_disables_the_button_while_locked(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        $this->followingRedirects()->from('/login')->post('/login', ['login' => $this->victim->username, 'password' => 'password'])
            ->assertSee('Sign-in is locked. You can try again in')->assertSee('left > 0', false);
    }

    public function test_the_api_login_is_locked_the_same_way_with_429_and_retry_after(): void
    {
        foreach (range(1, 4) as $i) {
            $this->postJson('/api/login', ['login' => $this->victim->username, 'password' => 'nope'])->assertUnprocessable();
        }

        $this->postJson('/api/login', ['login' => $this->victim->username, 'password' => 'nope'])
            ->assertStatus(429)->assertJsonPath('retry_after', 300)->assertHeader('Retry-After', '300');

        $this->postJson('/api/login', ['login' => $this->victim->username, 'password' => 'password'])->assertStatus(429);
    }

    public function test_the_helper_counts_and_forgets(): void
    {
        $this->assertSame(4, LoginThrottle::fail('someone', '1.2.3.4'));
        LoginThrottle::clear('someone', '1.2.3.4');
        $this->assertSame(4, LoginThrottle::fail('someone', '1.2.3.4'));
        $this->assertSame(0, LoginThrottle::lockedFor('someone', '1.2.3.4'));
    }
}
