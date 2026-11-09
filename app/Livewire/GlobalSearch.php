<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The search box in the top bar. It searches the DATABASE as you type (tasks, people, departments)
 * plus the pages of the menu, and only ever returns what the signed-in user may see:
 *
 *   tasks        Task::visibleTo($user)               (own / team / all, exactly like the lists)
 *   people       super admin: all users · HR: all employees · manager: own team · employee: nobody
 *   departments  super admin only
 *
 * Every query is bound (no SQL injection), wildcards typed by the user are escaped, results are
 * limited to a handful per group, and nothing is searched before 2 characters.
 */
class GlobalSearch extends Component
{
    private const LIMIT = 5;

    /** Menu pages passed in by the layout: [label, url, icon]. Locked: the browser cannot change them. */
    #[Locked]
    public array $pages = [];

    public string $q = '';

    public function mount(array $pages = []): void
    {
        $this->pages = $pages;
    }

    /** @return list<array{title:string,items:list<array{label:string,sub:?string,url:string,icon:string}>}> */
    private function search(): array
    {
        $user = auth()->user();
        $term = mb_substr(trim($this->q), 0, 60);
        $groups = [];

        // Pages: all of them while the box is empty, otherwise the matching ones
        $pages = collect($this->pages)
            ->when($term !== '', fn ($c) => $c->filter(fn ($p) => mb_stripos($p['label'], $term) !== false))
            ->take($term === '' ? 8 : self::LIMIT)
            ->map(fn ($p) => ['label' => $p['label'], 'sub' => null, 'url' => $p['url'], 'icon' => $p['icon']])
            ->values()->all();

        if ($term === '') {
            return $pages ? [['title' => __('Pages'), 'items' => $pages]] : [];
        }

        if (mb_strlen($term) >= 2) {
            if ($tasks = $this->tasks($user, $term)) {
                $groups[] = ['title' => __('Tasks'), 'items' => $tasks];
            }
            if ($people = $this->people($user, $term)) {
                $groups[] = ['title' => __('People'), 'items' => $people];
            }
            if ($departments = $this->departments($user, $term)) {
                $groups[] = ['title' => __('Departments'), 'items' => $departments];
            }
        }

        if ($pages) {
            $groups[] = ['title' => __('Pages'), 'items' => $pages];
        }

        return $groups;
    }

    private function tasks(User $user, string $term): array
    {
        return Task::query()
            ->visibleTo($user)
            ->with('employee.user')
            ->tap(fn (Builder $q) => Sql::whereContains($q, 'tasks.title', $term))
            ->latest('tasks.id')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Task $t) => [
                'label' => $t->title,
                'sub' => $t->employee->user->name.' · '.$t->status->label(),
                'url' => route('tasks.show', $t),
                'icon' => 'M9 5h10M9 12h10M9 19h10M4 5h.01M4 12h.01M4 19h.01',
            ])->all();
    }

    private function people(User $user, string $term): array
    {
        $icon = 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0';

        if ($user->isSuperAdmin()) {
            return User::query()
                ->where(function (Builder $q) use ($term) {
                    Sql::whereContains($q, 'users.name', $term);
                    Sql::whereContains($q, 'users.username', $term, 'or');
                    Sql::whereContains($q, 'users.email', $term, 'or');
                })
                ->orderBy('users.name')->limit(self::LIMIT)->get()
                ->map(fn (User $u) => ['label' => $u->name, 'sub' => $u->email.' · '.$u->roleLabels(), 'url' => route('admin.users.edit', $u), 'icon' => $icon])->all();
        }

        if (! $user->isHr() && ! $user->isManager()) {
            return [];                                   // employees do not search for colleagues
        }

        return Employee::query()
            ->visibleTo($user)   // same department only (HR and managers)
            ->with(['user', 'department'])
            ->when($user->isManager() && ! $user->isHr(), fn (Builder $q) => $q->where('manager_id', $user->employee?->id ?? 0))   // managers: own team only
            ->where(function (Builder $q) use ($term) {
                Sql::whereContains($q, 'employees.employee_number', $term);
                Sql::whereContains($q, 'employees.job_title', $term, 'or');
                $q->orWhereHas('user', function (Builder $u) use ($term) {
                    Sql::whereContains($u, 'users.name', $term);
                    Sql::whereContains($u, 'users.email', $term, 'or');
                });
            })
            ->limit(self::LIMIT)->get()
            ->map(fn (Employee $e) => [
                'label' => $e->user->name,
                'sub' => $e->job_title.' · '.$e->department->name,
                'url' => $user->isHr() ? route('employees.index', ['q' => $e->user->name]) : route('team.tasks', ['employeeId' => $e->id, 'status' => '']),
                'icon' => $icon,
            ])->all();
    }

    private function departments(User $user, string $term): array
    {
        if (! $user->isSuperAdmin()) {
            return [];
        }

        return Department::query()
            ->withCount('employees')
            ->where(function (Builder $q) use ($term) {
                Sql::whereContains($q, 'departments.name', $term);
                Sql::whereContains($q, 'departments.code', $term, 'or');
            })
            ->orderBy('name')->limit(self::LIMIT)->get()
            ->map(fn (Department $d) => [
                'label' => $d->name,
                'sub' => $d->code.' · '.$d->employees_count.' '.__('Employees'),
                'url' => route('admin.departments.edit', $d),
                'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01',
            ])->all();
    }

    public function render()
    {
        return view('livewire.global-search', ['groups' => $this->search(), 'term' => trim($this->q)]);
    }
}
