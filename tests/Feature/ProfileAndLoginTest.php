<?php

namespace Tests\Feature;

use App\Livewire\Profile\EditProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class ProfileAndLoginTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
        $this->employee->forceFill(['username' => 'abbas'])->save();
    }

    // ---- sign in by username or email ---------------------------------------------

    public function test_user_can_sign_in_with_username(): void
    {
        $this->post('/login', ['login' => 'abbas', 'password' => 'password'])->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($this->employee);
    }

    public function test_username_is_case_insensitive_and_email_still_works(): void
    {
        $this->post('/login', ['login' => '  ABBAS ', 'password' => 'password'])->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($this->employee);

        auth()->logout();
        $this->post('/login', ['login' => strtoupper($this->employee->email), 'password' => 'password'])->assertRedirect('/employee/dashboard');
    }

    public function test_wrong_credentials_show_a_generic_error(): void
    {
        $this->post('/login', ['login' => 'abbas', 'password' => 'bad'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => 'nobody', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_page_has_the_username_field_and_a_password_eye_toggle(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Username or email')
            ->assertSee('Show password');
    }

    public function test_api_login_accepts_a_username(): void
    {
        $this->postJson('/api/login', ['login' => 'abbas', 'password' => 'password'])->assertOk()->assertJsonStructure(['token']);
    }

    // ---- profile -----------------------------------------------------------------

    public function test_profile_page_is_for_signed_in_users_only(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->actingAs($this->employee)->get($this->p($this->employee, '/profile'))->assertOk()->assertSee('Personal information');
    }

    public function test_user_can_update_their_profile(): void
    {
        Livewire::actingAs($this->employee)->test(EditProfile::class)
            ->set('name', 'Abbas Updated')->set('username', 'Abbas.New')->set('phone', '+961 70 123 456')
            ->call('saveProfile')->assertHasNoErrors();

        $this->employee->refresh();
        $this->assertSame('Abbas Updated', $this->employee->name);
        $this->assertSame('abbas.new', $this->employee->username); // stored lowercase
    }

    public function test_username_must_be_unique_and_well_formed(): void
    {
        Livewire::actingAs($this->employee)->test(EditProfile::class)
            ->set('username', $this->manager->username)->call('saveProfile')->assertHasErrors(['username' => 'unique'])
            ->set('username', 'no spaces!')->call('saveProfile')->assertHasErrors(['username' => 'regex']);
    }

    public function test_user_cannot_change_their_role_through_the_profile_form(): void
    {
        Livewire::actingAs($this->employee)->test(EditProfile::class)
            ->set('name', 'Still Employee')->call('saveProfile');

        $this->assertSame('employee', $this->employee->fresh()->role->value);
    }

    public function test_password_change_requires_the_current_password_and_a_strong_new_one(): void
    {
        $page = Livewire::actingAs($this->employee)->test(EditProfile::class);

        $page->set('current_password', 'wrong')->set('password', 'NewPass123')->set('password_confirmation', 'NewPass123')
            ->call('updatePassword')->assertHasErrors(['current_password']);

        $page->set('current_password', 'password')->set('password', 'short')->set('password_confirmation', 'short')
            ->call('updatePassword')->assertHasErrors(['password']);

        $page->set('current_password', 'password')->set('password', 'NewPass123')->set('password_confirmation', 'Mismatch123')
            ->call('updatePassword')->assertHasErrors(['password' => 'confirmed']);

        $page->set('current_password', 'password')->set('password', 'NewPass123')->set('password_confirmation', 'NewPass123')
            ->call('updatePassword')->assertHasNoErrors();

        $this->assertTrue(Hash::check('NewPass123', $this->employee->fresh()->password));
    }

    public function test_user_can_upload_and_remove_a_profile_picture(): void
    {
        Storage::fake('local');

        Livewire::actingAs($this->employee)->test(EditProfile::class)
            ->set('photo', UploadedFile::fake()->image('me.png', 300, 300))
            ->call('savePhoto')->assertHasNoErrors();

        $path = $this->employee->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->manager)->get(route('avatar', $this->employee))->assertOk();

        Livewire::actingAs($this->employee)->test(EditProfile::class)->call('removePhoto');
        $this->assertNull($this->employee->fresh()->avatar_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_profile_picture_rejects_non_images_and_big_files(): void
    {
        Storage::fake('local');

        Livewire::actingAs($this->employee)->test(EditProfile::class)
            ->set('photo', UploadedFile::fake()->create('evil.pdf', 50, 'application/pdf'))
            ->call('savePhoto')->assertHasErrors('photo');

        Livewire::actingAs($this->employee)->test(EditProfile::class)
            ->set('photo', UploadedFile::fake()->image('huge.png')->size(5000))
            ->call('savePhoto')->assertHasErrors('photo');
    }

    public function test_avatars_are_only_served_to_signed_in_users(): void
    {
        $this->get(route('avatar', $this->employee))->assertRedirect('/login');
    }
}
