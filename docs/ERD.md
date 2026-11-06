# Database ERD

Rendered automatically by GitHub (Mermaid).

```mermaid
erDiagram
    DEPARTMENTS ||--o{ EMPLOYEES : "has"
    USERS ||--o| EMPLOYEES : "has profile"
    EMPLOYEES ||--o{ EMPLOYEES : "manages (manager_id)"
    EMPLOYEES ||--o{ TASKS : "owns"
    TASKS ||--o{ TASK_HISTORIES : "audit trail"
    USERS ||--o{ TASK_HISTORIES : "acts (actor_id)"
    USERS ||--o{ TASKS : "reviews (reviewed_by)"

    DEPARTMENTS {
        bigint id PK
        varchar name UK
        varchar code UK
        timestamps timestamps
    }
    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar password "hashed"
        varchar role "employee|manager|hr  (indexed)"
        timestamps timestamps
    }
    EMPLOYEES {
        bigint id PK
        bigint user_id FK,UK "-> users.id (cascade)"
        bigint department_id FK "-> departments.id (restrict)"
        bigint manager_id FK "nullable, -> employees.id (set null)"
        varchar employee_number UK
        varchar job_title
        date hired_at "nullable"
        timestamps timestamps
    }
    TASKS {
        bigint id PK
        bigint employee_id FK "-> employees.id (cascade)"
        varchar title "indexed"
        text description "nullable"
        varchar priority "low|medium|high"
        varchar status "PENDING..CANCELLED (indexed)"
        date due_date "nullable, indexed"
        bigint reviewed_by FK "nullable, -> users.id"
        timestamp reviewed_at "nullable"
        text rejection_reason "nullable"
        timestamp completed_at "nullable"
        timestamps timestamps "created_at indexed"
    }
    TASK_HISTORIES {
        bigint id PK
        bigint task_id FK "-> tasks.id (cascade)"
        bigint actor_id FK "nullable, -> users.id (set null)"
        varchar action "created|started|completed|approved|rejected|..."
        varchar from_status "nullable"
        varchar to_status
        text reason "nullable (required on reject)"
        timestamps timestamps
    }
```

## Design decisions

| Decision | Reason |
|---|---|
| `users` (login/role) separate from `employees` (HR profile) | Authentication data and organisational data change for different reasons. Keeps the `users` table small and lets the org structure evolve independently. |
| `employees.manager_id` self-reference | A manager's "team" is simply `employees WHERE manager_id = me`. One query defines the authorisation boundary for both the UI and the API. |
| `tasks.employee_id` points to **employees**, not users | Tasks belong to a position in the organisation, so department/manager can be derived by joining. |
| `task_histories` is append-only | Required "approval history". Stores who, what, from/to status and the reason, so the trail can never be silently rewritten. |
| `tasks.reviewed_by / reviewed_at / rejection_reason` | Denormalised *latest* review for fast list display; the complete record is in `task_histories`. |
| Composite index `(employee_id, status)` | Serves "my tasks by status" and the manager team scope. |
| Indexes on `status`, `due_date`, `created_at`, `title` | Back the HR filters (status, date range, title) and the default ordering. |
| `restrictOnDelete` on `employees.department_id` | A department that still has staff cannot be removed accidentally. |
| `cascadeOnDelete` for task → histories | Histories have no meaning without their task. |
