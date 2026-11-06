<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Initial accounts. NO password is stored in the code:
 *   - they come from .env (EMS_PASSWORD_*, EMS_MASTER_PASSWORD_HASH), or
 *   - are generated randomly and printed once at the end of the seeding run.
 *
 *   superadmin@ems.test  Super Admin (password = master password, can "log in as" anyone)
 *   hadi@ems.test        HR
 *   ahmad@ems.test       Manager  (manages Abbas)
 *   abbas@ems.test       Employee
 */
class DatabaseSeeder extends Seeder
{
    /** @var array<int, array{0:string,1:string,2:string}> */
    private array $printed = [];

    public function run(): void
    {
        $departments = collect([
            ['Engineering', 'ENG'],
            ['Marketing', 'MKT'],
            ['Finance', 'FIN'],
            ['Human Resources', 'HR'],
        ])->mapWithKeys(fn ($d) => [$d[1] => Department::create(['name' => $d[0], 'code' => $d[1]])]);

        // Super admin: a login only (no employee profile, no tasks of its own).
        $masterHash = config('ems.master_password_hash');
        $this->createUser('Super Admin', 'superadmin@ems.test', UserRole::SuperAdmin, $masterHash ?: null, $masterHash ? 'from EMS_MASTER_PASSWORD_HASH' : null);

        $hadi = $this->person('Hadi', 'hadi@ems.test', UserRole::Hr, 'hadi', $departments['HR'], 'HR Director', 'EMP-0001');
        $ahmad = $this->person('Ahmad', 'ahmad@ems.test', UserRole::Manager, 'ahmad', $departments['ENG'], 'Engineering Manager', 'EMP-0002');
        $abbas = $this->person('Abbas', 'abbas@ems.test', UserRole::Employee, 'abbas', $departments['ENG'], 'Backend Developer', 'EMP-0003', $ahmad);

        $this->sampleTasks($abbas);
        Task::factory()->count(2)->forEmployee($ahmad)->create();

        $this->printCredentials();
    }

    private function person(string $name, string $email, UserRole $role, string $key, Department $dept, string $title, string $number, ?Employee $manager = null): Employee
    {
        $user = $this->createUser($name, $email, $role, config("ems.seed_passwords.{$key}"));

        return Employee::create([
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'manager_id' => $manager?->id,
            'employee_number' => $number,
            'job_title' => $title,
            'hired_at' => now()->subMonths(18),
        ]);
    }

    /** @param  string|null  $secret  plain password, an already-hashed one (superadmin) or null to generate */
    private function createUser(string $name, string $email, UserRole $role, ?string $secret, ?string $note = null): User
    {
        $generated = null;
        $isHash = $note !== null; // only the super admin passes an already-hashed value

        if (blank($secret)) {
            $secret = $generated = Str::password(20);
            $isHash = false;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $isHash ? Str::random(40) : $secret]);
        $user->role = $role;                                                          // role is not mass assignable
        $user->email_verified_at = now();
        $user->save();

        if ($isHash) {
            // Store the configured hash as-is (query builder bypasses the 'hashed' cast and its cost check).
            User::whereKey($user->id)->update(['password' => $secret]);
        }

        if ($generated) {
            $this->printed[] = [$email, $generated, $role->label()];
        }

        return $user;
    }

    /** A realistic spread of statuses for the employee, created through the REAL workflow. */
    private function sampleTasks(Employee $abbas): void
    {
        $workflow = app(TaskWorkflowService::class);
        $employee = $abbas->user;
        $manager = $abbas->manager->user;

        foreach (['Design the reporting API', 'Fix login throttling bug', 'Write onboarding guide', 'Optimise slow task query', 'Prepare sprint demo'] as $i => $title) {
            $task = Task::factory()->forEmployee($abbas)->create(['title' => $title, 'status' => TaskStatus::Pending, 'due_date' => now()->addDays(3 + $i * 4)]);

            match ($i) {
                1 => $workflow->start($task, $employee),
                2 => $workflow->complete($task, $employee),            // waiting for Ahmad's decision
                3 => (function () use ($workflow, $task, $employee, $manager) {
                    $workflow->complete($task, $employee);
                    $workflow->approve($task, $manager);
                })(),
                4 => (function () use ($workflow, $task, $employee, $manager) {
                    $workflow->complete($task, $employee);
                    $workflow->reject($task, $manager, 'Please include the test results before resubmitting.');
                })(),
                default => null,
            };
        }
    }

    private function printCredentials(): void
    {
        if ($this->printed === [] || ! $this->command) {
            return;
        }

        $this->command->warn('Passwords were generated for accounts that had none in .env. Save them now - they are shown only once:');
        $this->command->table(['Email', 'Password', 'Role'], $this->printed);
    }
}
