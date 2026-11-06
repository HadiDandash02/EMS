<?php

namespace Tests\Concerns;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;

/**
 * A small, readable company used by most tests:
 *
 *   hr            HR user
 *   manager       manages  -> employee, teammate
 *   otherManager  manages  -> outsider        (a DIFFERENT team)
 */
trait BuildsOrganisation
{
    protected Department $dept;

    protected Employee $hrEmp;

    protected Employee $managerEmp;

    protected Employee $employeeEmp;

    protected Employee $teammateEmp;

    protected Employee $otherManagerEmp;

    protected Employee $outsiderEmp;

    protected User $hr;

    protected User $manager;

    protected User $employee;

    protected User $teammate;

    protected User $otherManager;

    protected User $outsider;

    protected function buildOrganisation(): void
    {
        $this->dept = Department::factory()->create(['name' => 'Engineering', 'code' => 'ENG']);

        $this->hrEmp = Employee::factory()->hr()->create(['department_id' => $this->dept->id]);
        $this->managerEmp = Employee::factory()->manager()->create(['department_id' => $this->dept->id]);
        $this->otherManagerEmp = Employee::factory()->manager()->create();

        $this->employeeEmp = Employee::factory()->reportingTo($this->managerEmp)->create();
        $this->teammateEmp = Employee::factory()->reportingTo($this->managerEmp)->create();
        $this->outsiderEmp = Employee::factory()->reportingTo($this->otherManagerEmp)->create();

        $this->hr = $this->hrEmp->user;
        $this->manager = $this->managerEmp->user;
        $this->otherManager = $this->otherManagerEmp->user;
        $this->employee = $this->employeeEmp->user;
        $this->teammate = $this->teammateEmp->user;
        $this->outsider = $this->outsiderEmp->user;
    }

    /** URL of a signed-in page for the given user's role: p($manager, '/tasks') => /manager/tasks */
    protected function p(User $user, string $path): string
    {
        return '/'.$user->url_prefix.$path;
    }

    protected function taskFor(Employee $employee, string $state = 'pending'): Task
    {
        return Task::factory()->forEmployee($employee)->{$state}()->create();
    }

    /** Valid payload for creating a task (web form or API). */
    protected function validTask(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Prepare quarterly report',
            'description' => 'Collect numbers from every team.',
            'priority' => 'high',
            'due_date' => now()->addWeek()->toDateString(),
        ], $overrides);
    }
}
