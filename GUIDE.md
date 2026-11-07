# TaskFlow – Guide to the assessment brief

This guide goes through **every point of the Phase 2 brief, in order**, and says:

* **Where** it is in the project (file paths),
* **How** it works, in plain words,
* **Example** you can run or show to the instructor.

Use it as your script when you explain the project. Paths are relative to the project folder. See `README.md` for setup and `docs/DESIGN-SYSTEM.md` for the look & feel.

> Accounts (username → role): `hadi` → HR, `ahmad` → Manager (manages Abbas), `abbas` → Employee, `superadmin` → Super Admin.
> Passwords are in your `.env` file (`EMS_PASSWORD_*`; the super admin uses your master password) – they are not shown on the login page.

---

## 1. Project and technologies

| Required | Used | Where to see it |
|---|---|---|
| PHP, Laravel | PHP 8.2, Laravel 12 | `composer.json` |
| MySQL | database `ems_tasks` (tests use in-memory SQLite) | `.env`, `phpunit.xml` |
| Livewire | Livewire 3 full-page components | `app/Livewire/` |
| Tailwind CSS | Tailwind 4 | `resources/css/app.css` |
| Git | feature branches + pull requests into `master` | `git log --oneline --graph` |

---

## 2. Business requirements (Employee / Manager / HR)

All "who can do what" rules live in **one file**: `app/Policies/TaskPolicy.php`.
The actions themselves live in **one service**: `app/Services/TaskWorkflowService.php`.

| Rule from the brief | Implemented in | Test that proves it |
|---|---|---|
| Employee sees only own tasks | `Task::visibleTo()` scope in `app/Models/Task.php` + `TaskPolicy::view` | `TaskAuthorizationTest::test_employee_task_list_only_contains_their_own_tasks` |
| Employee cannot open another employee's task (ID in URL) | `TaskPolicy::view` → HTTP 403 | `test_employee_cannot_access_another_employees_task_by_changing_the_id` |
| Employee creates a task | `app/Livewire/Tasks/TaskForm.php` → `TaskWorkflowService::create` | `TaskCreationTest::test_employee_can_create_a_task` |
| Employee edits only own **pending** task | `TaskPolicy::update` + status check in the service | `test_employee_cannot_edit_a_task_that_is_not_pending` |
| Employee completes / cancels (pending only) | `ConfirmAction` page → `complete()` / `cancel()` | `TaskCrudTest::test_complete_and_cancel_have_confirmation_pages` |
| Employee sees task history | history timeline in `resources/views/livewire/tasks/task-show.blade.php` (`task_histories` table) | `ApprovalWorkflowTest` |
| Employee cannot approve (nor own task) | `TaskPolicy::approve` | `test_employee_cannot_approve_a_task`, `test_employee_cannot_approve_their_own_task` |
| Manager sees only his team | `Task::forTeamOf($manager)` scope; route `/manager/team/tasks` | `test_manager_can_view_team_members_task_but_not_outsiders` |
| Manager approves / rejects completed tasks | `approve()` / `reject()` in the service | `test_manager_can_approve_an_authorized_completed_task` |
| Rejection needs a reason | `RejectTask` page validation **and** the service (`min 5 chars`) | `test_rejection_requires_a_reason` |
| Manager cannot approve own / outside team / already approved / cancelled | `TaskPolicy::approve` + `TaskStatus::transitions()` | `test_manager_cannot_approve_a_task_outside_their_team`, `test_a_task_cannot_be_approved_twice`, `test_a_cancelled_task_cannot_be_approved` |
| Manager approval history | `app/Livewire/Approvals/ApprovalHistory.php` (`/manager/approvals`) | `ApprovalWorkflowTest`, `ConsistentFiltersTest` |
| HR sees all employees | `app/Livewire/Employees/EmployeeList.php` (`/hr/employees`) | `SearchAndPagesTest` |
| HR sees / searches / filters all tasks | `TaskList` with scope `all` (`/hr/all-tasks`) | `TaskSearchFilterTest` |
| HR cannot approve (read-only) | `TaskPolicy::approve` | `test_hr_cannot_approve_tasks` |

**Example (show this in the recording):** log in as `abbas`, open `/employee/tasks/6` (a task that belongs to someone else) → **403 Forbidden**.

---

## 3. Task status workflow

* **Where:** `app/Enums/TaskStatus.php` – the method `transitions()` is the *single* state machine.
* **Statuses:** `PENDING`, `IN_PROGRESS`, `COMPLETED`, `APPROVED`, `REJECTED`, `CANCELLED`.
* **Allowed moves:**

```
PENDING      → IN_PROGRESS, COMPLETED, CANCELLED
IN_PROGRESS  → COMPLETED
COMPLETED    → APPROVED, REJECTED
REJECTED     → IN_PROGRESS            (rework, then complete again)
APPROVED     → (final)
CANCELLED    → (final)
```

* **Why:** an approved task must never go back to pending; a rejected task may be reworked. All decisions are documented in `README.md` §4.
* **Example:**

```php
TaskStatus::Approved->canTransitionTo(TaskStatus::Pending);   // false
TaskStatus::Completed->canTransitionTo(TaskStatus::Approved); // true
```

* **Unit test:** `tests/Unit/TaskStatusTest.php` checks every allowed and every forbidden move.

---

## 4. Database

* **Migrations** (`database/migrations/`): `departments`, `employees`, `tasks`, `task_histories`, `users` (+ profile fields), `personal_access_tokens`.
* **ERD:** `docs/ERD.md` (Mermaid diagram – renders on GitHub).
* **Relationships** (models in `app/Models/`):

```
Department 1─* Employee      Employee 1─* Task        Task 1─* TaskHistory
User       1─1 Employee      Employee *─1 Employee (manager_id = who manages him)
Task *─1 User (reviewed_by)  TaskHistory *─1 User (actor_id)
```

* **Indexes** (in `create_tasks_table.php`): `(employee_id, status)` for "my tasks" and the manager's queue, `status`, `due_date`, `created_at`, `title` – each one exists because a screen filters or sorts by it.
* **Foreign keys:** `constrained()->cascadeOnDelete()` for owned data (a task's history dies with the task), `nullOnDelete()` for "who did it" (`reviewed_by`, `actor_id`) so history survives deleting a user.
* **Create + fill:** `php artisan migrate:fresh --seed`.

---

## 5. Laravel requirements

| Topic | Where |
|---|---|
| Routes (web) | `routes/web.php` – role-prefixed groups `/employee/…`, `/manager/…`, `/hr/…`, `/admin/…` |
| Routes (API) | `routes/api.php` |
| Controllers stay thin | `app/Http/Controllers/Api/TaskController.php` – each method is 2–4 lines, it only calls the service |
| Models + relationships | `app/Models/*.php` |
| Factories / seeders | `database/factories/*`, `database/seeders/DatabaseSeeder.php` (creates users and sample tasks **through the real workflow**) |
| Validation | Form Requests `app/Http/Requests/Api/*`, Livewire `rules()`; shared rules in `app/Support/TaskRules.php`, passwords in `app/Support/PasswordPolicy.php` |
| Authorization | Policy `TaskPolicy`, Gate `manage-users` (`AppServiceProvider`), middleware `EnsureUserHasRole` |

---

## 6. Search, filtering, pagination

* **Filter logic (one place):** `Task::scopeFilter()` in `app/Models/Task.php`.
* **UI:** `resources/views/components/filter-bar.blade.php` (the panel) + `data-table.blade.php` (the results).
* **Behaviour (same on all 5 lists):** the **filters come first**, the table appears only after **Search** (or after you pick a value in a dropdown). The Results card can be collapsed.
* **Filters:** Task, Status, Priority, Employee, Department, date range (HR can filter by employee, department, status, date and task title ✔). Every dropdown is *searchable* and scrolls.
* **Pagination:** 10 / 25 / 50 / 100 rows per page (`HasFilterBar` trait, `rowsPerPage()`).
* **No N+1:** lists use eager loading – `->with(['employee.user', 'employee.department', 'reviewer'])` in `TaskList`, `withCount('tasks')` in `EmployeeList`.
* **SQL injection safe:** `app/Support/Sql.php::whereContains()` escapes `% _ !` and uses bindings.
* **Tests:** `TaskSearchFilterTest`, `ConsistentFiltersTest`, `SearchAndPagesTest`.

**Example:** HR → *All tasks* → pick **Employee = Abbas** → the table opens with only Abbas's tasks.

---

## 7. Business logic and the transaction decision

This is the part to explain in the recording. File: `app/Services/TaskWorkflowService.php`.

```php
public function approve(Task $task, User $actor, ?string $note = null): Task
{
    return $this->locked($task, $actor, 'approve', function (Task $t) use ($actor, $note) {
        $t->reviewed_by = $actor->id;
        $t->reviewed_at = now();
        $this->transition($t, $actor, TaskStatus::Approved, TaskAction::Approved, $note);
    });
}

private function locked(Task $task, User $actor, string $ability, callable $change): Task
{
    return DB::transaction(function () use ($task, $actor, $ability, $change) {
        $fresh = Task::query()->whereKey($task->getKey())->lockForUpdate()->firstOrFail();  // 1. lock
        Gate::forUser($actor)->authorize($ability, $fresh);                                  // 2. who may act
        $change($fresh);                                                                     // 3. state machine + save + history
        return $fresh->refresh();
    });
}
```

**Why a transaction?** Approving changes two tables (the task row and a new `task_histories` row). Either both are saved or neither – no approved task without an audit record.
**Why `lockForUpdate`?** If two managers press *Approve* at the same moment, the second waits, then sees the task is already approved and is refused → a task cannot be approved twice.
**Why re-check authorization in the service?** Even a forged request or another developer's code cannot skip the rules.
**Test:** `ApprovalWorkflowTest::test_status_and_history_change_together_or_not_at_all`.

---

## 8. REST API

* **Code:** `routes/api.php`, `app/Http/Controllers/Api/TaskController.php` + `AuthController.php`, resources `app/Http/Resources/TaskResource.php` and `TaskHistoryResource.php`, Form Requests in `app/Http/Requests/Api/`.
* **Auth:** Laravel Sanctum bearer tokens.

| Method + URL | Purpose | Success | Errors |
|---|---|---|---|
| `POST /api/login` | get a token | 200 | 422 wrong credentials |
| `GET /api/tasks` | list (paginated, filters `status`, `search`, `per_page`…) | 200 | 401 |
| `GET /api/tasks/{id}` | one task | 200 | 401, 403, 404 |
| `POST /api/tasks` | create | 201 | 422 |
| `PUT/PATCH /api/tasks/{id}` | update | 200 | 403, 422 |
| `DELETE /api/tasks/{id}` | delete | 204 | 403, 422 |
| `POST /api/tasks/{id}/start` · `/complete` · `/cancel` | employee actions | 200 | 403, 422 |
| `POST /api/tasks/{id}/approve` | manager approves | 200 | 403, 422 |
| `POST /api/tasks/{id}/reject` (`reason` required) | manager rejects | 200 | 403, 422 |

**Examples (curl):**

```bash
# 1. log in
curl -X POST http://127.0.0.1:8000/api/login -H "Accept: application/json" \
     -d "login=abbas" -d "password=YOUR_PASSWORD"
#  → {"token":"1|abc…","token_type":"Bearer","user":{…}}

# 2. list tasks (paginated)
curl http://127.0.0.1:8000/api/tasks?per_page=5 -H "Accept: application/json" -H "Authorization: Bearer 1|abc…"

# 3. validation error → 422
curl -X POST http://127.0.0.1:8000/api/tasks -H "Accept: application/json" -H "Authorization: Bearer 1|abc…" \
     -d "title=ab" -d "priority=urgent"

# 4. no token → 401
curl http://127.0.0.1:8000/api/tasks -H "Accept: application/json"
```

**Tests:** `tests/Feature/Api/TaskApiTest.php` (validation 422, unauthenticated 401, employee cannot approve 403, double approval 422, reject needs a reason).

---

## 9. Livewire

Components are in `app/Livewire/`, their views in `resources/views/livewire/`.

| Livewire feature | Example in the project |
|---|---|
| Component + properties | `TaskList` (`$status`, `$taskId`, `$perPage`, `$searched`) |
| Actions | `startTask()`, `confirm()`, `reject()`, `applyFilters()`, `clearFilters()` |
| Validation | `TaskForm::rules()`, `RejectTask::rules()` – errors appear under the field |
| Loading states | buttons use `wire:loading` / `wire:target` ("Please wait…") |
| Search / filter | `HasFilterBar` trait + `x-searchable-select wire:model.live` |
| Pagination | `WithPagination` + `<x-table-footer>` |
| Events | `#[On('task-updated')]` refreshes the list/detail after an action |
| Locked properties | `#[Locked] $scope` – the browser cannot switch "mine" into "all" (`test_list_scope_cannot_be_tampered_with_from_the_browser`) |

---

## 10. Tailwind CSS interface

| Required | Where |
|---|---|
| Forms | `task-form.blade.php`, `user-form.blade.php` |
| Tables | `components/data-table.blade.php` (sorting, columns, CSV export) |
| Buttons | `<x-button>`; styles in `resources/css/app.css` (`.btn-*`) |
| Status indicators | `components/status-badge.blade.php`, `priority-badge`, `role-badge` |
| Validation messages | red text under each field (`@error`) |
| Empty states | `components/empty-state.blade.php` |
| Loading states | `wire:loading` on buttons |
| Confirmation states | **pages**, not popups: `tasks/confirm-action.blade.php`, `reject-task`, `admin/delete-user` |
| Responsive | Tailwind breakpoints; the task table becomes cards on small screens |

Extras: dark mode, Arabic/English with right-to-left (`lang/ar.json`), full-width pages with breadcrumbs.
**Change the style:** `docs/DESIGN-SYSTEM.md` says which file to edit for buttons, cards, colours, page width.

---

## 11. Security

| Threat | Protection | Where |
|---|---|---|
| Unauthorized access | policy + role middleware, always **server side** | `TaskPolicy`, `EnsureUserHasRole` |
| Changing the ID in URL / API | `authorize('view', $task)` → 403 | `TaskShow::mount`, `TaskController::show` |
| Mass assignment | `$fillable` lists; `role` and `status` are **not** fillable | models; `test_role_cannot_be_mass_assigned` |
| Invalid input | server-side validation everywhere | Form Requests, Livewire `rules()` |
| SQL injection | Eloquent bindings + escaped LIKE | `app/Support/Sql.php`; `test_sql_injection_attempt_is_harmless` |
| CSRF | Laravel middleware; an expired token redirects nicely instead of "419" | `bootstrap/app.php`; `SessionExpiredTest` |
| Sensitive data | passwords hashed, `password`/`remember_token` hidden, master-password hash only in `.env` | `User` model, `config/ems.php` |
| Privilege escalation | admin edit computes the role rule from the **stored** user | `UserForm`; `test_editing_cannot_promote_a_user_to_super_admin` |

---

## 12. Automated tests

Run: `php artisan test` → **242 tests, all passing** (SQLite in memory, never touches your MySQL data).

| Required scenario | Test |
|---|---|
| Employee can create a task | `TaskCreationTest::test_employee_can_create_a_task` |
| Invalid task data rejected | `test_invalid_task_data_is_rejected` |
| Employee cannot access another's task | `TaskAuthorizationTest::test_employee_cannot_access_another_employees_task_by_changing_the_id` |
| Employee cannot approve | `ApprovalWorkflowTest::test_employee_cannot_approve_a_task` |
| Manager can approve an authorized task | `test_manager_can_approve_an_authorized_completed_task` |
| Manager cannot approve unauthorized | `test_manager_cannot_approve_a_task_outside_their_team` |
| Cannot be approved twice | `test_a_task_cannot_be_approved_twice` |
| Rejection requires a reason | `test_rejection_requires_a_reason` |
| API validation | `Api/TaskApiTest::test_api_validation_errors_return_422` |
| Unauthorized API rejected | `test_unauthenticated_requests_are_rejected_with_json_401` |
| Unit test | `tests/Unit/TaskStatusTest.php` |

**Example:** `php artisan test --filter=ApprovalWorkflowTest`

---

## 13. Git

* Feature branches: `feature/task-management`, `feature/task-api`, `feature/authorization-workflow`, `feature/automated-tests`, `feature/design-system`, `feature/live-filters`, …
* Meaningful commits, merged into `master` through pull requests – `master` is never edited directly.
* **Show it:** `git log --oneline --graph --decorate -20`.

---

## 15. README

`README.md` has every required section: setup (§1), database (§1), architecture (§2), relationships (§3), status workflow (§4), authorization (§5), validation (§6), API (§7), testing (§8), AI usage (§9), known limitations (§10), Git workflow (§11), recording guide (§12).

---

## 16–17. Submission and screen recording

Checklist: repository ✔ · pull requests ✔ · `docs/ERD.md` ✔ · `README.md` ✔ · tests ✔ · API docs (README §7 + this guide) ✔ · migrations/seeders/factories ✔ · **screen recording** (you).

Order for a 10–15 minute recording (full script in `README.md` §12):
1. Login (username + eye icon, no demo passwords) · 2. Employee creates a task · 3. Employee's task list · 4. Manager opens a team task · 5. Manager approves / rejects with a reason · 6. Unauthorized access → 403 · 7. HR search & filters · 8. API calls (login, list, 422, 401, 403) · 9. `php artisan test` · 10. Explain `TaskWorkflowService::locked()` (section 7 above).

---

## 18. Final change requests – where to change things

| If the instructor asks… | Change here |
|---|---|
| Change an authorization rule | `app/Policies/TaskPolicy.php` (+ the matching test) |
| Change a status transition | `TaskStatus::transitions()` in `app/Enums/TaskStatus.php` (+ `tests/Unit/TaskStatusTest.php`) |
| Add a new filter | 1) public property + `#[Url]` in `app/Livewire/Tasks/TaskList.php` 2) add it to `filterProperties()` and `query()` 3) a `<x-searchable-select>` in `task-list.blade.php` 4) key in `Task::scopeFilter()` |
| Add a field to the API | `app/Http/Resources/TaskResource.php` (+ migration and `$fillable` if it is stored) |
| Fix an authorization issue | `TaskPolicy`, then add a test in `TaskAuthorizationTest` |
| Optimize a slow query | eager loading `->with(...)` in the list component / controller; add an index in a new migration |
| Change a business rule | `TaskWorkflowService` (rule) and `TaskRules` (validation) |
| Change a button / colour / page layout | `docs/DESIGN-SYSTEM.md` |

**Example – "add a `due_before` filter":** add `public string $dueBefore = ''` to `TaskList`, put it in `filterProperties()`, pass `'due_before' => $this->dueBefore ?: null` to `->filter([...])`, and in `Task::scopeFilter()` add
`->when($filters['due_before'] ?? null, fn ($q, $v) => $q->whereDate('tasks.due_date', '<=', $v))`. Then add one test in `TaskSearchFilterTest`.

---

## 19. Debugging – how to answer (cause · why · fix · verify · prevent)

| Typical issue | Where to look | Prevent with |
|---|---|---|
| Authorization failure (someone sees a task they should not) | `Task::visibleTo()`, `TaskPolicy::view` | a test that requests the other user's ID and expects 403 |
| N+1 queries | the list query – is there a `->with(...)`? | `Model::preventLazyLoading()` in development |
| Validation problem | the Form Request / Livewire `rules()` | one test per rule |
| Wrong status transition | `TaskStatus::transitions()` | `TaskStatusTest` data sets |
| API response problem | `TaskResource`, status codes in `TaskController` | `TaskApiTest` |
| Transaction issue | `TaskWorkflowService::locked()` | the "changes together or not at all" test |

A real bug found and fixed in this project (good example to tell): the **Search button did nothing in the browser** because the Livewire property `search` had the same name as the method `search()`. The PHP tests passed (they call the method directly), but in the browser JavaScript found the property first. Fix: rename the method to `applyFilters()`; verified in a real browser; prevented by testing the page in a browser, not only in PHP.

---

## 20. What the instructor evaluates → where you show it

Understand requirements → §2 · technical decisions → §3, §7 · implement → all sections · use AI → `README.md` §9 · review AI code → the bug story in §19 · debug → §19 · modify existing code → §18 · explain decisions → §7 · tests → §12 · security → §11 · Git → §13.
