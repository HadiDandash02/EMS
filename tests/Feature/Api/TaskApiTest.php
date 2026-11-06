<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsOrganisation;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use BuildsOrganisation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrganisation();
    }

    // ---- authentication ------------------------------------------------------------

    public function test_unauthenticated_requests_are_rejected_with_json_401(): void
    {
        // No Accept header on purpose: API errors must still be JSON.
        $this->get('/api/tasks')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->post('/api/tasks', $this->validTask())->assertUnauthorized();
        $this->post('/api/tasks/1/approve')->assertUnauthorized();
    }

    public function test_user_can_log_in_and_use_the_token(): void
    {
        $token = $this->postJson('/api/login', ['email' => $this->employee->email, 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'role']])
            ->json('token');

        $this->withToken($token)->getJson('/api/tasks')->assertOk();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $this->postJson('/api/login', ['email' => $this->employee->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    // ---- create / validation -------------------------------------------------------

    public function test_employee_can_create_a_task(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/tasks', $this->validTask())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Prepare quarterly report')
            ->assertJsonPath('data.status.value', 'PENDING')
            ->assertJsonPath('data.employee.id', $this->employeeEmp->id);
    }

    public function test_api_validation_errors_return_422(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/tasks', ['title' => 'x', 'priority' => 'nope', 'due_date' => 'yesterday'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority', 'due_date']);
    }

    public function test_client_cannot_set_status_owner_or_review_fields(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson('/api/tasks', $this->validTask([
            'status' => 'APPROVED',
            'employee_id' => $this->teammateEmp->id,
            'reviewed_by' => $this->manager->id,
        ]))->assertCreated();

        $response->assertJsonPath('data.status.value', 'PENDING')
            ->assertJsonPath('data.employee.id', $this->employeeEmp->id);
        $this->assertNull(\App\Models\Task::first()->reviewed_by);
    }

    // ---- reading / IDOR ------------------------------------------------------------

    public function test_employee_cannot_view_another_employees_task(): void
    {
        $others = $this->taskFor($this->teammateEmp);
        Sanctum::actingAs($this->employee);

        $this->getJson("/api/tasks/{$others->id}")->assertForbidden();
    }

    public function test_listing_only_returns_visible_tasks_and_is_paginated(): void
    {
        \App\Models\Task::factory()->count(20)->forEmployee($this->employeeEmp)->create();
        $this->taskFor($this->teammateEmp);
        Sanctum::actingAs($this->employee);

        $this->getJson('/api/tasks?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
    }

    public function test_listing_validates_filters(): void
    {
        Sanctum::actingAs($this->employee);

        $this->getJson('/api/tasks?status=BOGUS&per_page=1000')->assertUnprocessable()->assertJsonValidationErrors(['status', 'per_page']);
    }

    public function test_hr_sees_everything_and_can_filter(): void
    {
        $this->taskFor($this->employeeEmp, 'completed');
        $this->taskFor($this->outsiderEmp);
        Sanctum::actingAs($this->hr);

        $this->getJson('/api/tasks')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/tasks?status=COMPLETED')->assertJsonPath('meta.total', 1);
    }

    public function test_api_responses_do_not_expose_sensitive_fields(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        Sanctum::actingAs($this->hr);

        $body = $this->getJson("/api/tasks/{$task->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString($this->employee->email, $body);
    }

    // ---- update / workflow ---------------------------------------------------------

    public function test_owner_can_update_a_pending_task_but_not_someone_elses(): void
    {
        $mine = $this->taskFor($this->employeeEmp);
        $theirs = $this->taskFor($this->teammateEmp);
        Sanctum::actingAs($this->employee);

        $this->patchJson("/api/tasks/{$mine->id}", ['title' => 'Updated title'])->assertOk()->assertJsonPath('data.title', 'Updated title');
        $this->patchJson("/api/tasks/{$theirs->id}", ['title' => 'Hacked'])->assertForbidden();
    }

    public function test_non_pending_task_cannot_be_updated(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'inProgress');
        Sanctum::actingAs($this->employee);

        $this->putJson("/api/tasks/{$task->id}", ['title' => 'Too late'])->assertUnprocessable();
    }

    public function test_owner_can_complete_a_task(): void
    {
        $task = $this->taskFor($this->employeeEmp);
        Sanctum::actingAs($this->employee);

        $this->postJson("/api/tasks/{$task->id}/complete")->assertOk()->assertJsonPath('data.status.value', 'COMPLETED');
    }

    public function test_manager_can_approve_then_second_approval_is_rejected(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/tasks/{$task->id}/approve")->assertOk()->assertJsonPath('data.status.value', 'APPROVED');
        $this->postJson("/api/tasks/{$task->id}/approve")->assertUnprocessable();
    }

    public function test_employee_cannot_approve_via_api(): void
    {
        $task = $this->taskFor($this->teammateEmp, 'completed');
        Sanctum::actingAs($this->employee);

        $this->postJson("/api/tasks/{$task->id}/approve")->assertForbidden();
        $this->assertSame('COMPLETED', $task->fresh()->status->value);
    }

    public function test_manager_cannot_approve_outside_their_team_via_api(): void
    {
        $task = $this->taskFor($this->outsiderEmp, 'completed');
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/tasks/{$task->id}/approve")->assertForbidden();
    }

    public function test_reject_requires_a_reason_and_records_it(): void
    {
        $task = $this->taskFor($this->employeeEmp, 'completed');
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/tasks/{$task->id}/reject")->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson("/api/tasks/{$task->id}/reject", ['reason' => 'abc'])->assertUnprocessable();

        $this->postJson("/api/tasks/{$task->id}/reject", ['reason' => 'Not enough detail provided.'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'REJECTED')
            ->assertJsonPath('data.review.rejection_reason', 'Not enough detail provided.');
    }

    public function test_unknown_task_returns_404_json(): void
    {
        Sanctum::actingAs($this->employee);

        $this->getJson('/api/tasks/99999')->assertNotFound();
    }
}
