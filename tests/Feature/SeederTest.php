<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_four_accounts_without_any_known_password(): void
    {
        config(['ems.master_password_hash' => null, 'ems.seed_passwords' => []]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            ['abbas@ems.test' => 'employee', 'ahmad@ems.test' => 'manager', 'hadi@ems.test' => 'hr', 'superadmin@ems.test' => 'super_admin'],
            User::orderBy('email')->get()->mapWithKeys(fn ($u) => [$u->email => $u->role->value])->all()
        );

        foreach (User::all() as $user) {
            $this->assertFalse(Hash::check('password', $user->password), "{$user->email} must not use a guessable password");
        }
    }

    public function test_seeder_uses_the_passwords_from_configuration(): void
    {
        config(['ems.seed_passwords' => ['hadi' => 'Hadi-Pass-12345!', 'ahmad' => 'Ahmad-Pass-12345!', 'abbas' => 'Abbas-Pass-12345!']]);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('Abbas-Pass-12345!', User::where('email', 'abbas@ems.test')->value('password')));
        $this->assertGreaterThan(0, Task::count());
    }
}
