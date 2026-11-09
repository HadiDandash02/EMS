<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/**
 * EVERY failed sign-in from a device counts (any username, any password). The 5th locks sign-in for five minutes:
 * a reload, another browser, a private window, the e-mail instead of the username or the API change nothing.
 */
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

    private function wrong(?string $login = null, string $password = 'definitely-wrong')
    {
        return $this->from('/login')->post('/login', ['login' => $login ?? $this->victim->username, 'password' => $password]);
    }

    private function message(): string
    {
        return (string) session('errors')->first('login');
    }

    public function test_the_user_is_warned_with_the_attempts_left(): void
    {
        $this->wrong()->assertSessionHasErrors('login');
        $this->assertStringContainsString('These credentials do not match our records.', $this->message());
        $this->assertStringContainsString('4 attempts left', $this->message());

        $this->wrong();
        $this->wrong();
        $this->assertStringContainsString('2 attempts left', $this->message());

        $this->wrong();
        $this->assertStringContainsString('1 attempt left', $this->message());
    }

    public function test_any_username_and_any_password_counts_as_an_attempt(): void
    {
        // five DIFFERENT usernames (real, unknown, e-mail, capitals) and different passwords: still five attempts
        $this->wrong('nobody-1', 'a');
        $this->assertStringContainsString('4 attempts left', $this->message());
        $this->wrong($this->manager->username, 'b');
        $this->assertStringContainsString('3 attempts left', $this->message());
        $this->wrong('someone@nowhere.test', 'c');
        $this->assertStringContainsString('2 attempts left', $this->message());
        $this->wrong(strtoupper($this->hr->username), 'd');
        $this->assertStringContainsString('1 attempt left', $this->message());
        $this->wrong('nobody-2', 'e');

        $this->assertStringContainsString('locked for 5 minutes', $this->message());
        $this->assertGuest();
    }

    public function test_the_fifth_wrong_attempt_locks_sign_in_even_for_the_right_password(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }
        $this->assertStringContainsString('locked for 5 minutes', $this->message());

        $this->from('/login')->post('/login', ['login' => $this->victim->username, 'password' => 'password'])
            ->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertStringContainsString('Too many failed sign-in attempts', $this->message());
    }

    public function test_the_lock_covers_the_whole_device_not_only_the_account_that_was_attacked(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        // another, perfectly valid account from the same computer is refused too
        $this->from('/login')->post('/login', ['login' => $this->manager->username, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_the_lock_ends_after_five_minutes(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        $this->travel(290)->seconds();                                                // 4:50 later: still locked
        $this->post('/login', ['login' => $this->victim->username, 'password' => 'password']);
        $this->assertGuest();

        $this->travel(15)->seconds();                                                 // past 5:00 in total
        $this->post('/login', ['login' => $this->victim->username, 'password' => 'password'])->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($this->victim);
    }

    public function test_only_a_successful_login_resets_the_counter(): void
    {
        foreach (range(1, 4) as $i) {
            $this->wrong();
        }
        $this->post('/login', ['login' => $this->victim->username, 'password' => 'password'])->assertRedirect('/employee/dashboard');
        auth()->logout();

        $this->wrong();                                                               // counts as the FIRST failure again
        $this->assertStringContainsString('4 attempts left', $this->message());
    }

    public function test_a_page_reload_keeps_the_lock_and_the_countdown(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        // GET /login again (a reload): the server still knows, so the countdown and the disabled button come back
        $this->get('/login')->assertOk()
            ->assertSee('Sign-in is locked. You can try again in')
            ->assertSee('left: 3', false)                                             // 3xx seconds left
            ->assertSee('value="'.$this->victim->username.'"', false);                // the typed login is put back
    }

    public function test_a_new_browser_session_or_a_private_window_is_locked_too(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        $this->flushSession();                                                        // no cookies, like a private window of the same computer

        $this->get('/login')->assertSee('Sign-in is locked. You can try again in');   // the page already shows the lock
        $this->post('/login', ['login' => $this->victim->email, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_the_login_page_disables_the_button_while_locked(): void
    {
        foreach (range(1, 5) as $i) {
            $this->wrong();
        }

        $this->get('/login')->assertSee('left > 0', false)->assertSee('x-bind:disabled="left > 0"', false);
    }

    public function test_the_api_and_the_website_share_the_same_lock_with_429_and_retry_after(): void
    {
        foreach (range(1, 4) as $i) {
            $this->postJson('/api/login', ['login' => 'user-'.$i, 'password' => 'nope'])->assertUnprocessable();   // different names, all count
        }

        $this->postJson('/api/login', ['login' => $this->victim->username, 'password' => 'nope'])
            ->assertStatus(429)->assertJsonPath('retry_after', 300)->assertHeader('Retry-After', '300');

        // the lock set by the API also blocks the website
        $this->from('/login')->post('/login', ['login' => $this->victim->username, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_the_helper_counts_and_forgets(): void
    {
        $this->assertSame(4, LoginThrottle::fail('1.2.3.4'));
        LoginThrottle::clear('1.2.3.4');
        $this->assertSame(4, LoginThrottle::fail('1.2.3.4'));
        $this->assertSame(0, LoginThrottle::lockedFor('1.2.3.4'));
        $this->assertSame(4, LoginThrottle::fail('5.6.7.8'));   // another device has its own count
    }
}
