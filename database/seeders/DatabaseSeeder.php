<?php

namespace Database\Seeders;

use App\Enums\TaskAction;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskWorkflowService;
use Illuminate\Database\Seeder;

/**
 * Demo data. Every account uses the password "password".
 *
 *   hr@ems.test        HR
 *   sarah@ems.test     Manager (Engineering)  - manages Ali, Mona, Omar
 *   david@ems.test     Manager (Marketing)    - manages Lina, Karim
 *   ali@ems.test ...   Employees
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect([
            ['Engineering', 'ENG'],
            ['Marketing', 'MKT'],
            ['Finance', 'FIN'],
            ['Human Resources', 'HR'],
        ])->mapWithKeys(fn ($d) => [$d[1] => Department::create(['name' => $d[0], 'code' => $d[1]])]);

        $hr = $this->person('Hannah Brooks', 'hr@ems.test', UserRole::Hr, $departments['HR'], 'HR Director', 'EMP-0001');

        $sarah = $this->person('Sarah Collins', 'sarah@ems.test', UserRole::Manager, $departments['ENG'], 'Engineering Manager', 'EMP-0002');
        $david = $this->person('David Miller', 'david@ems.test', UserRole::Manager, $departments['MKT'], 'Marketing Manager', 'EMP-0003');

        $team = [
            [$this->person('Ali Hassan', 'ali@ems.test', UserRole::Employee, $departments['ENG'], 'Backend Developer', 'EMP-0004', $sarah)],
            [$this->person('Mona Saleh', 'mona@ems.test', UserRole::Employee, $departments['ENG'], 'QA Engineer', 'EMP-0005', $sarah)],
            [$this->person('Omar Farouk', 'omar@ems.test', UserRole::Employee, $departments['ENG'], 'Frontend Developer', 'EMP-0006', $sarah)],
            [$this->person('Lina Haddad', 'lina@ems.test', UserRole::Employee, $departments['MKT'], 'Content Specialist', 'EMP-0007', $david)],
            [$this->person('Karim Nasser', 'karim@ems.test', UserRole::Employee, $departments['MKT'], 'SEO Analyst', 'EMP-0008', $david)],
        ];

        // Employee outside both teams (reports to nobody) - useful to demo "unauthorised" cases.
        $this->person('Nora Quinn', 'nora@ems.test', UserRole::Employee, $departments['FIN'], 'Accountant', 'EMP-0009');

        $workflow = app(TaskWorkflowService::class);

        $employees = Employee::with('user')->get();

        foreach ($employees as $employee) {
            foreach (range(1, 4) as $i) {
                $task = Task::factory()->forEmployee($employee)->create();
                $this->advance($workflow, $task, $employee, $i);
            }
        }

        // Manager's own tasks (reviewed by their own manager level - none here, so keep them simple).
        Task::factory()->count(2)->forEmployee($sarah)->create();
        Task::factory()->count(2)->forEmployee($david)->create();
        Task::factory()->count(2)->forEmployee($hr)->create();
    }

    private function person(string $name, string $email, UserRole $role, Department $dept, string $title, string $number, ?Employee $manager = null): Employee
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->role = $role;
        $user->email_verified_at = now();
        $user->save();

        return Employee::create([
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'manager_id' => $manager?->id,
            'employee_number' => $number,
            'job_title' => $title,
            'hired_at' => now()->subMonths(rand(3, 48)),
        ]);
    }

    /**
     * Push a task through the REAL workflow service so seeded history rows are authentic.
     */
    private function advance(TaskWorkflowService $workflow, Task $task, Employee $owner, int $scenario): void
    {
        $actor = $owner->user;
        $manager = $owner->manager?->user;

        match ($scenario) {
            1 => null, // stays PENDING
            2 => $workflow->start($task, $actor),
            3 => $workflow->complete($task, $actor), // waiting for approval
            4 => $this->finish($workflow, $task, $actor, $manager),
        };
    }

    private function finish(TaskWorkflowService $workflow, Task $task, User $actor, ?User $manager): void
    {
        $workflow->complete($task, $actor);

        if (! $manager) {
            return;
        }

        if (rand(0, 1)) {
            $workflow->approve($task, $manager);
        } else {
            $workflow->reject($task, $manager, 'Please add more detail and attach the supporting report.');
        }
    }
}
