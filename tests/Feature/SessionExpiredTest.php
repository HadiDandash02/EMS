<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

/** A stale form token must never show the raw "419 Page Expired" screen. */
class SessionExpiredTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();

        // CSRF checking is switched off in tests, so simulate the failure the framework raises.
        Route::middleware('web')->post('/_expired', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
    }

    public function test_a_visitor_with_a_stale_token_is_sent_back_to_the_login_page_with_a_message(): void
    {
        $this->post('/_expired', ['login' => 'abbas', 'password' => 'secret-should-not-be-kept'])
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Your session expired. Please try again.')
            ->assertSessionHasInput('login', 'abbas')
            ->assertSessionMissing('_old_input.password');

        $this->get('/login')->assertOk()->assertSee('Your session expired. Please try again.');
    }

    public function test_a_signed_in_user_goes_back_to_the_previous_page_with_a_message(): void
    {
        $this->actingAs($this->employee)->from('/employee/tasks')->post('/_expired')
            ->assertRedirect('/employee/tasks')
            ->assertSessionHas('error');
    }

    public function test_the_message_is_translated(): void
    {
        $this->get('/language/ar');

        $this->post('/_expired')->assertSessionHas('error', 'انتهت جلستك. يرجى المحاولة مرة أخرى.');
    }

    public function test_api_clients_still_get_json_not_a_redirect(): void
    {
        $this->postJson('/_expired')->assertStatus(419);
    }
}
