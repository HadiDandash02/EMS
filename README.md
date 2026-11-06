# EMS – Employee Task & Approval Management System

A Laravel application where employees create and complete tasks, managers approve or reject
completed work, and HR gets a company-wide, searchable view – with a complete audit trail of every
action.

**Author:** Ali Ahmad Abbas Hadi
**Stack:** PHP 8.2 · Laravel 12 · MySQL · Livewire 3 · Tailwind CSS 4 · Laravel Sanctum · PHPUnit

---

## 1. Project setup

### Requirements
PHP ≥ 8.2 (with `pdo_mysql`, `pdo_sqlite`), Composer, Node.js ≥ 18, MySQL/MariaDB (XAMPP works).

### Install
```bash
git clone <repository-url> ems && cd ems
composer install
npm install
cp .env.example .env
php artisan key:generate
```
`.env` already points to MySQL (`DB_DATABASE=ems_tasks`, user `root`, empty password – the XAMPP
defaults). Change them if yours differ.

### Database
```bash
php artisan ems:db-create      # creates the MySQL database from .env if it does not exist
php artisan migrate --seed     # creates all tables + demo data
```
Or in one go (drops everything and rebuilds): `php artisan ems:db-create && php artisan migrate:fresh --seed`

### Run
```bash
npm run build                  # production assets   (or `npm run dev` while developing)
php artisan serve              # http://127.0.0.1:8000
```

### Demo accounts (password for all: `password`)
| Role | Email | Notes |
|---|---|---|
| HR | hr@ems.test | sees everything, approves nothing |
| Manager | sarah@ems.test | Engineering – manages Ali, Mona, Omar |
| Manager | david@ems.test | Marketing – manages Lina, Karim |
| Employee | ali@ems.test (also mona, omar, lina, karim) | |
| Employee | nora@ems.test | reports to nobody – good for "outside the team" demos |

The login page shows one-click demo buttons when `APP_ENV=local`.

---

## 2. Architecture

```
Browser ──► Routes ──► Middleware (auth, role) ──► Livewire component / Controller
                                                          │  (thin: input + output only)
                                  Form Request / Livewire rules (validation, TaskRules)
                                                          │
                                       TaskWorkflowService  ◄── TaskPolicy (who may?)
                                       (transaction, lock, status rules, history)
                                                          │
                                              Eloquent models ──► MySQL
```

| Decision | Why |
|---|---|
| **Service class for the workflow** (`app/Services/TaskWorkflowService.php`) | The *only* code that changes a task's status. Web UI, API and seeder all go through it, so rules can't be bypassed or duplicated. |
| **Status rules in an enum** (`app/Enums/TaskStatus.php`) | `transitions()` is the single definition of the state machine; it is unit tested. |
| **Policy for "who", service for "what state"** | Authorisation is a property of user + resource; state validity must be re-checked *inside* the DB transaction. |
| **One query scope for visibility** (`Task::visibleTo`) | Lists (Livewire + API) and the policy agree on what a user may see. |
| **Shared validation rules** (`app/Support/TaskRules.php`) | Livewire form and API Form Requests use identical rules. |
| **Livewire where it adds value** | Live search/filter, pagination, modal create/edit/reject, inline actions with loading states, events between components. Login and dashboard are plain Blade (no interactivity needed). |
| **Sanctum bearer tokens for the API** | Stateless, revocable, no CSRF needed for token clients. |

Main folders: `app/Enums`, `app/Models`, `app/Policies`, `app/Services`, `app/Livewire`,
`app/Http/{Controllers,Requests,Resources,Middleware}`, `database/{migrations,factories,seeders}`, `tests`.

---

## 3. Database & relationships

Full diagram and design notes: **[docs/ERD.md](docs/ERD.md)** (Mermaid – renders on GitHub).

```
departments 1───* employees *───1 users          employees.manager_id ──► employees.id (self reference)
employees   1───* tasks
tasks       1───* task_histories *───1 users (actor)
tasks.reviewed_by ──► users
```

* `users` = login + role (`employee|manager|hr`). `employees` = organisational profile
  (department, manager, job title). One user ↔ one employee.
* A manager's **team** = employees whose `manager_id` is the manager's employee id. That single
  relationship is the authorisation boundary.
* `task_histories` is an append-only audit log (who, action, from→to status, reason).
* Foreign keys everywhere; `RESTRICT` on department delete, `CASCADE` for tasks→histories.
* Indexes follow the real queries: `(employee_id, status)`, `status`, `due_date`, `created_at`, `title`, `users.role`,
  `(task_id, created_at)` and `(actor_id, action)` on histories.

---

## 4. Business rules – task status workflow

```
                    ┌──────────────► CANCELLED  (final)
                    │ (pending only)
 PENDING ──► IN_PROGRESS ──► COMPLETED ──► APPROVED  (final)
    │                            ▲   │
    └────────────────────────────┘   └──► REJECTED ──► IN_PROGRESS  (rework)
```

| From | Allowed to | Who |
|---|---|---|
| PENDING | IN_PROGRESS, COMPLETED, CANCELLED | owner |
| IN_PROGRESS | COMPLETED | owner |
| COMPLETED | APPROVED, REJECTED | the owner's manager (not the owner, not HR) |
| REJECTED | IN_PROGRESS ("rework"), then COMPLETED again | owner |
| APPROVED, CANCELLED | – (final) | – |

**Decisions**
* An approved task is **final**; it can never go back to pending. A rejected task is *not* final – the
  employee reworks it and resubmits, and the history keeps every round.
* A pending task may be completed directly (small tasks) – the "in progress" step is optional.
* Only **pending** tasks can be edited or cancelled (per the requirements).
* Rejection **requires** a reason (min. 5 chars). Enforced in the form, the API *and* the service.
* Managers cannot review their own tasks; they cannot review tasks outside their team; HR is read-only.
* **Transaction decision:** every transition runs in `DB::transaction()` with `lockForUpdate()` on the task row.
  The status update and the history insert must succeed or fail together (no approved task without an audit
  record), and the lock stops two managers approving/rejecting the same task simultaneously – the second one
  re-reads the new status and is refused. A test proves the rollback (`test_status_and_history_change_together_or_not_at_all`).

---

## 5. Authorization

Three layers, all **server side** (hiding buttons is only a UX hint):

1. **Route middleware** – `auth` for everything; `role:manager`, `role:hr`, `role:manager,hr` for area access
   (`app/Http/Middleware/EnsureUserHasRole.php`). Wrong role → 403.
2. **Policy** – `app/Policies/TaskPolicy.php` (`view`, `update`, `start`, `complete`, `cancel`, `approve`, `reject`),
   returning explicit denial messages. Used by controllers, Livewire (`$this->authorize`) and the service.
3. **Service re-check** – `TaskWorkflowService` authorises the actor against the *locked, freshly loaded* row,
   so even a forged Livewire call or direct service use cannot skip it.

Also: lists use `Task::visibleTo($user)`; Livewire `scope` is `#[Locked]` (browser can't switch "mine" to "all");
`role` is not mass assignable; the API resource whitelists output (no emails/hashes); IDs in URLs are
policy-checked (changing `/tasks/5` to someone else's task gives **403**).

## 6. Validation

* Livewire form → `TaskRules::fields()` (`app/Support/TaskRules.php`) and `RejectModal` rules.
* API → Form Requests in `app/Http/Requests/Api/` (`StoreTaskRequest`, `UpdateTaskRequest`, `RejectTaskRequest`, `ListTasksRequest`).
  Update/Reject requests authorise *before* validating.
* Business-rule validation (status, reason) → `TaskWorkflowService`.
* Mass assignment: `Task::$fillable` = title, description, priority, due_date only. Status, owner and review fields are
  assigned by the service (`test_client_cannot_set_status_owner_or_review_fields`).
* SQL injection: Eloquent bound parameters; user LIKE terms are escaped (`app/Support/Sql.php`). CSRF: Laravel's web middleware
  (login, logout, every Livewire request). Login is throttled.

---

## 7. REST API

Base URL `/api`. Always JSON. Authenticate with `Authorization: Bearer <token>`.

| Method | Endpoint | Description | Success |
|---|---|---|---|
| POST | `/api/login` | `email`, `password` → token | 200 |
| POST | `/api/logout` | revoke current token | 200 |
| GET | `/api/tasks` | list *visible* tasks, paginated. Filters: `search`, `status`, `priority`, `employee_id`, `department_id`, `date_from`, `date_to`, `per_page` (≤50), `page` | 200 |
| POST | `/api/tasks` | create (own task): `title`*, `description`, `priority`*, `due_date` | 201 |
| GET | `/api/tasks/{id}` | details + history | 200 |
| PUT/PATCH | `/api/tasks/{id}` | update own **pending** task (partial allowed) | 200 |
| POST | `/api/tasks/{id}/start` | PENDING/REJECTED → IN_PROGRESS | 200 |
| POST | `/api/tasks/{id}/complete` | → COMPLETED | 200 |
| POST | `/api/tasks/{id}/cancel` | PENDING → CANCELLED | 200 |
| POST | `/api/tasks/{id}/approve` | manager of the owner: COMPLETED → APPROVED | 200 |
| POST | `/api/tasks/{id}/reject` | `reason`* (min 5): COMPLETED → REJECTED | 200 |

Error codes: **401** no/invalid token · **403** not allowed · **404** not found · **422** validation error *or* invalid status transition.

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/login -H "Accept: application/json" \
  -d email=ali@ems.test -d password=password | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')

curl -s -X POST http://127.0.0.1:8000/api/tasks -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -d title="Write release notes" -d priority=high -d due_date=2026-12-01
curl -s "http://127.0.0.1:8000/api/tasks?status=COMPLETED&per_page=5" -H "Authorization: Bearer $TOKEN"
```

Example task object: `{ id, title, description, priority:{value,label}, status:{value,label}, due_date, completed_at,
employee:{id,name,department}, review:{reviewed_by,reviewed_at,rejection_reason}, history:[…], abilities:{update,approve} }`.

---

## 8. Testing

Tests run against in-memory SQLite (fast, isolated) – no MySQL needed.
```bash
php artisan test                       # everything
php artisan test --filter=ApprovalWorkflow
php artisan test --testsuite=Unit
```
| File | Covers |
|---|---|
| `Unit/TaskStatusTest` | the state machine (allowed + forbidden transitions) |
| `Feature/TaskCreationTest` | create, invalid data rejected, edit rules |
| `Feature/TaskAuthorizationTest` | IDOR (other employee's task), role areas, scope tampering, mass-assigned role |
| `Feature/ApprovalWorkflowTest` | approve/reject rules, no double approval, own/outside-team, reason required, **transaction rollback**, rework cycle |
| `Feature/TaskSearchFilterTest` | HR filters, pagination, wildcard & SQL-injection safety, N+1 guard |
| `Feature/Api/TaskApiTest` | 401, 403, 404, 422, create/update/approve/reject, pagination shape, no sensitive data |

---

## 9. AI usage

I used Claude (Claude Code) as a pair-programming assistant for scaffolding and review: drafting the
migrations/models, the policy, the service and the Livewire/Blade views, and writing the test suite.
I made/reviewed the design decisions (separate `users`/`employees`, status transitions, transaction + row
lock, what the API exposes) and ran, debugged and verified everything (e.g. fixed a MySQL key-length error,
Livewire event dispatching from Alpine, a SQLite/MySQL `LIKE` escape difference, a PHP 8 ternary error and a
missing Laravel 12 `authorize()` helper that the tests caught). I can explain every file in this repository.

## 10. Known limitations / future improvements

* No email/in-app notifications when a task is approved or rejected.
* Employees cannot attach files or comments to tasks.
* Only a direct manager can approve (no delegation/backup approver or multi-level approval).
* Confirmations use the browser `confirm()` dialog instead of a custom modal.
* No admin screen to create employees/departments (they are seeded).
* The API is token-only; no refresh/expiry policy for tokens beyond Sanctum defaults.
* Browser (E2E) tests are not included; UI behaviour is covered with Livewire component tests.

## 11. Git workflow

`main` is kept untouched until the Pull Request. Work happened on `feature/task-management`, built from small
topic branches merged with `--no-ff` (`chore/project-setup`, `feature/database-structure`,
`feature/authorization-workflow`, `feature/web-interface`, `feature/task-api`, `feature/automated-tests`, `docs/readme`).
