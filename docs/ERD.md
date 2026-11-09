# Database ERD

![TaskFlow ERD](erd.png)

* Image files: [`erd.png`](erd.png) (for slides / printing) and [`erd.svg`](erd.svg) (vector, zoomable).
* The diagram is generated from [`erd.mmd`](erd.mmd) (the source below). Edit that file and re-render to update the pictures.
* **Tables:** departments, users, employees, tasks, task_histories, personal_access_tokens (+ Laravel's sessions/cache/jobs tables, not shown).
* **Relationships:** a department has many employees; a user has one employee profile; an employee manages many employees (`manager_id`, self reference) and owns many tasks; a task has many history rows; a user acts in history rows (`actor_id`) and reviews tasks (`reviewed_by`).

<details><summary>Diagram source (Mermaid - GitHub also renders it)</summary>

```mermaid
erDiagram
    DEPARTMENTS ||--o{ EMPLOYEES : "has (restrict on delete)"
    USERS ||--o| EMPLOYEES : "has profile (cascade)"
    EMPLOYEES ||--o{ EMPLOYEES : "manages (manager_id)"
    EMPLOYEES ||--o{ TASKS : "owns (cascade)"
    TASKS ||--o{ TASK_HISTORIES : "audit trail (cascade)"
    USERS ||--o{ TASK_HISTORIES : "acts (actor_id)"
    USERS ||--o{ TASKS : "reviews (reviewed_by)"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "API tokens"

    DEPARTMENTS {
        bigint id PK
        varchar name UK
        varchar code UK
        timestamp created_at
        timestamp updated_at
    }
    USERS {
        bigint id PK
        varchar name
        varchar username UK "nullable, lowercase"
        varchar email UK
        varchar phone "nullable"
        varchar avatar_path "nullable"
        varchar password "hashed"
        varchar role "employee, manager, hr, super_admin (indexed)"
        varchar remember_token "nullable"
        timestamp created_at
        timestamp updated_at
    }
    EMPLOYEES {
        bigint id PK
        bigint user_id FK, UK
        bigint department_id FK
        bigint manager_id FK "nullable, self reference"
        varchar employee_number UK
        varchar job_title
        date hired_at "nullable"
        timestamp created_at
        timestamp updated_at
    }
    TASKS {
        bigint id PK
        bigint employee_id FK
        varchar title "indexed"
        text description "nullable"
        varchar priority "low, medium, high"
        varchar status "PENDING to CANCELLED (indexed)"
        date due_date "nullable, indexed"
        bigint reviewed_by FK "nullable"
        timestamp reviewed_at "nullable"
        text rejection_reason "nullable"
        timestamp completed_at "nullable"
        timestamp created_at "indexed"
        timestamp updated_at
    }
    TASK_HISTORIES {
        bigint id PK
        bigint task_id FK
        bigint actor_id FK "nullable"
        varchar action "created, started, completed, approved, rejected"
        varchar from_status "nullable"
        varchar to_status
        text reason "nullable (required on reject)"
        timestamp created_at
        timestamp updated_at
    }
    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        varchar tokenable_type
        bigint tokenable_id
        varchar name
        varchar token UK "hashed"
        text abilities "nullable"
        timestamp last_used_at "nullable"
    }
```

</details>

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
